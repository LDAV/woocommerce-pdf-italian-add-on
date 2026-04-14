<?php
if(defined("WP_PLUGIN_DIR")) {
	if(file_exists(WP_PLUGIN_DIR . "/woocommerce-pdf-ips-templates/templates/Modern/receipt.php")){
		include WP_PLUGIN_DIR . "/woocommerce-pdf-ips-templates/templates/Modern/receipt.php";
	} elseif(file_exists(WP_PLUGIN_DIR . "/woocommerce-pdf-invoices-packing-slips/templates/Modern/invoice.php")){
		include WP_PLUGIN_DIR . "/woocommerce-pdf-invoices-packing-slips/templates/Modern/invoice.php";
	}
}
