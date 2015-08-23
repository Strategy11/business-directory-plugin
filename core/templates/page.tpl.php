<?php if ( $_bar ): ?>
    <?php echo wpbdp_x_render( 'bar', array( 'items' => $_bar_items ) ); ?>
<?php endif; ?>

<?php //do_action( 'wpbdp_page_before', $_id, $_template, $_vars ); ?>
<?php //do_action( 'wpbdp_page_' . $_id . '_before', $_vars ); ?>

<div id="wpbdp-page-<?php echo $_id; ?>" class="wpbdp-page wpbdp-page-<?php echo $_id; ?> <?php echo $_class; ?>">

    <?php //do_action( 'wpbdp_page_before_inner', $_id, $_template, $_vars ); ?>
    <?php //do_action( 'wpbdp_page_' . $_id . '_before_inner', $_vars ); ?>
    <div class="wpbdp-page-inner <?php echo $_inner_class; ?>">
        <?php echo $content; ?>
    </div>
    <?php //do_action( 'wpbdp_page_' . $_id . '_after_inner', $_vars ); ?>
    <?php //do_action( 'wpbdp_page_after_inner', $_id, $_template, $_vars ); ?>

</div>

<?php //do_action( 'wpbdp_page_' . $_id . '_after', $_vars ); ?>
<?php //do_action( 'wpbdp_page_after', $_id, $_template, $_vars ); ?>
