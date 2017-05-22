<h2 class="category-name">
    <?php echo esc_html( $term->name ); ?>
</h2>

<?php echo wpbdp_x_render( 'listings', array( 'query' => $query ) ); ?>
