		// echo '<script type="text/javascript">jQuery("#wpbdp-debugging-placeholder").replaceWith(jQuery("#wpbdp-debugging").show());</script>';

jQuery(function($) {

	$('#wpbody .wrap').before('<div id="wpbdp-debugging-placeholder"></div>');
	$('#wpbdp-debugging-placeholder').replaceWith($('#wpbdp-debugging'));

});