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
            <label><?php _ex( 'Address', 'themes/default', 'WPBDM' ); ?></label>
            <?php echo $fields->_h_address; ?>
        </div>
        <?php endif; ?>

        <?php echo $fields->exclude('t_title,t_address,t_city,t_state,t_zip')->html; ?>
    </div>

</div>
