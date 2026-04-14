<?php 
if(defined("WP_PLUGIN_DIR")) {
	if(file_exists(WP_PLUGIN_DIR . "/woocommerce-pdf-ips-templates/templates/Simple Premium/receipt.php")){
		include WP_PLUGIN_DIR . "/woocommerce-pdf-ips-templates/templates/Simple Premium/receipt.php";
	} elseif(file_exists(WP_PLUGIN_DIR . "/woocommerce-pdf-invoices-packing-slips/templates/Simple Premium/invoice.php")){
		include WP_PLUGIN_DIR . "/woocommerce-pdf-invoices-packing-slips/templates/Simple Premium/invoice.php";
	}
}