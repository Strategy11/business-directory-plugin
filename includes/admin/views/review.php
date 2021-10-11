<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="notice notice-info is-dismissible wpbdp-review-notice">
	<div class="wpbdp-satisfied">
		<p>
			<?php echo esc_html( $title ); ?>
			<br/>
			<?php esc_html_e( 'Are you enjoying Business Directory Plugin?', 'business-directory-plugin' ); ?>
		</p>
		<a href="#" class="wpbdp_reverse_button wpbdp_animate_bg show-wpbdp-feedback wpbdp-button-secondary" data-link="feedback">
			<?php esc_html_e( 'Not Really', 'business-directory-plugin' ); ?>
		</a>
		<a href="#" class="wpbdp_animate_bg show-wpbdp-feedback wpbdp-button-primary" data-link="review">
			<?php esc_html_e( 'Yes!', 'business-directory-plugin' ); ?>
		</a>
	</div>
	<div class="wpbdp-review-request wpbdp_hidden">
		<p><?php esc_html_e( 'Awesome! Could you do me a BIG favor and give Business Directory Plugin a review to help me grow my little business and boost our motivation?', 'business-directory-plugin' ); ?></p>
		<p>- Steph Wells<br/>
			<span><?php esc_html_e( 'Co-Founder and CTO of Business Directory Plugin', 'business-directory-plugin' ); ?><span>
		</p>
		<a href="#" class="wpbdp-dismiss-review-notice wpbdp_reverse_button wpbdp-button-secondary" data-link="no" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'No thanks, maybe later', 'business-directory-plugin' ); ?>
		</a>
		<a href="https://wordpress.org/support/plugin/business-directory-plugin/reviews/?filter=5#new-post" class="wpbdp-dismiss-review-notice wpbdp-review-out wpbdp-button-primary" data-link="yes" target="_blank" rel="noopener">
			<?php esc_html_e( 'Ok, you deserve it', 'business-directory-plugin' ); ?>
		</a>
		<br/>
		<a href="#" class="wpbdp-dismiss-review-notice" data-link="done" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'I already did', 'business-directory-plugin' ); ?>
		</a>
	</div>
</div>
<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		$(document).on( 'click', '.wpbdp-dismiss-review-notice, .wpbdp-review-notice .notice-dismiss', function( event ) {

			if ( ! $( this ).hasClass( 'wpbdp-review-out' ) ) {
				event.preventDefault();
			}
			var link = $( this ).data('link');
			if ( typeof link === 'undefined' ) {
				link = 'no';
			}

			wpbdpDismissReview( link );
			$( '.wpbdp-review-notice' ).remove();
		} );


		$('.show-wpbdp-feedback').click( function( e ){
			e.preventDefault();
			var link = $(this).data('link');
			var className = '.wpbdp-' + link + '-request';
			jQuery('.wpbdp-satisfied').hide();
			jQuery(className).show();
		});
	} );

	function wpbdpDismissReview( link ) {
		jQuery.post( ajaxurl, {
			action: 'wpbdp_dismiss_review',
			link: link,
			nonce: '<?php echo esc_html( wp_create_nonce( 'wpbdp_ajax' ) ); ?>'
		} );
	}
</script>
