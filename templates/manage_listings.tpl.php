<div id="wpbdp-manage-listings-page" class="wpbdp-manage-listings-page businessdirectory-manage-listings businessdirectory wpbdp-page">
    <?php if ( ! $query->have_posts() ): ?>
        <p><?php _ex('You do not currently have any listings in the directory.', 'templates', 'WPBDM'); ?></p>
        <?php echo sprintf('<a href="%s">%s</a>.', wpbdp_get_page_link('main'),
                           _x('Return to directory', 'templates', 'WPBDM')); ?> 
    <?php else: ?>
        <p><?php _ex("Your current listings are shown below. To edit a listing click the edit button. To delete a listing click the delete button.", 'templates', "WPBDM"); ?></p>
        <?php
        while ( $query->have_posts() ) :
            $query->the_post();
            echo WPBDP_Listing_Display_Helper::excerpt();
        endwhile;
        ?>

        <div class="wpbdp-pagination">
        <?php
        if ( function_exists( 'wp_pagenavi' ) ) :
            wp_pagenavi( array( 'query' => $query ) );
        else :
        ?>
            <span class="prev"><?php previous_posts_link( _x( '&laquo; Previous ', 'templates', 'WPBDM' ) ); ?></span>
            <span class="next"><?php next_posts_link( _x( 'Next &raquo;', 'templates', 'WPBDM' ), $query->max_num_pages ); ?></span>
        <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
