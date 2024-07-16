<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wcpdf_it_get_order($post_or_order_object){
	$order = ( $post_or_order_object instanceof \WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
	if (!is_object( $order ) && is_numeric( $order ) ) $order = wc_get_order( absint( $order ) );
	return($order);
}

function wcpdf_it_get_billing_invoice_type($order) {
	$order = wcpdf_it_get_order($order);
	$invoicetype = "invoice";
	if($order) {
		$invoicetype = $order->get_meta("_billing_invoice_type",true);
	}
	return($invoicetype);
}

function wcpdf_it_get_billing_customer_type($order) {
	$order = wcpdf_it_get_order($order);
	$customertype = "business";
	if($order) {
		$customertype = $order->get_meta("_billing_customer_type",true);
	}
	return($customertype);
}

function wcpdf_it_get_billing_cf($order) {
	$order = wcpdf_it_get_order($order);
	$cf = "";
	if($order) {
		$cf = $order->get_meta("_billing_cf",true);
	}
	return($cf);
}

function wcpdf_it_get_billing_cf2($order) {
	$order = wcpdf_it_get_order($order);
	$cf = "";
	if($order) {
		$cf = $order->get_meta("_billing_cf2",true);
	}
	return($cf);
}

function wcpdf_it_get_billing_PEC($order) {
	$order = wcpdf_it_get_order($order);
	$PEC = "";
	if($order) {
		$PEC = $order->get_meta("_billing_PEC",true);
	}
	return($PEC);
}

function wcpdf_it_get_order_id($order) {
	$order = wcpdf_it_get_order($order);
	$order_id = $order->get_id();
	return($order_id);
}

function wcpdf_it_get_order_meta( $order, $key = '', $single = true, $context = 'edit' ) {
	$order = wcpdf_it_get_order($order);
	$order_id = $order->get_id();
	return $order->get_meta( $key, $single );
}
