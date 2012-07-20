<?php // TODO: verify user can perform each one of these actions ?>
<div class="listing-actions">
<?php if (is_user_logged_in()): ?>
    <?php if ($view == 'single'): ?>
        <a href="<?php echo wpbdp_get_page_link('editlisting', $listing_id); ?>" class="edit-listing">
            <?php _ex('Edit', 'templates', 'WPBDM'); ?>
        </a>    
        <a href="<?php echo wpbdp_get_page_link('upgradetostickylisting', $listing_id); ?>" class="upgrade-to-sticky">
            <?php _ex('Upgrade Listing', 'templates', 'WPBDM'); ?>
        </a>

        <a href="<?php echo wpbdp_get_page_link('deletelisting', $listing_id); ?>" class="delete-listing">
            <?php _ex('Delete', 'templates', 'WPBDM'); ?>
        </a>        
    <?php elseif ($view == 'excerpt'): ?>
        <a href="<?php the_permalink(); ?>" class="view-listing">
            <?php _ex('View', 'templates', 'WPBDM'); ?>
        </a>
        <a href="<?php echo wpbdp_get_page_link('editlisting', $listing_id); ?>" class="edit-listing">
            <?php _ex('Edit', 'templates', 'WPBDM'); ?>
        </a>
        <a href="<?php echo wpbdp_get_page_link('deletelisting', $listing_id); ?>" class="delete-listing">
            <?php _ex('Delete', 'templates', 'WPBDM'); ?>
        </a>
    <?php endif; ?>
<?php endif; ?>
</div>