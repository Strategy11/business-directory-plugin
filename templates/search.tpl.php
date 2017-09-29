<div id="wpbdp-search-page" class="wpbdp-search-page businessdirectory-search businessdirectory wpbdp-page <?php echo $_class; ?>">
    <div class="wpbdp-bar cf"><?php wpbdp_the_main_links(); ?></div>
    <h2 class="title"><?php _ex('Search', 'search', 'WPBDM'); ?></h2>

    <?php if ( 'none' == $search_form_position || 'above' == $search_form_position ): ?>
    <?php echo $search_form; ?>
    <?php endif; ?>

    <?php if ($searching): ?>
        <h3><?php _ex('Search Results', 'search', 'WPBDM'); ?></h3>

        <?php if ( $results ): ?>
            <div class="search-results">
            <?php echo $results; ?>
            </div>
        <?php else: ?>
            <p><?php _ex( "No listings found.", 'templates', "WPBDM" ); ?></p>
            <?php if ( 'none' == $search_form_position ): ?>
                <?php
                if ( ! empty( $_REQUEST['return_url'] ) ):
                    $return_url = $_REQUEST['return_url'];
                else:
                    $return_url = wpbdp_get_page_link( 'search');
                endif;
                ?>
                <p><?php echo sprintf( '<a href="%s">%s</a>.', $return_url, _x('Return to Search', 'templates', 'WPBDM' ) ); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ( 'below' == $search_form_position ): ?>
    <?php echo $search_form; ?>
    <?php endif; ?>
</div>
