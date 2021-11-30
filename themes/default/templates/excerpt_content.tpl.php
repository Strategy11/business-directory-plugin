<?php
/**
 * Listings except content template
 *
 * @package BDP/Themes/Default/Templates/Excerpt Content
 */

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
        <div class="wpbdp-field-display wpbdp-field wpbdp-field-value field-display field-value">
            <span class="field-label address-label"><?php echo $fields->_h_address_label; ?>:</span>
            <div><?php echo $fields->_h_address; ?></div>
        </div>
        <?php endif; ?>

        <?php echo $fields->exclude('t_title,t_address,t_address2,t_city,t_state,t_country,t_zip')->html; ?>
    </div>

</div>
