<div class="listing-actions">
<?php if ($view == 'single'): ?>
    <?php if (wpbdp_user_can('edit', $listing_id)): ?>
    <form action="<?php echo wpbdp_get_page_link('editlisting', $listing_id); ?>" method="POST">
        <input type="submit" name="" value="<?php _ex('Edit', 'templates', 'WPBDM'); ?>" class="edit-listing" />
    </form>
    <!--<a href="<?php echo wpbdp_get_page_link('editlisting', $listing_id); ?>" class="edit-listing">
        <?php _ex('Edit', 'templates', 'WPBDM'); ?>
    </a>-->
    <?php endif; ?>

    <?php if (wpbdp_user_can('upgrade-to-sticky', $listing_id)): ?>
    <form action="<?php echo wpbdp_get_page_link('upgradetostickylisting', $listing_id); ?>" method="POST">
        <input type="submit" name="" value="<?php _ex('Upgrade Listing', 'templates', 'WPBDM'); ?>" class="upgrade-to-sticky" />
    </form>
    <!--<a href="<?php echo wpbdp_get_page_link('upgradetostickylisting', $listing_id); ?>" class="upgrade-to-sticky">
        <?php _ex('Upgrade Listing', 'templates', 'WPBDM'); ?>
    </a>-->
    <?php endif; ?>

    <?php if (wpbdp_user_can('delete', $listing_id)): ?>
    <form action="<?php echo wpbdp_get_page_link('deletelisting', $listing_id); ?>" method="POST">
        <input type="submit" name="" value="<?php _ex('Delete', 'templates', 'WPBDM'); ?>" class="delete-listing" />
    </form>    
<!--    <a href="<?php echo wpbdp_get_page_link('deletelisting', $listing_id); ?>" class="delete-listing">
        <?php _ex('Delete', 'templates', 'WPBDM'); ?>
    </a>-->
    <?php endif; ?>
<?php elseif ($view == 'excerpt'): ?>
    <?php if (wpbdp_user_can('view', $listing_id)): ?>
    <input type="button" value="<?php _ex('View', 'templates', 'WPBDM'); ?>" class="view-listing"
           onclick="window.location.href = '<?php the_permalink(); ?>' " />

    <!--<a href="<?php the_permalink(); ?>" class="view-listing">
        <?php _ex('View', 'templates', 'WPBDM'); ?>
    </a>-->
    <?php endif; ?>

    <?php if (wpbdp_user_can('edit', $listing_id)): ?>
    <form action="<?php echo wpbdp_get_page_link('editlisting', $listing_id); ?>" method="POST">
        <input type="submit" name="" value="<?php _ex('Edit', 'templates', 'WPBDM'); ?>" class="edit-listing" />
    </form>    
    <!--<a href="<?php echo wpbdp_get_page_link('editlisting', $listing_id); ?>" class="edit-listing">
        <?php _ex('Edit', 'templates', 'WPBDM'); ?>
    </a>-->
    <?php endif; ?>

    <?php if (wpbdp_user_can('delete', $listing_id)): ?>
    <form action="<?php echo wpbdp_get_page_link('deletelisting', $listing_id); ?>" method="POST">
        <input type="submit" name="" value="<?php _ex('Delete', 'templates', 'WPBDM'); ?>" class="delete-listing" />
    </form>        
    <!--<a href="<?php echo wpbdp_get_page_link('deletelisting', $listing_id); ?>" class="delete-listing">
        <?php _ex('Delete', 'templates', 'WPBDM'); ?>
    </a>-->
    <?php endif; ?>
<?php endif; ?>
</div>