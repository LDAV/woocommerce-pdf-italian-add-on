<?php
if(defined("WP_PLUGIN_DIR")) {
	if(file_exists(WP_PLUGIN_DIR . "/woocommerce-pdf-ips-templates/templates/Business/receipt.php")){
		include WP_PLUGIN_DIR . "/woocommerce-pdf-ips-templates/templates/Business/receipt.php";
	} elseif(file_exists(WP_PLUGIN_DIR . "/woocommerce-pdf-invoices-packing-slips/templates/Business/invoice.php")){
		include WP_PLUGIN_DIR . "/woocommerce-pdf-invoices-packing-slips/templates/Business/invoice.php";
	}
}
