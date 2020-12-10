<?php
if ( ! $query->have_posts() ) :
	?>
	<span class="no-listings">
		<?php esc_html_e( 'No listings found.', 'business-directory-plugin' ); ?>
	</span>
	<?php
else:
	while ( $query->have_posts() ) {
		$query->the_post();
		wpbdp_render_listing( null, 'excerpt', 'echo' );
	}

	wpbdp_render(
		'parts/pagination',
		array(
			'query' => $query,
			'echo'  => true,
		),
		true
	);

endif;
