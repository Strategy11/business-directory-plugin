<?php
if ( $fallback ) :
	?>
	<div id="wpbdp-search-page" class="wpbdp-search-page businessdirectory-search businessdirectory wpbdp-page <?php echo esc_attr( $_class ); ?>">
	<?php if ( ! $form_only ) : ?>
		<div class="wpbdp-bar cf"><?php wpbdp_the_main_links(); ?></div>
	<?php endif; ?>
	<h2 class="title"><?php esc_html_e( 'Search', 'business-directory-plugin' ); ?></h2>

	<?php
	echo $search_form;
	return;
endif;

if ( wp_doing_ajax() ) :
	?>
	<div class="wpbdp-search-page businessdirectory-search wpbdp-page wpbdp-modal <?php echo esc_attr( $_class ); ?>">
		<div class="wpbdp-modal-overlay"></div>
		<div class="wpbdp-modal-content">
			<div class="wpbdp-modal-scrollbar">
				<span class="wpbdp-modal-close"></span>
				<h2 class="title"><?php esc_html_e( 'Advanced Search', 'business-directory-plugin' ); ?></h2>
				<?php echo $search_form; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php if ( $searching ) : ?>
	<div id="wpbdp-search-page" class="wpbdp-search-page businessdirectory-search businessdirectory wpbdp-page <?php echo esc_attr( $_class ); ?>">
		<?php echo wpbdp_main_box(); ?>

		<h3>
			<?php
			echo esc_html__( 'Search Results', 'business-directory-plugin' ) .
			' (' . esc_html( $count ) . ')';
			?>
		</h3>
		<?php if ( $results ) : ?>
			<div class="search-results">
			<?php echo $results; ?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'No listings found.', 'business-directory-plugin' ); ?></p>
		<?php endif; ?>

		<?php $searched = array_filter( (array) wpbdp_get_var( array( 'param' => 'listingfields' ) ) ); ?>
		<span id="wpdbp-searched-terms" data-search-terms="<?php echo esc_attr( wp_json_encode( $searched ) ); ?>"></span>
	</div>
<?php endif; ?>
