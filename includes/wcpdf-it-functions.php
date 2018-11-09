<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wcpdf_it_get_billing_invoice_type($order) {
	$invoicetype = "invoice";
	if($order) {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
			$invoicetype = get_post_meta($order->id,"_billing_invoice_type",true);
		} else {
			$invoicetype = $order->get_meta("_billing_invoice_type",true);
		}
	}
	return($invoicetype);
}

function wcpdf_it_get_billing_customer_type($order) {
	$customertype = "business";
	if($order) {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
			$customertype = get_post_meta($order->id,"_billing_customer_type",true);
		} else {
			$customertype = $order->get_meta("_billing_customer_type",true);
		}
	}
	return($customertype);
}

function wcpdf_it_get_billing_cf($order) {
	$cf = "";
	if($order) {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
			$cf = get_post_meta($order->id,"_billing_cf",true);
		} else {
			$cf = $order->get_meta("_billing_cf",true);
		}
	}
	return($cf);
}

function wcpdf_it_get_billing_cf2($order) {
	$cf = "";
	if($order) {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
			$cf = get_post_meta($order->id,"_billing_cf2",true);
		} else {
			$cf = $order->get_meta("_billing_cf2",true);
		}
	}
	return($cf);
}

function wcpdf_it_get_billing_PEC($order) {
	$PEC = "";
	if($order) {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
			$PEC = get_post_meta($order->id,"_billing_PEC",true);
		} else {
			$PEC = $order->get_meta("_billing_PEC",true);
		}
	}
	return($PEC);
}

function wcpdf_it_get_order_id($order) {
	if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '<' ) ) {
		$order_id = $order->id;
	} else {
		$order_id = $order->get_id();
	}
	return($order_id);
}
