<div id="wpbdp-main-box">

<div class="main-fields box-row">
    <form action="<?php echo $search_url; ?>" method="post">
        <div class="box-col box-col-fixed">
            <label for="wpbdp-main-box-keyword-field"><?php _ex( 'Find listings for:', 'main box', 'WPBDM' ); ?></label>
        </div>

        <div class="box-col box-col-expand">
            <input type="text" id="wpbdp-main-box-keyword-field" name="q" placeholder="<?php _ex( 'Keywords', 'main box', 'WPBDM' ); ?>" />
        </div>

        <?php echo $extra_fields; ?>

        <div class="box-col box-col-fixed">
            <input type="submit" value="<?php _ex( 'Find Listings', 'main box', 'WPBDM' ); ?>" />
        </div>
        <!-- <a class="advanced&#45;search&#45;link" href="<?php echo $search_url; ?>"><?php _ex( 'Advanced Search', 'main box', 'WPBDM' ); ?></a> -->
    </form>
</div>

<div class="box-row with-separator">
    <?php wpbdp_the_main_links(); ?>
</div>

</div>
