<?php
$__template__ = array( 'wrapper' => 'page' );
?>

<h2 class="category-name">
    <?php echo esc_html( $category->name ); ?>
</h2>

<!-- TODO: what to do with the filter "wpbdp_category_page_listings" -->
<?php echo wpbdp_x_render( 'listings', array( 'query' => $query ) ); ?>
