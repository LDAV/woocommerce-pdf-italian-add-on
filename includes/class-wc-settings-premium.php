<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooCommerce_Italian_add_on_Settings_premium' ) ) {

	class WooCommerce_Italian_add_on_Settings_premium {
	
		public function __construct() {
			add_action( 'wcpdf_IT_premium_versions_output', array( $this, 'output' ) );
		}

		public function output( $section ) {
			$arrContextOptions=array(
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
				),
			);
			try {
				$s = file_get_contents("https://ldav.it/premium.php", false, stream_context_create($arrContextOptions));
				preg_match("/<section\sitemprop=\"articleBody\"[^>]*>(.*?)<\\/section>/si", $s, $m);
				$content = isset($m[0]) ? $m[0] : $s;;
			} catch (Exception $e) {
				$content = "";
			}
?>
<h3><?php esc_attr_e( 'Premium Versions', WCPDF_IT_DOMAIN ) ?></h3>
<div class="'wcpdf_IT_result"><?php echo wp_kses($content, "post") ?></div>
<?php
		
		}
	} // end class

} // end class_exists

return new WooCommerce_Italian_add_on_Settings_premium();
