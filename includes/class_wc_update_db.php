<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

if ( ! class_exists( 'WooCommerce_Italian_add_on_Update' ) ) {
	class WooCommerce_Italian_add_on_Update {
		public $dbv;

		public function __construct() {
			$this->dbv = get_option('wcpdf_IT_db_version');
			add_action( 'wp_ajax_wcpdf_IT_update_db', array ( $this, 'update_db' ) );
		}

		public function test() {
			if(!$this->dbv || version_compare($this->dbv, "0.6.0", "<") ){
				//add_action( 'admin_notices', array ( $this, 'update_notice' ) );
				$this->update_db();
			}
		}

		public function update_db(){
			if($this->dbv && version_compare($this->dbv, "0.6.0", ">=") ) return;
			$args = array(
				'limit' => 99999,
				'type' => 'shop_order'
			);
			$orders = wc_get_orders( $args );
			foreach($orders as $order) {
				$order_id = $order->get_ID();
				$invoicetype = $order->get_meta("_billing_invoice_type",true);
				$customertype = $order->get_meta("_billing_customer_type",true);
				$cf = $order->get_meta("_billing_cf",true);
				if($cf) {
					$customertype = $customertype ? $customertype : ((strlen($cf) === 16) ? "personal" : "business");
					update_post_meta( $order_id, '_billing_customer_type', $customertype ); 
				}
			}
			update_option('wcpdf_IT_db_version', "0.6.0");
			//die("OK");
		}
		
		public function update_notice() {
			$s = admin_url('admin-ajax.php');
			$nonce = wp_create_nonce( "wcpdf_IT_update_db" );
	?>
	<div class="updated fade wcpdf_IT_update woocommerce-message">
		<p><?php esc_js(_e( '<strong>WooCommerce PDF Italian Add-on Data Update Required</strong> - We just need to update your install to the latest version', WCPDF_IT_DOMAIN )); ?></p>
		<p class="submit"><button type="button" class="wcpdf_IT-update-now button-primary"><?php esc_js(_e( 'Run the updater', 'woocommerce' )); ?></button></p>
	</div>
	<script type="text/javascript">
		jQuery( '.wcpdf_IT-update-now' ).click( 'click', function() {
			if(!window.confirm( '<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'woocommerce' ) ); ?>' )){return(false)};
			jQuery.post('<?php echo $s; ?>', {action:"wcpdf_IT_update_db", "_wpnonce" : '<?php echo $nonce?>'}, function(response) {
				jQuery(".wcpdf_IT_update").html('<p>' + "<?php echo esc_js(_e("OK. The data update was performed correctly", WCPDF_IT_DOMAIN)) ?>" + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">' + "<?php echo esc_js(_e("Hide this notice", WCPDF_IT_DOMAIN))?>" + '.</span></button>');
				jQuery(".wcpdf_IT_update .notice-dismiss").click(function(){
					jQuery(".wcpdf_IT_update").fadeOut();
				});
			});
			return(false);
		});
	</script>
	<?php
		}

	}
}
