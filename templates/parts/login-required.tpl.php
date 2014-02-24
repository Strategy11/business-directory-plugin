<?php echo wpbdp_render_msg(_x("You are not currently logged in. Please login or register first. When registering, you will receive an activation email. Be sure to check your spam if you don't see it in your email within 60 minutes.", 'templates', 'WPBDM')); ?>

<p></p>

<h2><?php _ex('Login', 'templates', 'WPBDM'); ?></h2>
<?php wp_login_form(); ?>

<?php
$registration_url = wpbdp_get_option( 'registration-url', '' );
wpbdp_debug( $registration_url );
if ( empty( $registration_url ) && function_exists( 'wp_registration_url' ) ) {
    $registration_url = wp_registration_url();
} else if ( empty( $registration_url ) ) {
    $registration_url = site_url( 'wp-login.php?action=register', 'login' );
}
?>

<?php if (get_option('users_can_register')): ?>
<a href="<?php echo $registration_url; ?>"><?php _ex( 'Not yet registered?', 'templates', 'WPBDM' ); ?></a>
<?php endif; ?>
