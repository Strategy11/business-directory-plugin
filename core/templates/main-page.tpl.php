<div id="wpbdp-categories">
    <?php  wpbdp_the_directory_categories(); ?>
</div>

<?php if ( $listings ): ?>
    <?php echo wpbdp_x_render( 'listings', array( 'query' => $listings ) ); ?>
<?php endif; ?>
