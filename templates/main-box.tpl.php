<div id="wpbdp-main-box">

<div class="main-fields box-row cols-2">
    <form action="<?php echo $search_url; ?>" method="get">
        <input type="hidden" name="wpbdp_view" value="search" />
        <div class="box-col search-fields">
            <div class="box-row cols-<?php echo $no_cols; ?>">
                <div class="box-col main-input">
                    <input type="text" id="wpbdp-main-box-keyword-field" class="keywords-field" name="kw" placeholder="<?php esc_attr_e( _x( 'Find listings for <keywords>', 'main box', 'WPBDM' ) ); ?>" />
                </div>
                <?php echo $extra_fields; ?>
            </div>
        </div>
        <div class="box-col submit-btn">
            <input type="submit" value="<?php _ex( 'Find Listings', 'main box', 'WPBDM' ); ?>" /><br />
            <a class="advanced-search-link" href="<?php echo $search_url; ?>"><?php _ex( 'Advanced Search', 'main box', 'WPBDM' ); ?></a>
        </div>
    </form>
</div>

<div class="box-row separator"></div>

<div class="box-row">
    <?php wpbdp_the_main_links(); ?>
</div>

</div>
