var wcpdf_IT;
function wcpdf_IT_check_required() {
	if(wcpdf_IT.invoice_required || jQuery("#billing_country").length === 0) {return(false);}
	var wcpdf_IT_country_selected = jQuery("#billing_country").val();
	jQuery("#billing_cf_field").removeClass("validate-required woocommerce-invalid woocommerce-invalid-required-field");
	jQuery("#billing_cf_field label abbr").remove();
	if(jQuery("#billing_invoice_type").val() === "invoice") {
		if(jQuery.inArray(wcpdf_IT_country_selected, wcpdf_IT.eu_vat_countries) >= 0) {
			jQuery("#billing_cf_field").addClass("validate-required");
			if(wcpdf_IT.has_error) {jQuery("#billing_cf_field").addClass("woocommerce-invalid woocommerce-invalid-required-field");}
			jQuery("#billing_cf_field label").append(wcpdf_IT.required_text);
		} else {
			if(wcpdf_IT.invoice_required_non_UE) {
				jQuery("#billing_cf_field").addClass("validate-required");
				if(wcpdf_IT.has_error) {jQuery("#billing_cf_field").addClass("woocommerce-invalid woocommerce-invalid-required-field");}
				jQuery("#billing_cf_field label").append(wcpdf_IT.required_text);
			}
		}
	}
}
function wcpdf_IT_hide_outside_UE() {
	if(jQuery("#billing_country").length === 0) {return(false);}
	var wcpdf_IT_country_selected = jQuery("#billing_country").val();
	if(jQuery.inArray(wcpdf_IT_country_selected, wcpdf_IT.eu_vat_countries) >= 0) {
		var odata = jQuery("#billing_cf_field").data("o_data");
		if(odata) {
			jQuery("#billing_cf_field").addClass(odata.class);
			jQuery("#billing_cf").val(odata.value);
			jQuery("#billing_invoice_type").val(odata.option);
			jQuery("#billing_cf_field").removeData("o_data");
		}
		jQuery("#billing_cf_field").show();
		jQuery("#billing_invoice_type_field").show();
		wcpdf_IT_check_required();
	} else {
		jQuery("#billing_cf_field").data("o_data", {"class" : jQuery("#billing_cf_field").attr("class"), "value" : jQuery("#billing_cf").val(), "option" : jQuery("#billing_invoice_type").val()});
		jQuery("#billing_cf").val("");
		jQuery("#billing_cf_field").removeClass("validate-required woocommerce-invalid woocommerce-invalid-required-field");
		jQuery("#billing_cf_field").hide();
		jQuery("#billing_invoice_type").val("receipt");
		jQuery("#billing_invoice_type_field").hide();
	}
}
jQuery(function($){
	if(typeof(jQuery.fn.select2) === "function"){$("#billing_invoice_type").select2({minimumResultsForSearch: Infinity});}
	if(wcpdf_IT.hide_outside_UE) {
		$("#billing_country").change(wcpdf_IT_hide_outside_UE);
		$("#billing_invoice_type").change(wcpdf_IT_hide_outside_UE);
		wcpdf_IT_hide_outside_UE();
	} else {
		$("#billing_country").change(wcpdf_IT_check_required);
		$("#billing_invoice_type").change(wcpdf_IT_check_required);
		wcpdf_IT_check_required();
	}
});
