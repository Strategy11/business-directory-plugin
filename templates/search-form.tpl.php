<div id="wpbdp-search-form-wrapper">

<h3><?php _ex('Find a listing', 'templates', 'WPBDM'); ?></h3>
<form action="<?php echo esc_url( wpbdp_get_page_link( 'main' ) ); ?>" id="wpbdp-search-form" method="GET">
    <input type="hidden" name="action" value="search" />
    <input type="hidden" name="page_id" value="<?php echo wpbdp_get_page_id('main'); ?>" />
    <input type="hidden" name="dosrch" value="1" />
    <input type="hidden" name="q" value="" />

    <?php echo $fields; ?>
    <?php do_action('wpbdp_after_search_fields'); ?>

    <p>
        <input type="reset" class="wpbdp-button reset" value="<?php _ex( 'Clear', 'search', 'WPBDM' ); ?> " onclick="window.location.href = '<?php echo wpbdp_get_page_link( 'search' ); ?>';" />
        <input type="submit" class="wpbdp-submit wpbdp-button submit" value="<?php _ex('Search', 'search', 'WPBDM'); ?>" />
    </p>
</form>

</div>
