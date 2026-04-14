<?php 
if(defined("WP_PLUGIN_DIR")) {
	if(file_exists(WP_PLUGIN_DIR . "/woocommerce-pdf-ips-pro/templates/Simple/receipt.php")){
		include WP_PLUGIN_DIR . "/woocommerce-pdf-ips-pro/templates/Simple/receipt.php";
	} elseif(file_exists(WP_PLUGIN_DIR . "/woocommerce-pdf-invoices-packing-slips/templates/Simple/invoice.php")){
		include WP_PLUGIN_DIR . "/woocommerce-pdf-invoices-packing-slips/templates/Simple/invoice.php";
	}
}