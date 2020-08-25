<?php
$show_personal_info_section = ! isset( $show_personal_info_section ) ? true : false;
$show_cc_section = ! isset( $show_cc_section ) ? true : false;
$show_details_section = ! isset( $show_details_section ) ? true : false;
?>
<?php if ( $show_personal_info_section ): ?>
<div class="wpbdp-checkout-personal-info-fields wpbdp-checkout-section">
    <h3><?php _ex( 'Personal Info', 'checkout', 'WPBDM' ); ?></h3>

    <div class="wpbdp-billing-detail-field wpbdp-required">
        <label><?php _ex( 'Email Address', 'checkout', 'WPBDM' ); ?></label>
        <span class="wpbdp-description"><?php _ex( 'We will send a receipt to this e-mail address.', 'checkout', 'WPBDM' ); ?></span>
        <input type="text" name="payer_email" value="<?php echo esc_attr( ! empty( $data['payer_email'] ) ? $data['payer_email'] : '' ); ?>" />
    </div>

    <div class="wpbdp-billing-detail-field wpbdp-required">
        <label><?php _ex( 'First Name', 'checkout', 'WPBDM' ); ?></label>
        <span class="wpbdp-description"><?php _ex( 'Your first name.', 'checkout', 'WPBDM' ); ?></span>
        <input type="text" name="payer_first_name" value="<?php echo esc_attr( ! empty( $data['payer_first_name'] ) ? $data['payer_first_name'] : '' ); ?>" />
    </div>

    <div class="wpbdp-billing-detail-field wpbdp-required">
        <label><?php _ex( 'Last Name', 'checkout', 'WPBDM' ); ?></label>
        <span class="wpbdp-description"><?php _ex( 'Your last name.', 'checkout', 'WPBDM' ); ?></span>
        <input type="text" name="payer_last_name" value="<?php echo esc_attr( ! empty( $data['payer_last_name'] ) ? $data['payer_last_name'] : '' ); ?>" />
    </div>
</div>
<?php endif; ?>

<?php if ( $show_cc_section ): ?>
<div class="wpbdp-checkout-cc-fields wpbdp-checkout-section">
    <h3><?php _ex( 'Credit Card Info', 'checkout', 'WPBDM' ); ?></h3>

    <div class="wpbdp-billing-detail-field wpbdp-required">
        <label><?php _ex( 'Card Number', 'checkout', 'WPBDM' ); ?></label>
        <span class="wpbdp-description"><?php _ex( 'The digits on the front of your credit card.', 'checkout', 'WPBDM' ); ?></span>
        <input type="text" name="card_number" value="" placeholder="<?php _ex( 'Card Number', 'checkout', 'WPBDM' ); ?>" />
    </div>

    <div class="wpbdp-billing-detail-field wpbdp-required">
        <label><?php _ex( 'CVC', 'checkout', 'WPBDM' ); ?></label>
        <span class="wpbdp-description"><?php _ex( 'The 3 digit (back) or 4 digit (front) security code on your credit card.', 'checkout', 'WPBDM' ); ?></span>
        <input type="text" name="cvc" value="" placeholder="<?php _ex( 'Security Code', 'checkout', 'WPBDM' ); ?>" />
    </div>

    <div class="wpbdp-billing-detail-field wpbdp-required">
        <label><?php _ex( 'Name on the Card', 'checkout', 'WPBDM' ); ?></label>
        <span class="wpbdp-description"><?php _ex( 'The name as it appears printed on the front of your credit card.', 'checkout', 'WPBDM' ); ?></span>
        <input type="text" name="card_name" value="" placeholder="<?php _ex( 'Name on the card', 'checkout', 'WPBDM' ); ?>" />
    </div>

    <div class="wpbdp-billing-detail-field wpbdp-required">
        <label><?php _ex( 'Expiration Date', 'checkout', 'WPBDM' ); ?></label>
        <span class="wpbdp-description"><?php _ex( 'Format: MM/YY', 'checkout', 'WPBDM'); ?></span>
        <select name="exp_month">
            <?php for ( $i = 1; $i <= 12; $i++ ): ?>
            <option value="<?php echo $i; ?>"><?php printf( '%02d', $i ); ?></option>
            <?php endfor; ?>
        </select>
        /
        <select name="exp_year">
            <?php for ( $i = date( 'Y' ); $i < date( 'Y' ) + 30; $i++ ): ?>
            <option value="<?php echo $i; ?>"><?php echo substr( $i, 2 ); ?></option>
            <?php endfor; ?>
        </select>
    </div>
</div>
<?php endif; ?>

<?php if ( $show_details_section ): ?>
<div class="wpbdp-checkout-billing-details wpbdp-checkout-section">
    <h3><?php _ex( 'Billing Details', 'checkout', 'WPBDM' ); ?></h3>

    <div class="wpbdp-billing-detail-field">
        <label><?php _ex( 'Address', 'checkout', 'WPBDM' ); ?></label>
        <span class="wpbdp-description"><?php _ex( 'Please enter the address where you receive your billing statement.', 'checkout', 'WPBDM' ); ?></span>
        <input type="text" name="payer_address" value="<?php echo esc_attr( ! empty( $data['payer_address'] ) ? $data['payer_address'] : '' ); ?>" />
    </div>

    <div class="wpbdp-billing-detail-field">
        <label><?php _ex( 'Address Line 2', 'checkout', 'WPBDM' ); ?></label>
        <span class="wpbdp-description"><?php _ex( 'Additional details (suite, apt no, etc.) associated with your billing address.', 'checkout', 'WPBDM' ); ?></span>
        <input type="text" name="payer_address_2" value="<?php echo esc_attr( ! empty( $data['payer_address_2'] ) ? $data['payer_address_2'] : '' ); ?>" />
    </div>

    <div class="wpbdp-billing-detail-field wpbdp-required">
        <label><?php _ex( 'City', 'checkout', 'WPBDM' ); ?></label>
        <span class="wpbdp-description"><?php _ex( 'The city for your billing address.', 'checkout', 'WPBDM' ); ?></span>
        <input type="text" name="payer_city" value="<?php echo esc_attr( ! empty( $data['payer_city'] ) ? $data['payer_city'] : '' ); ?>" />
    </div>

    <div class="wpbdp-billing-detail-field">
        <label><?php _ex( 'State / Province', 'checkout', 'WPBDM' ); ?></label>
        <span class="wpbdp-description"><?php _ex( 'The state or province for your billing address.', 'checkout', 'WPBDM' ); ?></span>
        <input type="text" name="payer_state" value="<?php echo esc_attr( ! empty( $data['payer_state'] ) ? $data['payer_state'] : '' ); ?>" />
    </div>

    <div class="wpbdp-billing-detail-field wpbdp-required">
        <label><?php _ex( 'Postal Code', 'checkout', 'WPBDM' ); ?></label>
        <span class="wpbdp-description"><?php _ex( 'The ZIP or postal code for your billing address.', 'checkout', 'WPBDM' ); ?></span>
        <input type="text" name="payer_zip" value="<?php echo esc_attr( ! empty( $data['payer_zip'] ) ? $data['payer_zip'] : '' ); ?>" />
    </div>

    <div class="wpbdp-billing-detail-field wpbdp-required">
        <label><?php _ex( 'Country', 'checkout', 'WPBDM' ); ?></label>
        <span class="wpbdp-description"><?php _ex( 'The country for your billing address.', 'checkout', 'WPBDM' ); ?></span>
        <input type="text" name="payer_country" value="<?php echo esc_attr( ! empty( $data['payer_country'] ) ? $data['payer_country'] : '' ); ?>" />
    </div>

</div>
<?php endif; ?>
