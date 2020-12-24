<?php
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

		add_action( 'add_meta_boxes_shop_order', array( $this, 'wcpdf_add_meta_boxes' ), 20, 1 );
		add_action( 'save_post', array( $this,'wcpdf_save_receipt_number_date' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wcpdf_admin_enqueue_scripts' ) );
		add_filter( 'wpo_wcpdf_document_classes', array( $this, 'wcpdf_register_documents' ), 10, 1 );
		add_action( 'wpo_wcpdf_after_order_details', array( $this, 'wcpdf_after_order_details' ), 10, 2 );

		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_receipt_number_column' ), 999 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'receipt_number_column_data' ), 2 );
		add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'receipt_number_column_sortable' ) );
		add_filter( 'pre_get_posts', array( $this, 'sort_by_receipt_number' ) );

	}

	public function wcpdf_register_documents( $documents ) {
		$documents['WPO_WCPDF_Receipt_Document'] = include( 'class-wcpdf-receipt-document.php' );
		return $documents;
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
	
	public function wcpdf_add_meta_boxes( $post ) {
		$order_id = $post->ID;
		$order = wc_get_order($order_id);
		$invoicetype = wcpdf_it_get_billing_invoice_type($order);
		if($invoicetype === "receipt"  || (!$invoicetype && WCPDF_IT()->what_if_no_invoicetype === "receipt")) {
			remove_meta_box( 'wpo_wcpdf-data-input-box', 'shop_order', 'normal' );
			add_meta_box(
				'wcpdf-data-input-box-receipt',
				__( 'PDF Receipt data', WCPDF_IT_DOMAIN ),
				array( $this, 'wcpdf_data_input_box_content' ),
				'shop_order',
				'normal',
				'default'
			);
		}
	}

	public function wcpdf_data_input_box_content ( $post ) {
		$order = wc_get_order( $post->ID );
		if ( $receipt = wcpdf_get_document( 'receipt', $order )) {
			$receipt_number = $receipt->get_number();
			$receipt_date = $receipt->get_date();

			do_action( 'wpo_wcpdf_meta_box_start', $post->ID );

		?>
		<div class="wcpdf-data-fields" data-document="receipt" data-order_id="<?php echo $post->ID; ?>">
			<h4><?php _e( 'Receipt', WCPDF_IT_DOMAIN ) ?><?php if ($receipt->exists()) : ?><span id="edit-receipt-date-number" class="dashicons dashicons-edit"></span><span class="wpo-wcpdf-delete-document dashicons dashicons-trash" data-nonce="<?php echo wp_create_nonce( "wpo_wcpdf_delete_document" ); ?>"></span><?php endif; ?></h4>

			<!-- Read only -->
			<div class="read-only">
				<?php if ($receipt->exists()) : ?>
				<div class="receipt-number">
					<p class="form-field _wcpdf_receipt_number_field ">	
						<p>
							<span><strong><?php _e( 'Receipt Number', WCPDF_IT_DOMAIN ); ?>:</strong></span>
							<span><?php if (!empty($receipt_number)) echo $receipt_number->formatted_number ?></span>
						</p>
					</p>
				</div>

				<div class="receipt-date">
					<p class="form-field form-field-wide">
						<p>
							<span><strong><?php _e( 'Receipt Date:', WCPDF_IT_DOMAIN ); ?></strong></span>
							<span><?php if (!empty($receipt_date)) echo $receipt_date->date_i18n( wc_date_format() ); ?></span>
						</p>
					</p>
				</div>
				<?php else : ?>
				<span id="set-receipt-date-number" class="button"><?php _e( 'Set receipt number & date', WCPDF_IT_DOMAIN ) ?></span>
				<?php endif; ?>
			</div>

			<!-- Editable -->
			<div class="editable">
				<p class="form-field _wcpdf_receipt_number_field ">
					<label for="_wcpdf_receipt_number"><?php _e( 'Receipt Number (unformatted!)', WCPDF_IT_DOMAIN ); ?>:</label>
					<?php if ( $receipt->exists() && !empty($receipt_number) ) : ?>
					<input type="text" class="short" style="" name="_wcpdf_receipt_number" id="_wcpdf_receipt_number" value="<?php echo $receipt_number->number ?>">
					<?php else : ?>
					<input type="text" class="short" style="" name="_wcpdf_receipt_number" id="_wcpdf_receipt_number" value="" disabled="disabled" >
					<?php endif; ?>
				</p>
				<p class="form-field form-field-wide">
					<label for="wcpdf_receipt_date"><?php _e( 'Receipt Date:', WCPDF_IT_DOMAIN ); ?></label>
					<?php if ( $receipt->exists() && !empty($receipt_date) ) : ?>
					<input type="text" class="date-picker-field" name="wcpdf_receipt_date" id="wcpdf_receipt_date" maxlength="10" value="<?php echo $receipt_date->date_i18n( 'Y-m-d' ); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
					<?php else : ?>
					<input type="text" class="date-picker-field" name="wcpdf_receipt_date" id="wcpdf_receipt_date" maxlength="10" disabled="disabled" value="" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
					<?php endif; ?>
				</p>
			</div>
		</div>
		<?php
			do_action( 'wpo_wcpdf_meta_box_end', $post->ID );
		}
	}

	public function wcpdf_save_receipt_number_date($post_id) {
		$post_type = get_post_type( $post_id );
		if( $post_type == 'shop_order' ) {
			// bail if this is not an actual 'Save order' action
			if (!isset($_POST['action']) || $_POST['action'] != 'editpost') {
				return;
			}
			
			$order = wc_get_order( $post_id );
			if ( $receipt = wcpdf_get_document( 'receipt', $order )) {
				if ( isset( $_POST['wcpdf_receipt_date'] ) ) {
					$date = $_POST['wcpdf_receipt_date'];
					$receipt_date = "{$date} 00:00:00";
					$receipt->set_date( $receipt_date );
				} elseif ( empty( $_POST['wcpdf_receipt_date'] ) && !empty( $_POST['_wcpdf_receipt_number'] ) ) {
					$receipt->set_date( current_time( 'timestamp', true ) );
				}

				if ( isset( $_POST['_wcpdf_receipt_number'] ) ) {
					$receipt->set_number( $_POST['_wcpdf_receipt_number'] );
				}

				$receipt->save();
			}
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
		return $bulk_actions;
	}
	
/*
	public function wcpdf_process_template_order($template_type, $order_id) {
		if($template_type == 'invoice') {
			$order = wc_get_order($order_id);
			$invoicetype = wcpdf_it_get_billing_invoice_type($order);
			$template_type = $invoicetype ? $invoicetype : "invoice";
		}
		return $template_type;
	}
*/
	
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

/*	
	public function my_account_pdf_link( $actions, $order ) {
		$receipt = wcpdf_get_document( 'receipt', $order );
		if ( $receipt && $receipt->is_enabled() ) {
			$pdf_url = wp_nonce_url( admin_url( 'admin-ajax.php?action=generate_wpo_wcpdf&document_type=receipt&order_ids=' . $order->order_id . '&my-account'), 'generate_wpo_wcpdf' );

			// check my account button settings
			$button_setting = $receipt->get_setting('my_account_buttons', 'available');
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

			// Check if receipt has been created already or if status allows download (filter your own array of allowed statuses)
			if ( $receipt_allowed || in_array($order->get_status(), apply_filters( 'wpo_wcpdf_myaccount_allowed_order_statuses', array() ) ) ) {
				$button_text = __( 'Download receipt (PDF)', 'woocommerce-pdf-italian-add-on' );
				$actions['receipt'] = array(
					'url'  => $pdf_url,
					'name' => $button_text
				);
			}
		}

		return apply_filters( 'wpo_wcpdf_myaccount_actions', $actions, $order );
	}
*/

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
				if ($receipt_allowed) {
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
		
		$file = "receipt2.php";
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
		$file_path = WooCommerce_Italian_add_on::$plugin_path . 'templates/pdf/Simple/receipt2.php';
		if(file_exists( $file_path )) return($file_path);
		$file_path = WooCommerce_Italian_add_on::$plugin_path . 'templates/pdf/Simple/receipt.php';
		return $file_path;
	}
/*
	public function wcpdf_custom_template_files( $template, $template_type ) {
		// bail out if file already exists in default or custom path!
		if( file_exists( $template ) ){
			return $template;
		}

		$custom_template_file = plugin_dir_path( __FILE__ ) . 'templates/' . $template_type . '.php';

		if( file_exists( $custom_template_file ) ){
			// default to bundled Simple template
			return $custom_template_file;
		} else {
			// unknown document type! This will inevitably throw an error unless there's another filter after this one.
			return $template;
		}
	}
*/

/*
	public function wcpdf_attach_receipt( $documents ) {
		global $order;
		$invoicetype = wcpdf_it_get_billing_invoice_type($order);
		if ( $invoicetype == 'receipt') {
			$documents['receipt'] = $documents['invoice'];
			unset($documents['invoice']);
		}
		return $documents;
	}
*/

	function wcpdf_after_order_details($type, $order) {
		if($type === "invoice" || $type === "receipt") {
			$country = $order->get_billing_country();
			$taxes = $order->get_total_tax();
			//controlla solo se l'IVA è 0 e non se vat_exempt_if_UE_business = true perché potrebbero essere cambiate le condizioni di esenzione.
			if($taxes == 0) {
				if($country == WCPDF_IT()->default_country) {
					if(!empty(WCPDF_IT()->options["txtVatExempt"])){
						echo '<div class"wcpdf_IT_txtVatExempt wcpdf_IT_VatExempt"><strong>' . WCPDF_IT()->options["txtVatExempt"] . '</strong></div>';
					}
				} else {
					$is_UE = in_array($country, WCPDF_IT()->eu_vat_countries);
					$azienda = (wcpdf_it_get_billing_customer_type($order) == "business" || wcpdf_it_get_billing_invoice_type($order) == "invoice");
					if($is_UE) {
						echo '<div class"wcpdf_IT_txtVatExempt wcpdf_IT_VatExemptUE"><strong>' . WCPDF_IT()->options["txtVatExemptUE"] . '</strong></div>';
					} else {
						if($azienda || !empty(WCPDF_IT()->options["txtVatExempt"])) {
							echo '<div class"wcpdf_IT_txtVatExempt wcpdf_IT_VatExemptExtraUE"><strong>' . WCPDF_IT()->options["txtVatExemptExtraUE"] . '</strong></div>';
						}
					}
				}
			}
		}
	}

	public function add_receipt_number_column( $columns ) {
		$receipt = wcpdf_get_document( 'receipt', null );
		$receipt_settings = $receipt->get_settings();
		if ( !isset( $receipt_settings['receipt_number_column'] ) ) {
			return $columns;
		}

		$new_columns = array_slice($columns, 0, 3, true) +
			array( 'pdf_receipt_number' => __( 'Receipt Number', WCPDF_IT_DOMAIN ) ) +
			array_slice($columns, 3, count($columns) - 1, true) ;
		return $new_columns;
	}

	public function receipt_number_column_data( $column ) {
		global $post, $the_order;

		$invoicetype = wcpdf_it_get_billing_invoice_type($the_order);
		if($invoicetype === "receipt") {
			$receipt = wcpdf_get_document( 'receipt', $the_order );
			if ( $column == 'pdf_receipt_number' ) {
				if ( $receipt ) {
					echo $receipt->get_number();
				}
			}
		}
	}

	public function receipt_number_column_sortable( $columns ) {
		$columns['pdf_receipt_number'] = 'pdf_receipt_number';
		return $columns;
	}

	public function sort_by_receipt_number( $query ) {
		if( ! is_admin() )
			return;
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