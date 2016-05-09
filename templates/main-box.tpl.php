<div id="wpbdp-main-box">

<div class="main-fields box-row">
    <form action="<?php echo $search_url; ?>" method="post">
        <label for="wpbdp-main-box-keyword-field"><?php _ex( 'Find listings for:', 'main box', 'WPBDM' ); ?></label>
        <input type="text" id="wpbdp-main-box-keyword-field" name="q" placeholder="<?php _ex( 'Keywords', 'main box', 'WPBDM' ); ?>" />
        <?php echo $extra_fields; ?>
        <input type="submit" value="<?php _ex( 'Find Listings', 'main box', 'WPBDM' ); ?>" />
        <a class="advanced-search-link" href="<?php echo $search_url; ?>"><?php _ex( 'Advanced Search', 'main box', 'WPBDM' ); ?></a>
    </form>
</div>

<div class="box-row with-separator">
    <?php wpbdp_the_main_links(); ?>
</div>

</div>
