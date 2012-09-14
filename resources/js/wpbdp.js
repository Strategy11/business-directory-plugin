if (typeof(window.WPBDP) == 'undefined') {
    window.WPBDP = {};
}

jQuery(document).ready(function($){

    $('.listing-actions input.delete-listing').click(function(e){
        var message = $(this).attr('data-confirmation-message');

        if (confirm(message)) {
            return true;
        }
        
        return false;
    });

});