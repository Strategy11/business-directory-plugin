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
<<<<<<< HEAD
	<?php wpbdp_x_part( 'parts/excerpt-details', array( 'exclude_fields' => 't_title' ) ); ?>
=======
    <?php if ( $images->thumbnail ): ?>
        <?php echo $images->thumbnail->html; ?>
    <?php endif; ?>

    <div class="listing-details">
        <?php if ( $fields->_h_address ): ?>
        <div class="address-info">
            <span class="address-label"><?php esc_html_e( 'Address', 'business-directory-plugin' ); ?>:</span>
            <?php echo $fields->_h_address; ?>
        </div>
        <?php endif; ?>

        <?php echo $fields->exclude('t_title,t_address,t_address2,t_city,t_state,t_country,t_zip')->html; ?>
    </div>

>>>>>>> parent of 9c9beccc (Excerpt view default theme)
</div>
