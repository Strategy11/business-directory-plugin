<h3><?php echo $_state->step_number . ' - '; ?><?php _ex( 'Submission Received', 'templates', 'WPBDM' ); ?></h3>

<?php if ( ! $_state->editing ): ?>
    <p><?php _ex( 'Your listing has been submitted.', 'templates', 'WPBDM' ); ?></p>
<?php else: ?>
    <p><?php _ex('Your listing changes were saved.', 'templates', 'WPBDM'); ?></p>
<?php endif; ?>
    
    <p>
        <?php if ( 'publish' == get_post_status( $_state->listing_id ) ): ?>
            <a href="<?php echo get_permalink( $_state->listing_id ); ?>"><?php _ex( 'Go to your listing', 'templates', 'WPBDM' ); ?></a> | 
        <?php endif; ?>
        <a href="<?php echo wpbdp_get_page_link( 'main' ); ?>"><?php _ex( 'Return to directory.', 'templates', 'WPBDM' ); ?></a>
   </p>
