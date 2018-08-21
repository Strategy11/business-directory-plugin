<?php
/**
 * Login form template
 *
 * @package BDP/Templates/Login
 */

// phpcs:disable WordPress.XSS.EscapeOutput.UnsafePrintingFunction
// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode
// phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
$show_message = isset( $show_message ) ? $show_message : true;

$registration_url = trim( wpbdp_get_option( 'registration-url', '' ) );

if ( ! $registration_url && get_option( 'users_can_register' ) ) {
    if ( function_exists( 'wp_registration_url' ) ) {
        $registration_url = wp_registration_url();
    } else {
        $registration_url = site_url( 'wp-login.php?action=register', 'login' );
    }
}

$registration_url  = $registration_url ? add_query_arg( array( 'redirect_to' => urlencode( $redirect_to ) ), $registration_url ) : '';
$lost_password_url = add_query_arg( 'redirect_to', urlencode( $redirect_to ), wp_lostpassword_url() );
?>

<div id="wpbdp-login-view">

    <?php if ( $show_message ) : ?>
    <?php echo wpbdp_render_msg( _x( "You are not currently logged in. Please login or register first. When registering, you will receive an activation email. Be sure to check your spam if you don't see it in your email within 60 minutes.", 'templates', 'WPBDM' ) ); ?>
    <?php endif; ?>

    <div class="wpbdp-login-options <?php echo $access_key_enabled ? 'options-2' : 'options-1'; ?>">

        <div id="wpbdp-login-form" class="wpbdp-login-option">
            <h4><?php _ex( 'Login', 'views:login', 'WPBDM' ); ?></h4>
            <?php wp_login_form( array( 'redirect' => $redirect_to ) ); ?>

            <p class="wpbdp-login-form-extra-links">
                <?php if ( $registration_url ) : ?>
                <a href="<?php echo esc_url( $registration_url ); ?>"><?php _ex( 'Not yet registered?', 'templates', 'WPBDM' ); ?></a> |
                <?php endif; ?>
                <a href="<?php echo esc_url( $lost_password_url ); ?>"><?php _ex( 'Lost your password?', 'templates', 'WPBDM' ); ?></a>
            </p>
        </div>

        <?php if ( $access_key_enabled ) : ?>
            <div id="wpbdp-login-access-key-form" class="wpbdp-login-option">
                <h4><?php _ex( '... or use an Access Key', 'views:login', 'WPBDM' ); ?></h4>
                <p><?php _ex( 'Please enter your access key and e-mail address.', 'views:login', 'WPBDM' ); ?></p>

                <form action="" method="post">
                    <input type="hidden" name="method" value="access_key" />
                    <p><input type="text" name="email" value="" placeholder="<?php _ex( 'E-Mail Address', 'views:login', 'WPBDM' ); ?>" /></p>
                    <p><input type="text" name="access_key" value="" placeholder="<?php _ex( 'Access Key', 'views:login', 'WPBDM' ); ?>" /></p>
                    <p><input type="submit" value="<?php _ex( 'Use Access Key', 'views:login', 'WPBDM' ); ?>" /></p>
                    <p><a href="<?php echo esc_url( $request_access_key_url ); ?>"><?php _ex( 'Request access key?', 'views:login', 'WPBDM' ); ?></a></p>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
