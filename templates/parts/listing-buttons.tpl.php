<?php // TODO: verify user can perform each one of these actions ?>
<div class="listing-actions">
<?php if ($view == 'single'): ?>
    <?php if (wpbdp_user_can('edit', $listing_id)): ?>
    <a href="<?php echo wpbdp_get_page_link('editlisting', $listing_id); ?>" class="edit-listing">
        <?php _ex('Edit', 'templates', 'WPBDM'); ?>
    </a>
    <?php endif; ?>

    <?php if (wpbdp_user_can('upgrade-to-sticky', $listing_id)): ?>
    <a href="<?php echo wpbdp_get_page_link('upgradetostickylisting', $listing_id); ?>" class="upgrade-to-sticky">
        <?php _ex('Upgrade Listing', 'templates', 'WPBDM'); ?>
    </a>
    <?php endif; ?>

    <?php if (wpbdp_user_can('delete', $listing_id)): ?>
    <a href="<?php echo wpbdp_get_page_link('deletelisting', $listing_id); ?>" class="delete-listing">
        <?php _ex('Delete', 'templates', 'WPBDM'); ?>
    </a>
    <?php endif; ?>
<?php elseif ($view == 'excerpt'): ?>
    <?php if (wpbdp_user_can('view', $listing_id)): ?>
    <a href="<?php the_permalink(); ?>" class="view-listing">
        <?php _ex('View', 'templates', 'WPBDM'); ?>
    </a>
    <?php endif; ?>

    <?php if (wpbdp_user_can('edit', $listing_id)): ?>
    <a href="<?php echo wpbdp_get_page_link('editlisting', $listing_id); ?>" class="edit-listing">
        <?php _ex('Edit', 'templates', 'WPBDM'); ?>
    </a>
    <?php endif; ?>

    <?php if (wpbdp_user_can('delete', $listing_id)): ?>
    <a href="<?php echo wpbdp_get_page_link('deletelisting', $listing_id); ?>" class="delete-listing">
        <?php _ex('Delete', 'templates', 'WPBDM'); ?>
    </a>
    <?php endif; ?>
<?php endif; ?>
</div>