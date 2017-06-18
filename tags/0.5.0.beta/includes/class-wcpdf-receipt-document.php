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
		$this->title	= __( 'Receipt', 'woocommerce-pdf-italian-add-on' );
		$this->icon		= plugin_dir_url(dirname(__FILE__)) . "images/receipt.png";

		// Call parent constructor
		parent::__construct( $order );
	}

	public function get_title() {
		// override/not using $this->title to allow for language switching!
		return apply_filters( "wpo_wcpdf_{$this->slug}_title", __( 'Receipt', 'woocommerce-pdf-italian-add-on' ) );
	}

	public function init() {
		$this->set_date( current_time( 'timestamp', true ) );
		$this->init_number();
	}

	public function init_number() {
		$number_store = new Sequential_Number_Store( 'receipt_number' );
		$document_date = $this->get_date();
		$document_number = $number_store->increment( $this->order_id, $document_date->date_i18n( 'Y-m-d H:i:s' ) );

		$this->set_number( $document_number );

		return $document_number;
	}

	public function get_filename( $context = 'download', $args = array() ) {
		$order_count = isset($args['order_ids']) ? count($args['order_ids']) : 1;

		$name = _n( 'receipt', 'receipts', $order_count, 'woocommerce-pdf-italian-add-on' );

		if ( $order_count == 1 ) {
			if ( isset( $this->settings['display_number'] ) ) {
				$suffix = (string) $this->get_number();
			} else {
				if ( empty( $this->order ) ) {
					$order = wc_get_order ( $order_ids[0] );
					$suffix = method_exists( $order, 'get_order_number' ) ? $order->get_order_number() : '';
				} else {
					$suffix = method_exists( $this->order, 'get_order_number' ) ? $this->order->get_order_number() : '';
				}
			}
		} else {
			$suffix = date('Y-m-d'); // 2020-11-11
		}

		$filename = $name . '-' . $suffix . '.pdf';

		// Filter filename
		$order_ids = isset($args['order_ids']) ? $args['order_ids'] : array( $this->order_id );
		$filename = apply_filters( 'wpo_wcpdf_filename', $filename, $this->get_type(), $order_ids, $context );

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

	public function get_settings() {
		$common_settings = WPO_WCPDF()->settings->get_common_document_settings();
		$document_settings = get_option( 'wpo_wcpdf_documents_settings_'.$this->get_type() );
		return (array) $document_settings + (array) $common_settings;
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
				'title'			=> __( 'Enable', 'woocommerce-pdf-italian-add-on' ),
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
				'title'			=> __( 'Attach to:', 'woocommerce-pdf-italian-add-on' ),
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
				'id'			=> 'display_date',
				'title'			=> __( 'Display receipt date', 'woocommerce-pdf-italian-add-on' ),
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
				'title'			=> __( 'Display receipt number', 'woocommerce-pdf-italian-add-on' ),
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
				'title'			=> __( 'Next receipt number', 'woocommerce-pdf-italian-add-on' ),
				'callback'		=> 'next_number_edit',
				'section'		=> 'receipt',
				'args'			=> array(
					'store'			=> 'receipt_number',
					'size'			=> '10',
					'description'	=> __( 'This is the number that will be used for the next document. By default, numbering starts from 1 and increases for every new document. Note that if you override this and set it lower than the current/highest number, this could create duplicate numbers!', 'woocommerce-pdf-italian-add-on' ),
				)
			),
			array(
				'type'			=> 'setting',
				'id'			=> 'number_format',
				'title'			=> __( 'Number format', 'woocommerce-pdf-italian-add-on' ),
				'callback'		=> 'multiple_text_input',
				'section'		=> 'receipt',
				'args'			=> array(
					'option_name'			=> $option_name,
					'id'					=> 'number_format',
					'fields'				=> array(
						'prefix'			=> array(
							'placeholder'	=> __( 'Prefix' , 'woocommerce-pdf-italian-add-on' ),
							'size'			=> 20,
							'description'	=> __( 'to use the receipt year and/or month, use [receipt_year] or [receipt_month] respectively' , 'woocommerce-pdf-italian-add-on' ),
						),
						'suffix'			=> array(
							'placeholder'	=> __( 'Suffix' , 'woocommerce-pdf-italian-add-on' ),
							'size'			=> 20,
							'description'	=> '',
						),
						'padding'			=> array(
							'placeholder'	=> __( 'Padding' , 'woocommerce-pdf-italian-add-on' ),
							'size'			=> 20,
							'type'			=> 'number',
							'description'	=> __( 'enter the number of digits here - enter "6" to display 42 as 000042' , 'woocommerce-pdf-italian-add-on' ),
						),
					),
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
		echo $this->get_formatted_number( $this->get_type() );
	}

	public function get_formatted_date() {
		if ( $date = $this->get_date( $this->get_type() ) ) {
			return $date->date_i18n( apply_filters( 'wpo_wcpdf_date_format', wc_date_format(), $this ) );
		} else {
			return '';
		}
	}

	public function formatted_date() {
		echo $this->get_formatted_date( $this->get_type() );
	}

}

endif; // class_exists

// return the class instance
return new WPO_WCPDF_Receipt_Document();