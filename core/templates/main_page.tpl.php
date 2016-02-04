<?php
$__template__ = array( 'wrapper' => 'page' );
?>

<div id="wpbdp-categories">
    <?php wpbdp_the_directory_categories(); ?>
</div>

<?php if ( $listings ): ?>
    <?php echo $listings; ?>
<?php endif; ?>
