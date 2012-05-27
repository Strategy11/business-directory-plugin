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
			$('form#wpbdp-formfield-form #field-data-options').text('');
		}
	}).change();

	$('form#wpbdp-fee-form input[name="_days"]').change(function(){
		var value = $(this).val();

		// alert(value);

		if (value == 0) {
			$('form input#wpbdp-fee-form-days-n').attr('disabled', true);
			$('form input[name="fee[days]"]').val('0');
		} else {
			$('form input#wpbdp-fee-form-days-n').removeAttr('disabled');
			$('form input[name="fee[days]"]').val($('form input#wpbdp-fee-form-days-n').val());
			$('form input#wpbdp-fee-form-days-n').focus();
		}

		return true;
	});

	// $('form#wpbdp-fee-form input[name="fee[days]"]').keypress(function(e){
	// 	$('form input#wpbdp-fee-form-days-0').removeAttr('checked');		
	// 	$('form input#wpbdp-fee-form-days').attr('checked', true);
	// 	// $('form#wpbdp-fee-form input[name="_days"]').change();
	// });

	$('form#wpbdp-fee-form').submit(function(){
		// alert($('form#wpbdp-fee-form input[name="fee[days]"]').val());
		// return false;
		$('form input[name="fee[days]"]').removeAttr('disabled');
		return true;
	});

});