jQuery(document).ready(function($){

	// form fields
	$('form#wpbdp-formfield-form select#field-association').change(function(){
		$('form#wpbdp-formfield-form select#field-type').change();
	});

	$('form#wpbdp-formfield-form select#field-type').change(function(){
		var selected_type = $('option:selected', $(this)).val();
		var association = $('form#wpbdp-formfield-form select#field-association option:selected').val();

		if ((selected_type == 'select' ||
			selected_type == 'radio' ||
			selected_type == 'multiselect' ||
			selected_type == 'checkbox') && association != 'category') {
			$('form#wpbdp-formfield-form #field-data-options').parents('tr').show();
		} else {
			$('form#wpbdp-formfield-form #field-data-options').parents('tr').hide();			
		}
	}).change();



});