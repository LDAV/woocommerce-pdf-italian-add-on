jQuery(document).ready(function($) {
	$('#wcpdf-data-input-box-receipt').insertAfter('#woocommerce-order-data');
	$( "#set-receipt-date-number, #edit-receipt-date-number" ).click(function() {
		$form = $(this).closest('.wcpdf-data-fields');
		$form.find(".read-only").hide();
		$form.find(".editable").show();
		$form.find(':input').prop('disabled', false);
	});
});

