<?php
use \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'wcpdf_Integration_Italian_add_on' )) :

class wcpdf_Integration_Italian_add_on extends WooCommerce_Italian_add_on {

	public function __construct() {
		add_filter( 'wpo_wcpdf_meta_box_actions' , array( $this, 'wcpdf_meta_box_actions'), 20, 2 );
		add_filter( 'wpo_wcpdf_listing_actions' , array( $this, 'wcpdf_listing_actions'), 20, 2 );
		add_filter( 'wpo_wcpdf_bulk_actions' , array( $this, 'wcpdf_bulk_actions') );

		add_filter( 'wpo_wcpdf_custom_attachment_condition' , array( $this, 'wcpdf_custom_email_condition'), 8,4);
		add_filter( 'wpo_wcpdf_myaccount_actions', array( $this, 'wcpdf_my_account'), 10, 2 );
		add_filter( 'wpo_wcpdf_template_file', array( $this, 'wcpdf_template_files'), 20, 3 );

		//add_action( 'add_meta_boxes_shop_order', array( $this, 'wcpdf_add_meta_boxes' ), 20, 1 );
		add_action( 'add_meta_boxes', array( $this, 'wcpdf_add_meta_boxes' ), 20, 2 );
		add_action( 'save_post', array( $this,'wcpdf_save_receipt_number_date' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wcpdf_admin_enqueue_scripts' ) );
		add_filter( 'wpo_wcpdf_document_classes', array( $this, 'wcpdf_register_documents' ), 10, 1 );

		if($this->receipt_columns_enabled()) {
			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_receipt_number_column' ), 999 ); // WC 7.1+
			add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'receipt_number_column_data' ), 10, 2 ); // WC 7.1+
			//add_filter( 'manage_woocommerce_page_wc-orders_sortable_columns', array( $this, 'receipt_number_column_sortable' ) ); // WC 7.1+
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_receipt_number_column' ), 999 );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'receipt_number_column_data' ), 10, 2 );
			add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'receipt_number_column_sortable' ) );
			if ( !class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) || !OrderUtil::custom_orders_table_usage_is_enabled() ) {
				add_filter( 'pre_get_posts', array( $this, 'sort_by_receipt_number' ) );
			}
		}
	}

	public function wcpdf_register_documents( $documents ) {
		$documents['WPO_WCPDF_Receipt_Document'] = include( 'class-wcpdf-receipt-document.php' );
		return $documents;
	}

	public function receipt_columns_enabled() {
		$is_enabled       = false;
		$invoice_settings = get_option( 'wpo_wcpdf_documents_settings_receipt', array() );
		$invoice_columns  = [
			'receipt_number_column',
			'receipt_date_column',
		];
		
		foreach ( $invoice_columns as $column ) {
			if ( isset( $invoice_settings[$column] ) ) {
				$is_enabled = true;
				break;
			}
		}
		
		return $is_enabled;
	}

	
	public function wcpdf_admin_enqueue_scripts ( $hook ) {
		wp_enqueue_style(
			'wcpdf-order-receipt-styles',
			plugin_dir_url( plugin_basename(__FILE__) ) .  'order-styles.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			'wcpdf-order-receipt',
			plugin_dir_url( plugin_basename(__FILE__) ) . 'order-script.js',
			array( 'jquery' ),
			$this->version
		);
	}
	
	public function wcpdf_add_meta_boxes( $wc_screen_id, $order ) {
		if ( function_exists( 'wc_get_container' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
			$screen_id = wc_get_page_screen_id( 'shop-order' );
		} else {
			$screen_id = 'shop_order';
		}

		if ( $wc_screen_id != $screen_id ) return;

		//$order_id = $post->ID;
		//$order = wc_get_order($order_id);
		$invoicetype = wcpdf_it_get_billing_invoice_type($order);
		if($invoicetype === "receipt"  || (!$invoicetype && WCPDF_IT()->what_if_no_invoicetype === "receipt")) {
			remove_meta_box( 'wpo_wcpdf-data-input-box', $screen_id, 'normal' );
			add_meta_box(
				'wpo_wcpdf-data-input-box',
				__( 'PDF document data', 'woocommerce-pdf-invoices-packing-slips' ),
				array( $this, 'data_input_box_content' ),
				$screen_id,
				'normal',
				'default'
			);
		}
	}

	public function data_input_box_content ( $post_or_order_object ) {
		$order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		$this->disable_storing_document_settings();
		$receipt = wcpdf_get_document( 'receipt', $order );

		do_action( 'wpo_wcpdf_meta_box_start', $order, $this );

		if ( $receipt ) {
			// data
			$data = array(
				'number' => array(
					'label'  => __( 'Receipt Number:', WCPDF_IT_DOMAIN ),
				),
				'date'   => array(
					'label'  => __( 'Receipt Date:', WCPDF_IT_DOMAIN ),
				),
			);
			// output
			$this->output_number_date_edit_fields( $receipt, $data );
		}

		do_action( 'wpo_wcpdf_meta_box_end', $order, $this );
	}
	
	public function disable_storing_document_settings() {
		add_filter( 'wpo_wcpdf_document_store_settings', array( $this, 'return_false' ), 9999 );
	}

	public function restore_storing_document_settings() {
		remove_filter( 'wpo_wcpdf_document_store_settings', array( $this, 'return_false' ), 9999 );
	}

	public function return_false(){
		return false;
	}

	public function get_current_values_for_document( $document, $data ) {
		$current = array(
			'number' => array(
				'plain'     => $document->exists() && ! empty( $document->get_number() ) ? $document->get_number()->get_plain() : '',
				'formatted' => $document->exists() && ! empty( $document->get_number() ) ? $document->get_number()->get_formatted() : '',
				'name'      => "_wcpdf_{$document->slug}_number",
			),
			'date' => array(
				'formatted' => $document->exists() && ! empty( $document->get_date() ) ? $document->get_date()->date_i18n( wc_date_format().' @ '.wc_time_format() ) : '',
				'date'      => $document->exists() && ! empty( $document->get_date() ) ? $document->get_date()->date_i18n( 'Y-m-d' ) : date_i18n( 'Y-m-d' ),
				'hour'      => $document->exists() && ! empty( $document->get_date() ) ? $document->get_date()->date_i18n( 'H' ) : date_i18n( 'H' ),
				'minute'    => $document->exists() && ! empty( $document->get_date() ) ? $document->get_date()->date_i18n( 'i' ) : date_i18n( 'i' ),
				'name'      => "_wcpdf_{$document->slug}_date",
			),
		);

		if ( !empty( $data['notes'] ) ) {
			$current['notes'] = array(
				'value' => $document->get_document_notes(),
				'name'  =>"_wcpdf_{$document->slug}_notes",
			);
		}

		foreach ( $data as $key => $value ) {
			if ( isset( $current[$key] ) ) {
				$data[$key] = array_merge( $current[$key], $value );
			}
		}

		return apply_filters( 'wpo_wcpdf_current_values_for_document', $data, $document );
	}

	public function output_number_date_edit_fields( $document, $data ) {
		if( empty( $document ) || empty( $data ) ) return;
		$data = $this->get_current_values_for_document( $document, $data );
		?>
		<div class="wcpdf-data-fields" data-document="<?= $document->get_type(); ?>" data-order_id="<?php echo $document->order->get_id(); ?>">
			<section class="wcpdf-data-fields-section number-date">
				<!-- Title -->
				<h4>
					<?= $document->get_title(); ?>
					<?php if( $document->exists() && ( isset( $data['number'] ) || isset( $data['date'] ) ) ) : ?>
						<span class="wpo-wcpdf-edit-date-number dashicons dashicons-edit"></span>
						<span class="wpo-wcpdf-delete-document dashicons dashicons-trash" data-action="delete" data-nonce="<?php echo wp_create_nonce( "wpo_wcpdf_delete_document" ); ?>"></span>
						<?php do_action( 'wpo_wcpdf_document_actions', $document ); ?>
					<?php endif; ?>
				</h4>

				<!-- Read only -->
				<div class="read-only">
					<?php if( $document->exists() ) : ?>
						<?php if( isset( $data['number'] ) ) : ?>
						<div class="<?= $document->get_type(); ?>-number">
							<p class="form-field <?= $data['number']['name']; ?>_field">	
								<p>
									<span><strong><?= $data['number']['label']; ?></strong></span>
									<span><?= $data['number']['formatted']; ?></span>
								</p>
							</p>
						</div>
						<?php endif; ?>
						<?php if( isset( $data['date'] ) ) : ?>
						<div class="<?= $document->get_type(); ?>-date">
							<p class="form-field form-field-wide">
								<p>
									<span><strong><?= $data['date']['label']; ?></strong></span>
									<span><?= $data['date']['formatted']; ?></span>
								</p>
							</p>
						</div>
						<?php endif; ?>
						<?php do_action( 'wpo_wcpdf_meta_box_after_document_data', $document, $document->order ); ?>
					<?php else : ?>
						<?php /* translators: document title */ ?>
						<span class="wpo-wcpdf-set-date-number button"><?php printf( __( 'Set %s number & date', 'woocommerce-pdf-invoices-packing-slips' ), $document->get_title() ); ?></span>
					<?php endif; ?>
				</div>

				<!-- Editable -->
				<div class="editable">
					<?php if( isset( $data['number'] ) ) : ?>
					<p class="form-field <?= $data['number']['name']; ?>_field ">
						<label for="<?= $data['number']['name']; ?>"><?= $data['number']['label']; ?></label>
						<input type="text" class="short" style="" name="<?= $data['number']['name']; ?>" id="<?= $data['number']['name']; ?>" value="<?= $data['number']['plain']; ?>" disabled="disabled" > (<?= __( 'unformatted!', 'woocommerce-pdf-invoices-packing-slips' ) ?>)
					</p>
					<?php endif; ?>
					<?php if( isset( $data['date'] ) ) : ?>
					<p class="form-field form-field-wide">
						<label for="<?= $data['date']['name'] ?>[date]"><?= $data['date']['label']; ?></label>
						<input type="text" class="date-picker-field" name="<?= $data['date']['name'] ?>[date]" id="<?= $data['date']['name'] ?>[date]" maxlength="10" value="<?= $data['date']['date']; ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" disabled="disabled"/>@<input type="number" class="hour" disabled="disabled" placeholder="<?php _e( 'h', 'woocommerce' ); ?>" name="<?= $data['date']['name']; ?>[hour]" id="<?= $data['date']['name']; ?>[hour]" min="0" max="23" size="2" value="<?= $data['date']['hour']; ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:<input type="number" class="minute" placeholder="<?php _e( 'm', 'woocommerce' ); ?>" name="<?= $data['date']['name']; ?>[minute]" id="<?= $data['date']['name']; ?>[minute]" min="0" max="59" size="2" value="<?= $data['date']['minute']; ?>" pattern="[0-5]{1}[0-9]{1}"  disabled="disabled" />
					</p>
					<?php endif; ?>
				</div>

				<!-- Document Notes -->
				<?php if( array_key_exists( 'notes', $data ) ) : ?>

				<?php do_action( 'wpo_wcpdf_meta_box_before_document_notes', $document, $document->order ); ?>

				<!-- Read only -->
				<div class="read-only">
					<span><strong><?= $data['notes']['label']; ?></strong></span>
					<span class="wpo-wcpdf-edit-document-notes dashicons dashicons-edit" data-edit="notes"></span>
					<p><?= ( $data['notes']['value'] == strip_tags( $data['notes']['value'] ) ) ? nl2br( $data['notes']['value'] ) : $data['notes']['value']; ?></p>
				</div>
				<!-- Editable -->
				<div class="editable-notes">
					<p class="form-field form-field-wide">
						<label for="<?= $data['notes']['name']; ?>"><?= $data['notes']['label']; ?></label>
						<p><textarea name="<?= $data['notes']['name']; ?>" class="<?= $data['notes']['name']; ?>" cols="60" rows="5" disabled="disabled"><?= $data['notes']['value']; ?></textarea></p>
					</p>
				</div>

				<?php do_action( 'wpo_wcpdf_meta_box_after_document_notes', $document, $document->order ); ?>

				<?php endif; ?>
				<!-- / Document Notes -->

			</section>

			<!-- Save/Cancel buttons -->
			<section class="wcpdf-data-fields-section wpo-wcpdf-document-buttons">
				<div>
					<a class="button button-primary wpo-wcpdf-save-document" data-nonce="<?php echo wp_create_nonce( "wpo_wcpdf_save_document" ); ?>" data-action="save"><?php _e( 'Save changes', 'woocommerce-pdf-invoices-packing-slips' ); ?></a>
					<a class="button wpo-wcpdf-cancel"><?php _e( 'Cancel', 'woocommerce-pdf-invoices-packing-slips' ); ?></a>
				</div>
			</section>
			<!-- / Save/Cancel buttons -->
		</div>
		<?php
	}

	
	public function wcpdf_save_receipt_number_date($post_id) {
		$post_type = get_post_type( $post_id );
		if( $post_type == 'shop_order' ) {
			// bail if this is not an actual 'Save order' action
			if (!isset($_POST['action']) || $_POST['action'] != 'editpost') {
				return;
			}
			
			$order = wc_get_order( $post_id );
			$receipt = wcpdf_get_document( 'receipt', $order );
			if(!$receipt) return;
		
			if ( isset( $_POST['wcpdf_receipt_date'] ) ) {
				$date = $_POST['wcpdf_receipt_date'];
				$receipt_date = "{$date} 00:00:00";
				$receipt->set_date( $receipt_date );
			} elseif ( empty( $_POST['wcpdf_receipt_date'] ) && !empty( $_POST['_wcpdf_receipt_number'] ) ) {
				$receipt->set_date( current_time( 'timestamp', true ) );
			}

			if ( isset( $_POST['_wcpdf_receipt_number'] ) ) {
				$invoice_number = sanitize_text_field( $_POST['_wcpdf_receipt_number'] );
				$receipt->set_number( $invoice_number );
			}

			$receipt->save();
		}
	}
	
	public function wcpdf_meta_box_actions( $meta_actions, $post_id ) {
		$order = wc_get_order($post_id);
		$invoicetype = wcpdf_it_get_billing_invoice_type($order);
		if($invoicetype == "receipt" || (!$invoicetype && WCPDF_IT()->what_if_no_invoicetype === "receipt")) {
			unset($meta_actions['invoice']);
		}
		if($invoicetype == "invoice" || (!$invoicetype && WCPDF_IT()->what_if_no_invoicetype === "invoice")) {
			unset($meta_actions['receipt']);
		}
		if($invoicetype == "noinvoice" || (!$invoicetype && WCPDF_IT()->what_if_no_invoicetype === "noinvoice")) {
			unset($meta_actions['invoice']);
			unset($meta_actions['receipt']);
		}
		return $meta_actions;
	}
	
	public function wcpdf_listing_actions( $listing_actions, $order) {
		$invoicetype = wcpdf_it_get_billing_invoice_type($order);
		if($invoicetype == "receipt" || (!$invoicetype && WCPDF_IT()->what_if_no_invoicetype === "receipt")) {
			unset($listing_actions['invoice']);
		}
		if($invoicetype == "invoice" || (!$invoicetype && WCPDF_IT()->what_if_no_invoicetype === "invoice")) {
			unset($listing_actions['receipt']);
		}
		if($invoicetype == "noinvoice" || (!$invoicetype && WCPDF_IT()->what_if_no_invoicetype === "noinvoice")) {
			unset($listing_actions['invoice']);
			unset($listing_actions['receipt']);
		}
		return $listing_actions;
	}
	
	public function wcpdf_bulk_actions( $bulk_actions) {
		$bulk_actions['receipt'] = __( 'PDF Receipts', WCPDF_IT_DOMAIN );
		//unset($bulk_actions['receipt']);
		return $bulk_actions;
	}

	public function wcpdf_custom_email_condition($attach, $order, $email_id, $document_type) {
		$invoicetype = wcpdf_it_get_billing_invoice_type($order);
		if(($invoicetype === "invoice" && $document_type === "receipt") || 
			 ($invoicetype === "receipt" && $document_type === "invoice") )
			return false;
		if(!$invoicetype &&
			( (WCPDF_IT()->what_if_no_invoicetype === "noinvoice") ||
				(WCPDF_IT()->what_if_no_invoicetype === "receipt" && $document_type === "invoice") ||
				(WCPDF_IT()->what_if_no_invoicetype === "invoice" && $document_type === "receipt")
			) ) return(false);
		return $attach;
	}

	public function wcpdf_my_account( $actions, $order ) {
		$invoicetype = wcpdf_it_get_billing_invoice_type($order);
		if(!$invoicetype && WCPDF_IT()->what_if_no_invoicetype !== "invoice") {
			unset($actions['invoice']);
		}
		if ($invoicetype == 'receipt' || (!$invoicetype && WCPDF_IT()->what_if_no_invoicetype === "receipt") ) {
			$receipt = wcpdf_get_document( 'receipt', $order );
			if ( $receipt && $receipt->is_enabled() ) {
				$order_id = wcpdf_it_get_order_id($order);
				$wpo_wcpdf_general_settings = get_option('wpo_wcpdf_general_settings');
				$pdf_url = wp_nonce_url( admin_url( 'admin-ajax.php?action=generate_wpo_wcpdf&document_type=receipt&order_ids=' . $order_id . '&my-account'), 'generate_wpo_wcpdf' );
				$button_setting = $receipt->get_setting('my_account_buttons', 'available');
				if($button_setting) {
					switch ($button_setting) {
						case 'available':
							$receipt_allowed = $receipt->exists();
							break;
						case 'always':
							$receipt_allowed = true;
							break;
						case 'never':
							$receipt_allowed = false;
							break;
						case 'custom':
							$allowed_statuses = $button_setting = $receipt->get_setting('my_account_restrict', array());
							if ( !empty( $allowed_statuses ) && in_array( $order->get_status(), array_keys( $allowed_statuses ) ) ) {
								$receipt_allowed = true;
							} else {
								$receipt_allowed = false;
							}
							break;
					}
				} elseif ( !$invoicetype && WCPDF_IT()->what_if_no_invoicetype === "noinvoice") {
					$receipt_allowed = false;
				} else {
					$receipt_allowed = $receipt->exists();
				}
				if (!empty($receipt_allowed)) {
					$actions['receipt'] = array(
						'url'  => $pdf_url,
						'name' => __( 'Download Receipt (PDF)', WCPDF_IT_DOMAIN )
					);				
				}
				unset($actions['invoice']);
			}
/*
			$actions['receipt'] = array(
				'url'  => wp_nonce_url( admin_url( 'admin-ajax.php?action=generate_wpo_wcpdf&document_type=receipt&order_ids=' . $order->id . '&my-account' ), 'generate_wpo_wcpdf' ),
				'name' => __( 'Download Receipt (PDF)', WCPDF_IT_DOMAIN )
			);				
*/
		}
		return $actions;
	}

	public function wcpdf_template_files( $file_path, $type, $order ) {
		if($type !== "receipt" || strpos($file_path, "receipt") === false) return $file_path;
		
		$file = "receipt.php";
		$path = WPO_WCPDF()->settings->get_template_path( $file );
		$file_path = "{$path}/{$file}";
		if(file_exists( $file_path )) return($file_path);
		$file_path = "{$path}/receipt.php";
		if(file_exists( $file_path )) return($file_path);

		// if WCPDF premium template is selected, use that
		if ( defined('WPO_WCPDF_TEMPLATES_VERSION') && version_compare( WPO_WCPDF_TEMPLATES_VERSION, '2.4', '>' ) ) {
			// use setting, fallback to basename
			$template_name = !empty(WCPDF_IT()->options['template_name']) ? WCPDF_IT()->options['template_name'] : basename( WPO_WCPDF()->settings->get_template_path() );

			if ($template_name != 'Simple') {
				$file_path = WooCommerce_Italian_add_on::$plugin_path . "templates/pdf/{$template_name}/receipt.php";
				if(file_exists( $file_path )) return($file_path);
			}
		}

		// use default (Simple)
		$file_path = WooCommerce_Italian_add_on::$plugin_path . 'templates/pdf/Simple/receipt.php';
		return $file_path;
	}

	public function add_receipt_number_column( $columns ) {
		$receipt = wcpdf_get_document( 'receipt', null );
		$receipt_settings = $receipt->get_settings();
		if ( !isset( $receipt_settings['receipt_number_column'] ) ) {
			return $columns;
		}

		$new_columns = array_slice($columns, 0, 4, true) +
			array( 
			'pdf_receipt_number' => __( 'Receipt Number', WCPDF_IT_DOMAIN ),
			'pdf_receipt_date' => __( 'Receipt Date', WCPDF_IT_DOMAIN ) ) +
			array_slice($columns, 4, count($columns) - 1, true) ;
		return $new_columns;
	}

	public function receipt_number_column_data( $column, $order  ) {
		$invoicetype = wcpdf_it_get_billing_invoice_type($order);
		if($invoicetype === "receipt") {
			$receipt = wcpdf_get_document( 'receipt', $order );
			switch($column) {
				case 'pdf_receipt_number':
					$receipt_number = ! empty( $receipt ) && ! empty( $receipt->get_number() ) ? $receipt->get_number() : '';
					echo $receipt_number;
					break;
				case 'pdf_receipt_date':
					$receipt_date = ! empty( $receipt ) && ! empty( $receipt->get_date() ) ? $receipt->get_date()->date_i18n( wcpdf_date_format( $receipt, 'receipt_date_column' ) ) : '';
					echo $receipt_date;
					break;
			}
		}
	}

	public function receipt_number_column_sortable( $columns ) {
		$columns['pdf_receipt_number'] = 'pdf_receipt_number';
		$columns['pdf_receipt_date'] = 'pdf_receipt_date';
		return $columns;
	}

	public function sort_by_receipt_number( $query ) {
		if( ! is_admin() ) return;
		$orderby = $query->get( 'orderby');
		if( 'pdf_receipt_number' == $orderby ) {
			$query->set('meta_key','_wcpdf_receipt_number');
			$query->set('orderby','meta_value_num');
		}
	}

}
endif;
$wcpdf_it_add_on = new wcpdf_Integration_Italian_add_on();
?>