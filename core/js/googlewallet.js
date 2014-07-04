
jQuery(function($) {
    $('#googlewallet-buy').click(function(e) {
        e.preventDefault();

        var $context = $(this).parents('form');

        google.payments.inapp.buy({
            "jwt": $context.find('input[name="jwt"]').val(),

            "success": function(result) {
                $context.find('input[name="order_id"]').val(result.response.orderId);
                $context.find('input[name="jwt"]').val(result.jwt);
                $context.submit();
            },

            "failure": function(result) {
                $context.find('input[name="success"]').val('0');
                $context.find('input[name="error"]').val(result.response.errorType);
                $context.submit();
            }
        });
    });
});
