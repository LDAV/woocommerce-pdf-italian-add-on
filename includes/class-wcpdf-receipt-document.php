<?php
use WPO\WC\PDF_Invoices\Documents\Order_Document;
use WPO\WC\PDF_Invoices\Documents\Order_Document_Methods;
use WPO\WC\PDF_Invoices\Documents\Sequential_Number_Store;
use WPO\WC\PDF_Invoices\Compatibility\WC_Core as WCX;
use WPO\WC\PDF_Invoices\Compatibility\Order as WCX_Order;
use WPO\WC\PDF_Invoices\Compatibility\Product as WCX_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WPO_WCPDF_Receipt_Document' ) ) :

/**
 * Receipt document
 * 
 * @class       WPO_WCPDF_Receipt_Document
 * @version     2.0
 * @category    Class
 * @author      Ewout Fernhout modified by labdav
 */

class WPO_WCPDF_Receipt_Document extends Order_Document_Methods {
	/**
	 * Set document properties and init/load the order object (when passed).
	 *
	 * @param  int|object|WC_Order $order Order to init.
	 */
	public function __construct( $order = 0 ) {
		// set properties
		$this->type		= 'receipt';
		$this->title	= __( 'Receipt', WCPDF_IT_DOMAIN );
		$this->icon		= plugin_dir_url(dirname(__FILE__)) . "images/receipt.svg";

		// Call parent constructor
		parent::__construct( $order );
	}

	public function get_title() {
		// override/not using $this->title to allow for language switching!
		return apply_filters( "wpo_wcpdf_{$this->slug}_title", __( 'Receipt', WCPDF_IT_DOMAIN ) );
	}

	public function init() {
		$this->set_date( current_time( 'timestamp', true ) );
		$this->init_number();
	}

	public function init_number() {
		global $wpdb;
		// If a third-party plugin claims to generate receipt numbers, trigger this instead
		if ( apply_filters( 'woocommerce_receipt_number_by_plugin', false ) || apply_filters( 'wpo_wcpdf_external_receipt_number_enabled', false, $this ) ) {
			$receipt_number = apply_filters( 'woocommerce_generate_receipt_number', null, $this->order );
			$receipt_number = apply_filters( 'wpo_wcpdf_external_receipt_number', $receipt_number, $this );
			if ( is_numeric($receipt_number) || $receipt_number instanceof Document_Number ) {
				$this->set_number( $receipt_number );
			} else {
				// receipt number is not numeric, treat as formatted
				// try to extract meaningful number data
				$formatted_number = $receipt_number;
				$number = (int) preg_replace('/\D/', '', $receipt_number);
				$receipt_number = compact( 'number', 'formatted_number' );
				$this->set_number( $receipt_number );				
			}
			return $receipt_number;
		}

		$number_store_method = WPO_WCPDF()->settings->get_sequential_number_store_method();
		$number_store = new Sequential_Number_Store( 'receipt_number', $number_store_method );
		// reset receipt number yearly
		if ( isset( $this->settings['reset_number_yearly'] ) ) {
			$current_year = date("Y");
			$last_number_year = $number_store->get_last_date('Y');
			// check if we need to reset
			if ( $current_year != $last_number_year ) {
				$number_store->set_next( 1 );
			}
		}

		$receipt_date = $this->get_date();
		$receipt_number = $number_store->increment( $this->order_id, $receipt_date->date_i18n( 'Y-m-d H:i:s' ) );

		$this->set_number( $receipt_number );

		return $receipt_number;
	}

	public function get_filename( $context = 'download', $args = array() ) {
		$order_count = isset($args['order_ids']) ? count($args['order_ids']) : 1;

		$name = _n( 'receipt', 'receipts', $order_count, WCPDF_IT_DOMAIN );

		if ( $order_count == 1 ) {
			if ( isset( $this->settings['display_number'] ) && $this->settings['display_number'] == 'invoice_number' ) {
				$suffix = (string) $this->get_number();
			} else {
				if ( empty( $this->order ) && isset( $args['order_ids'][0] ) ) {
					$order = wc_get_order( $args['order_ids'][0] );
					$suffix = is_callable( array( $order, 'get_order_number' ) ) ? $order->get_order_number() : '';
				} else {
					$suffix = is_callable( array( $this->order, 'get_order_number' ) ) ? $this->order->get_order_number() : '';
				}
			}
			// ensure unique filename in case suffix was empty
			if ( empty( $suffix ) ) {
				if ( ! empty( $this->order_id ) ) {
					$suffix = $this->order_id;
				} elseif ( ! empty( $args['order_ids'] ) && is_array( $args['order_ids'] ) ) {
					$suffix = reset( $args['order_ids'] );
				} else {
					$suffix = uniqid();
				}
			}
		} else {
			$suffix = date_i18n( 'Y-m-d' ); // 2024-12-31
		}

		// get filename
		$output_format = ! empty( $args['output'] ) ? esc_attr( $args['output'] ) : 'pdf';
		$filename      = $name . '-' . $suffix . wcpdf_get_document_output_format_extension( $output_format );

		// Filter filename
		$order_ids = isset( $args['order_ids'] ) ? $args['order_ids'] : array( $this->order_id );
		$filename  = apply_filters( 'wpo_wcpdf_filename', $filename, $this->get_type(), $order_ids, $context, $args );

		// sanitize filename (after filters to prevent human errors)!
		return sanitize_file_name( $filename );
	}

	public function get_receipt_number() {
		// Call the woocommerce_receipt_number filter and let third-party plugins set a number.
		// Default is null, so we can detect whether a plugin has set the receipt number
		$third_party_receipt_number = apply_filters( 'woocommerce_receipt_number', null, $this->order_id );
		if ($third_party_receipt_number !== null) {
			return $third_party_receipt_number;
		}

		if ( $receipt_number = $this->get_number('receipt') ) {
			return $formatted_receipt_number = $receipt_number->formatted_number;
		} else {
			return '';
		}
	}

	public function receipt_number() {
		echo $this->get_receipt_number();
	}

	public function get_receipt_date() {
		if ( $receipt_date = $this->get_date('receipt') ) {
			return $receipt_date->date_i18n( apply_filters( 'wpo_wcpdf_date_format', wc_date_format(), $this ) );
		} else {
			return '';
		}
	}

	public function receipt_date() {
		echo $this->get_receipt_date();
	}

	public function init_settings() {
		// Register settings.
		$page = $option_group = $option_name = 'wpo_wcpdf_documents_settings_receipt';

		$settings_fields = array(
			array(
				'type'			=> 'section',
				'id'			=> 'receipt',
				'title'			=> '',
				'callback'		=> 'section',
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'enabled',
				'title'			=> __( 'Enable', WCPDF_IT_DOMAIN ),
				'callback'		=> 'checkbox',
				'section'		=> 'receipt',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'enabled',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'attach_to_email_ids',
				'title'			=> __( 'Attach to:', WCPDF_IT_DOMAIN ),
				'callback'		=> 'multiple_checkboxes',
				'section'		=> 'receipt',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'attach_to_email_ids',
					'fields' 		=> $this->get_wc_emails(),
					'description'	=> !is_writable( WPO_WCPDF()->main->get_tmp_path( 'attachments' ) ) ? '<span class="wpo-warning">' . sprintf( __( 'It looks like the temp folder (<code>%s</code>) is not writable, check the permissions for this folder! Without having write access to this folder, the plugin will not be able to email invoices.', 'woocommerce-pdf-invoices-packing-slips' ), WPO_WCPDF()->main->get_tmp_path( 'attachments' ) ).'</span>':'',
				)
			),

			array(
				'type'			=> 'setting',
				'id'			=> 'display_shipping_address',
				'title'			=> __( 'Display shipping address', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'receipt',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'display_shipping_address',
					'description'		=> __( 'Display shipping address (in addition to the default billing address) if different from billing address', 'woocommerce-pdf-invoices-packing-slips' ),
				)
			),

			array(
				'type'			=> 'setting',
				'id'			=> 'display_email',
				'title'			=> __( 'Display email address', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'receipt',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'display_email',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'display_phone',
				'title'			=> __( 'Display phone number', 'woocommerce-pdf-invoices-packing-slips' ),
				'callback'		=> 'checkbox',
				'section'		=> 'receipt',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'display_phone',
				)
			),

			array(
				'type'			=> 'setting',
				'id'			=> 'display_date',
				'title'			=> __( 'Display receipt date', WCPDF_IT_DOMAIN ),
				'callback'		=> 'checkbox',
				'section'		=> 'receipt',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'display_date',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'display_number',
				'title'			=> __( 'Display receipt number', WCPDF_IT_DOMAIN ),
				'callback'		=> 'checkbox',
				'section'		=> 'receipt',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'display_number',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'next_receipt_number',
				'title'			=> __( 'Next receipt number', WCPDF_IT_DOMAIN ),
				'callback'		=> 'next_number_edit',
				'section'		=> 'receipt',
				'args'			=> array(
					'store'			=> 'receipt_number',
					'size'			=> '10',
					'description'	=> __( 'This is the number that will be used for the next document. By default, numbering starts from 1 and increases for every new document. Note that if you override this and set it lower than the current/highest number, this could create duplicate numbers!', WCPDF_IT_DOMAIN ),
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'number_format',
				'title'			=> __( 'Number format', WCPDF_IT_DOMAIN ),
				'callback'		=> 'multiple_text_input',
				'section'		=> 'receipt',
				'args'			=> array(
					'option_name'			=> $option_name,
					'id'					=> 'number_format',
					'fields'				=> array(
						'prefix'			=> array(
							'placeholder'	=> __( 'Prefix' , WCPDF_IT_DOMAIN ),
							'size'			=> 20,
							'description'	=> __( 'to use the receipt year and/or month, use [receipt_year] or [receipt_month] respectively' , WCPDF_IT_DOMAIN ),
						),
						'suffix'			=> array(
							'placeholder'	=> __( 'Suffix' , WCPDF_IT_DOMAIN ),
							'size'			=> 20,
							'description'	=> '',
						),
						'padding'			=> array(
							'placeholder'	=> __( 'Padding' , WCPDF_IT_DOMAIN ),
							'size'			=> 20,
							'type'			=> 'number',
							'description'	=> __( 'enter the number of digits here - enter "6" to display 42 as 000042' , WCPDF_IT_DOMAIN ),
						),
					),
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'reset_number_yearly',
				'title'			=> __( 'Reset receipt number yearly', WCPDF_IT_DOMAIN ),
				'callback'		=> 'checkbox',
				'section'		=> 'receipt',
				'args'			=> array(
					'option_name'		=> $option_name,
					'id'				=> 'reset_number_yearly',
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'my_account_buttons',
				'title'			=> __( 'Allow My Account receipt download', WCPDF_IT_DOMAIN ),
				'callback'		=> 'select',
				'section'		=> 'receipt',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'my_account_buttons',
					'options' 		=> array(
						'available'	=> __( 'Only when an receipt is already created/emailed' , WCPDF_IT_DOMAIN ),
						'custom'	=> __( 'Only for specific order statuses (define below)' , WCPDF_IT_DOMAIN ),
						'always'	=> __( 'Always' , WCPDF_IT_DOMAIN ),
						'never'		=> __( 'Never' , WCPDF_IT_DOMAIN ),
					),
					'custom'		=> array(
						'type'		=> 'multiple_checkboxes',
						'args'		=> array(
							'option_name'	=> $option_name,
							'id'			=> 'my_account_restrict',
							'fields'		=> $this->get_wc_order_status_list(),
						),
					),
				)
			),
			
			array(
				'type'			=> 'setting',
				'id'			=> 'receipt_number_column',
				'title'			=> __( 'Enable receipt number column in the orders list', WCPDF_IT_DOMAIN ),
				'callback'		=> 'checkbox',
				'section'		=> 'receipt',
				'args'			=> array(
					'option_name'	=> $option_name,
					'id'			=> 'receipt_number_column',
				)
			),

		);

		// allow plugins to alter settings fields
		$settings_fields = apply_filters( "wpo_wcpdf_settings_fields_documents_{$this->slug}", $settings_fields, $page, $option_group, $option_name );
		WPO_WCPDF()->settings->add_settings_fields( $settings_fields, $page, $option_group, $option_name );
		return;

	}

	public function get_formatted_number() {
		if ( $number = $this->get_number( $this->get_type() ) ) {
			return $formatted_number = $number->formatted_number;
		} else {
			return '';
		}
	}

	public function formatted_number() {
		echo $this->get_formatted_number();
	}

	public function get_formatted_date() {
		if ( $date = $this->get_date( $this->get_type() ) ) {
			return $date->date_i18n( apply_filters( 'wpo_wcpdf_date_format', wc_date_format(), $this ) );
		} else {
			return '';
		}
	}

	public function formatted_date() {
		echo $this->get_formatted_date();
	}

}

endif; // class_exists

// return the class instance
return new WPO_WCPDF_Receipt_Document();