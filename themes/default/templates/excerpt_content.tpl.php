<div class="listing-title">
    <?php echo $fields->t_title->value; ?>
</div>

<div class="excerpt-content">
    <?php if ( $images->thumbnail ): ?>
        <?php echo $images->thumbnail->html; ?>
    <?php endif; ?>

    <div class="listing-details">
        <?php if ( $fields->t_address->value || $fields->t_zip->value ): ?>
        <div class="address-info">
            <?php if ( $fields->t_address->value ): ?>
                <label><?php _ex( 'Address', 'themes/default', 'WPBDM' ); ?></label>
                <span class="address"><?php echo $fields->t_address->value; ?></span>
            <?php endif; ?>

            <?php if ( $fields->t_zip->value ): ?>
                <br /><span class="zip-code"><?php echo $fields->t_zip->value; ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php echo $fields->exclude('t_title,t_address,t_zip')->html; ?>
    </div>

</div>
