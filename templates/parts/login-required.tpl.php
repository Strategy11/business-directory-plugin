<?php
$show_message = isset( $show_message ) ? $show_message : true;
?>
<?php if ( $show_message ): ?>
<?php echo wpbdp_render_msg(_x("You are not currently logged in. Please login or register first. When registering, you will receive an activation email. Be sure to check your spam if you don't see it in your email within 60 minutes.", 'templates', 'WPBDM')); ?>
<?php endif; ?>

<p></p>

<h2><?php _ex('Login', 'templates', 'WPBDM'); ?></h2>
<?php wp_login_form(); ?>

<?php
$registration_url = trim( wpbdp_get_option( 'registration-url', '' ) );

if ( ! $registration_url && get_option( 'users_can_register' ) ) {
    if ( function_exists( 'wp_registration_url' ) ) {
        $registration_url = wp_registration_url();
    } else {
        $registration_url = site_url( 'wp-login.php?action=register', 'login' );
    }
}

$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$registration_url = $registration_url ? add_query_arg( array( 'redirect_to' => urlencode( $current_url ) ), $registration_url ) : '';
$lost_password_url = add_query_arg( 'redirect_to', urlencode( $current_url ), wp_lostpassword_url() );
?>

<p>
    <?php if ( $registration_url ): ?>
    <a href="<?php echo esc_url( $registration_url ); ?>"><?php _ex( 'Not yet registered?', 'templates', 'WPBDM' ); ?></a> | 
    <?php endif; ?>
    <a href="<?php echo esc_url( $lost_password_url ); ?>"><?php _ex( 'Lost your password?', 'templates', 'WPBDM' ); ?></a>
</p>
