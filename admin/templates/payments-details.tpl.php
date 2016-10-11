<?php
echo wpbdp_admin_header(
    str_replace( '<id>',
                 $payment->get_id(),
                 _x( 'Payment #<id>', 'admin payments', 'WPBDM' ) ) 
);
?>
<?php wpbdp_admin_notices(); ?>

<?php print_r( $payment ); ?>

<?php echo wpbdp_admin_footer(); ?>
