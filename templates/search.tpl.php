<?php $_class .= wp_doing_ajax() ? ' wpbdp-modal' : ''; ?>

<div id="wpbdp-search-page" class="wpbdp-search-page businessdirectory-search businessdirectory wpbdp-page <?php echo esc_attr( $_class ); ?>">
	<?php if ( wp_doing_ajax() ) : ?>
	<div class="wpbdp-modal-overlay"></div>
	<div class="wpbdp-modal-content">
		<div class="wpbdp-modal-scrollbar">
			<span class="wpbdp-modal-close"></span>

	<?php elseif ( ! $form_only ) : ?>
		<div class="wpbdp-bar cf"><?php wpbdp_the_main_links(); ?></div>
	<?php endif; ?>
	<h2 class="title"><?php esc_html_e( 'Advanced Search', 'business-directory-plugin' ); ?></h2>

	<?php if ( 'none' === $search_form_position || 'above' === $search_form_position ) : ?>
		<?php echo $search_form; ?>
	<?php endif; ?>

	<?php if ( $searching ) : ?>
		<h3><?php echo esc_html__( 'Search Results', 'business-directory-plugin' ) . ' (' . esc_html( $count ) . ')'; ?>
		<?php if ( 'none' === $search_form_position ) : ?>
			<?php
			$return_url = wpbdp_get_var( array( 'param' => 'return_url' ), 'request' );
			if ( empty( $return_url ) ) :
				$return_url = wpbdp_get_page_link( 'search' );
			endif;
			?>
			<a class="wpbdp-no-bold wpbdp-smaller" href="<?php echo esc_url( $return_url ); ?>">
				<?php esc_html_e( 'Search Again', 'business-directory-plugin' ); ?>
			</a>
		<?php endif; ?>
		</h3>
		<?php if ( $results ) : ?>
			<div class="search-results">
			<?php echo $results; ?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'No listings found.', 'business-directory-plugin' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( 'below' === $search_form_position ) : ?>
		<?php echo $search_form; ?>
	<?php endif; ?>

	<?php if ( wp_doing_ajax() ) : ?>
		</div>
	</div>
	<?php endif; ?>
</div>
