<div id="wpbdp-search-page" class="wpbdp-search-page businessdirectory-search businessdirectory wpbdp-page <?php echo $_class; ?>">
    <div class="wpbdp-bar cf"><?php wpbdp_the_main_links(); ?></div>
    <h2 class="title"><?php _ex('Search', 'search', 'business-directory-plugin' ); ?></h2>

    <?php if ( 'none' == $search_form_position || 'above' == $search_form_position ): ?>
    <?php echo $search_form; ?>
    <?php endif; ?>

    <?php if ($searching): ?>
        <h3><?php _ex('Search Results', 'search', 'business-directory-plugin' ); ?></h3>

        <?php if ( $results ): ?>
            <div class="search-results">
            <?php echo $results; ?>
            </div>
        <?php else: ?>
            <p><?php esc_html_e( 'No listings found.', 'business-directory-plugin' ); ?></p>
            <?php if ( 'none' == $search_form_position ): ?>
                <?php
                $return_url = wpbdp_get_var( array( 'param' => 'return_url' ), 'request' );
                if ( empty( $return_url ) ):
                    $return_url = wpbdp_get_page_link( 'search');
                endif;
                ?>
                <p><?php echo sprintf( '<a href="%s">%s</a>.', esc_url( $return_url ), esc_html__( 'Return to Search', 'business-directory-plugin' ) ); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ( 'below' == $search_form_position ): ?>
    <?php echo $search_form; ?>
    <?php endif; ?>
</div>
