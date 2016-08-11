<?php
$show_bar = ( isset( $_child->_bar ) ? $_child->_bar : ( isset( $_bar ) ? $_bar : true ) );
?>
<div id="wpbdp-page-<?php echo $_child->_id; ?>" class="wpbdp-page wpbdp-page-<?php echo $_child->_id; ?> <?php echo $_class; ?>">
    <?php if ( $show_bar ): ?><?php echo wpbdp_x_render( 'bar' ); ?><?php endif; ?>

    <?php
    // TODO: Try to use blocks for this too, instead of actions.
    ?>

    <?php do_action( 'wpbdp_page_before', $_child->_id ); ?>
    <?php do_action( 'wpbdp_page_' . $_child->_id . '_before' ); ?>
    <?php echo $content; ?>
    <?php do_action( 'wpbdp_page_after', $_child->_id ); ?>
    <?php do_action( 'wpbdp_page_' . $_child->_id . '_after' ); ?>
</div>
