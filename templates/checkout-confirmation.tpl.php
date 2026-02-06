<?php
switch ( $payment->status ) :
	case 'completed':
		echo wp_kses_post( wpbdp_get_option( 'payment-message' ) );
		break;
	case 'on-hold':
		wpbdp_render_msg(
			_x( 'Your payment is on hold. Please contact the admin if you need further details.', 'checkout', 'business-directory-plugin' ),
			'status',
			'echo'
		);
		break;
	case 'failed':
		wpbdp_render_msg(
			_x( 'Your payment was rejected. Please contact the admin for further details.', 'checkout', 'business-directory-plugin' ),
			'error',
			'echo'
		);
		break;
	case 'canceled':
		wpbdp_render_msg(
			sprintf( _x( 'The payment (#%s) was canceled at your request.', 'checkout', 'business-directory-plugin' ), $payment->id ),
			'status',
			'echo'
		);
		break;
	case 'pending':
		echo '<p>';
		esc_html_e( 'Your payment is awaiting verification by the gateway.', 'business-directory-plugin' );
		echo '</p>';
		wpbdp_render_msg(
			_x( 'Verification usually takes 1-2 minutes. This page will automatically refresh when there\'s an update.', 'checkout', 'business-directory-plugin' ),
			'status',
			'echo'
		);
		break;
	default:
		wp_die();
endswitch;
?>

<?php if ( 'canceled' != $payment->status ) : ?>
<div id="wpbdp-checkout-confirmation-receipt">
	<?php echo wpbdp()->payments->render_receipt( $payment ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
<?php endif; ?>

<?php if ( 'pending' == $payment->status ) : ?>
<script>
setTimeout(function() {
	location.reload();
}, 5000 );
</script>
<?php endif; ?>
