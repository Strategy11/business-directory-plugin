<?php
/**
 * Listings except content template
 *
 * @package BDP/Themes/Default/Templates/Excerpt Content
 */

// phpcs:disable
?>

<div class="listing-title">
    <?php echo $fields->t_title->value; ?>
</div>

<div class="excerpt-content">
    <?php if ( $images->thumbnail ): ?>
        <?php echo $images->thumbnail->html; ?>
    <?php endif; ?>

    <div class="listing-details">
        <?php if ( $fields->_h_address ): ?>
        <div class="address-info">
            <span class="address-label"><?php _ex( 'Address', 'themes/default', 'WPBDM' ); ?>:</span>
            <?php echo $fields->_h_address; ?>
        </div>
        <?php endif; ?>

        <?php echo $fields->exclude('t_title,t_address,t_city,t_state,t_country,t_zip')->html; ?>
    </div>

</div>
