<?php

function wpbdp_render_page($template, $vars=array(), $echo_output=false) {
    if ($vars) {
        extract($vars);
    }

    ob_start();
    include($template);
    $html = ob_get_contents();
    ob_end_clean();

    if ($echo_output)
        echo $html;

    return $html;
}

function wpbdp_locate_template($template, $allow_override=true, $try_defaults=true) {
    $template_file = '';

    if (!is_array($template))
        $template = array($template);

    if ($allow_override) {
        $search_for = array();

        foreach ($template as $t) {
            $search_for[] = $t . '.tpl.php';
            $search_for[] = $t . '.php';
            $search_for[] = 'single/' . $t . '.tpl.php';
            $search_for[] = 'single/' . $t . '.php';
        }

        $template_file = locate_template($search_for);
    }

    if (!$template_file && $try_defaults) {
        foreach ($template as $t) {
            $template_path = WPBDP_TEMPLATES_PATH . '/' . $t . '.tpl.php'; 
            
            if (file_exists($template_path)) {
                $template_file = $template_path;
                break;
            }
        }
    }

    return $template_file;
}

function wpbdp_render($template, $vars=array(), $allow_override=true) {
    $vars = wp_parse_args($vars, array(
        '__page__' => array(
            'class' => array(),
            'content_class' => array(),
            'before_content' => '')));
    $template_name = is_array( $template ) ? $template[0] : $template;
    $vars = apply_filters('wpbdp_template_vars', $vars, $template_name);
    return apply_filters( "wpbdp_render_{$template_name}", wpbdp_render_page(wpbdp_locate_template($template, $allow_override), $vars, false) );
}

function wpbdp_render_msg($msg, $type='status') {
    $html = '';
    $html .= sprintf('<div class="wpbdp-msg %s">%s</div>', $type, $msg);
    return $html;
}

function _wpbdp_template_mode($template) {
    if ( wpbdp_locate_template(array('businessdirectory-' . $template, 'wpbusdirman-' . $template), true, false) )
        return 'template';
    return 'page';
}


/**
 * @since 3.5.6
 */
function _wpbdp_recaptchalib_mode() {
    if ( function_exists( 'recaptcha_get_html' ) )
        return 'classic';

    if ( class_exists( 'ReCaptcha' ) && class_exists( 'ReCaptchaResponse' ) )
        return 'default';

    return 'none';
}

/**
 * Displays a reCAPTCHA field using the configured settings.
 * @return string HTML for the reCAPTCHA field.
 * @since 3.4.2
 */
function wpbdp_recaptcha() {
    $public_key = wpbdp_get_option( 'recaptcha-public-key' );

    if ( ! $public_key )
        return '';

    $html = '';

    switch ( _wpbdp_recaptchalib_mode() ) {
        case 'none':
            require_once( WPBDP_PATH . 'vendors/recaptcha/recaptchalib.php' );
        case 'classic':
            $html = recaptcha_get_html( $public_key, null, is_ssl() );
            break;
        case 'default':
            $html = sprintf( '<div class="g-recaptcha" data-sitekey="%s"></div>', $public_key );
            $html .= sprintf( '<script type="text/javascript" src="%s"></script>', 'https://www.google.com/recaptcha/api.js?hl=en' );
            break;
        default:
            break;
    }

    return $html;
}

/**
 * Validates reCAPTCHA input.
 * @return boolean TRUE if validation succeeded, FALSE otherwise.
 * @since 3.4.2
 */
function wpbdp_recaptcha_check_answer( &$error_msg = null ) {
    $private_key = wpbdp_get_option( 'recaptcha-private-key' );

    if ( ! $private_key )
        return true;

    switch ( _wpbdp_recaptchalib_mode() ) {
        case 'none':
            require_once( WPBDP_PATH . 'vendors/recaptcha/recaptchalib.php' );
        case 'classic':
            $resp = recaptcha_check_answer( $private_key, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field'] );

            if ( ! $resp->is_valid )
                $error_msg = $resp->error;

            return $resp->is_valid;
            break;
        case 'default':
            $reCaptcha = new ReCaptcha( $private_key );
            $resp = $reCaptcha->verifyResponse( $_SERVER['REMOTE_ADDR'], $_POST['g-recaptcha-response'] );

            return ( null != $resp && $resp->success );
            break;
        default:
            break;
    }

    return false;
}
