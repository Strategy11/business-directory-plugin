<?php
$months = array(
    '01' => _x( 'Jan', 'months', 'WPBDM' ),
    '02' => _x( 'Feb', 'months', 'WPBDM' ),
    '03' => _x( 'Mar', 'months', 'WPBDM' ),
    '04' => _x( 'Apr', 'months', 'WPBDM' ),
    '05' => _x( 'May', 'months', 'WPBDM' ),
    '06' => _x( 'Jun', 'months', 'WPBDM' ),
    '07' => _x( 'Jul', 'months', 'WPBDM' ),
    '08' => _x( 'Aug', 'months', 'WPBDM' ),
    '09' => _x( 'Sep', 'months', 'WPBDM' ),
    '10' => _x( 'Oct', 'months', 'WPBDM' ),
    '11' => _x( 'Nov', 'months', 'WPBDM' ),
    '12' => _x( 'Dec', 'months', 'WPBDM' ),
);
?>

<form action="<?php echo $action; ?>" id="wpbdp-billing-information" method="post">

    <?php if ( $errors ): ?>
    <ul class="validation-errors">
        <?php foreach ( $errors as $err ): ?>
        <li><?php echo $err; ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <div class="billing-info-section cc-details">
        <h4><?php _ex( 'Credit Card Details', 'checkout form', 'WPBDM' ); ?></h4>
        <p><?php _ex( 'Please enter your credit card details below.', 'checkout form', 'WPBDM' ); ?></p>

        <table>
            <tr class="wpbdp-billing-field customer-first-name">
                <td scope="row">
                    <label for="wpbdp-billing-field-first-name"><?php _ex( 'First Name:', 'checkout form', 'WPBDM' ); ?></label>
                </td>
                <td>
                    <input type="text" id="wpbdp-billing-field-first-name" name="first_name" size="25" value="<?php echo esc_attr( wpbdp_getv( $posted, 'first_name' ) ); ?>" />
                </td>
            </tr>
            <tr class="wpbdp-billing-field customer-last-name">
                <td scope="row">
                    <label for="wpbdp-billing-field-last-name"><?php _ex( 'Last Name:', 'checkout form', 'WPBDM' ); ?></label>
                </td>
                <td>
                    <input type="text" id="wpbdp-billing-field-last-name" name="last_name" size="25" value="<?php echo esc_attr( wpbdp_getv( $posted, 'last_name' ) ); ?>" />
                </td>
            </tr>
            <tr class="wpbdp-billing-field cc-number">
                <td scope="row">
                    <label for="wpbdp-billing-field-number"><?php _ex( 'Card Number:', 'checkout form', 'WPBDM' ); ?></label>
                </td>
                <td>
                    <input type="text" id="wpbdp-billing-field-number" name="cc_number" size="25" value="<?php echo esc_attr( wpbdp_getv( $posted, 'cc_number' ) ); ?>" />
                </td>
            </tr>
            <tr class="wpbdp-billing-field cc-exp">
                <td scope="row">
                    <label for="wpbdp-billing-field-exp"><?php _ex( 'Expiration Date (MM/YYYY):', 'checkout form', 'WPBDM' ); ?></label>
                </td>
                <td>
                    <select id="wpbdp-billing-field-exp" name="cc_exp_month">
                        <?php foreach ( $months as $month => $name ): ?>
                            <option value="<?php echo $month; ?>"><?php echo $month; ?> - <?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select> /
                    <input type="text" size="8"name="cc_exp_year" />
                </td>
            </tr>
            <tr class="wpbdp-billing-field cc-cvc">
                <td scope="row">
                    <label for="wpbdp-billing-field-cvc"><?php _ex( 'CVC:', 'checkout form', 'WPBDM' ); ?></label>
                </td>
                <td>
                    <input type="text" id="wpbdp-billing-field-cvc" name="cc_cvc" size="8" />
                </td>
            </tr>
        </table>
    </div>

    <div class="billing-info-section billing-address">
        <h4><?php _ex( 'Billing Address', 'checkout form', 'WPBDM' ); ?></h4>

        <table>
            <tr class="wpbdp-billing-field customer-country">
                <td scope="row">
                    <label for="wpbdp-billing-field-country"><?php _ex( 'Country:', 'checkout form', 'WPBDM' ); ?></label>
                </td>
                <td>
                    <input type="text" id="wpbdp-billing-field-country" name="address_country" size="25" value="<?php echo esc_attr( wpbdp_getv( $posted, 'address_country' ) ); ?>" />
                </td>
            </tr>
            <tr class="wpbdp-billing-field customer-state">
                <td scope="row">
                    <label for="wpbdp-billing-field-state"><?php _ex( 'State:', 'checkout form', 'WPBDM' ); ?></label>
                </td>
                <td>
                    <input type="text" id="wpbdp-billing-field-state" name="address_state" size="25" value="<?php echo esc_attr( wpbdp_getv( $posted, 'address_state' ) ); ?>" />
                </td>
            </tr>
            <tr class="wpbdp-billing-field customer-city">
                <td scope="row">
                    <label for="wpbdp-billing-field-city"><?php _ex( 'City:', 'checkout form', 'WPBDM' ); ?></label>
                </td>
                <td>
                    <input type="text" id="wpbdp-billing-field-city" name="address_city" size="25" value="<?php echo esc_attr( wpbdp_getv( $posted, 'address_city' ) ); ?>" />
                </td>
            </tr>
            <tr class="wpbdp-billing-field customer-address-1">
                <td scope="row">
                    <label for="wpbdp-billing-field-address-1"><?php _ex( 'Address Line 1:', 'checkout form', 'WPBDM' ); ?></label>
                </td>
                <td>
                    <input type="text" id="wpbdp-billing-field-address-1" name="address_line1" size="25" value="<?php echo esc_attr( wpbdp_getv( $posted, 'address_line1' ) ); ?>" />
                </td>
            </tr>
            <tr class="wpbdp-billing-field customer-address-2">
                <td scope="row">
                    <label for="wpbdp-billing-field-address-2"><?php _ex( 'Address Line 2:', 'checkout form', 'WPBDM' ); ?></label>
                </td>
                <td>
                    <input type="text" id="wpbdp-billing-field-address-2" name="address_line2" size="25" value="<?php echo esc_attr( wpbdp_getv( $posted, 'address_line2' ) ); ?>" />
                </td>
            </tr>
            <tr class="wpbdp-billing-field customer-zip-code">
                <td scope="row">
                    <label for="wpbdp-billing-field-zip-code"><?php _ex( 'ZIP Code:', 'checkout form', 'WPBDM' ); ?></label>
                </td>
                <td>
                    <input type="text" id="wpbdp-billing-field-zip-code" name="zipcode" size="25" value="<?php echo esc_attr( wpbdp_getv( $posted, 'zipcode' ) ); ?>" />
                </td>
            </tr>
        </table>
    </div>

    <div class="form-buttons">
        <!-- <input type="submit" name="cancel" value="<?php _ex( 'Cancel', 'WPBDM' ); ?>" /> -->
        <input type="submit" name="pay" value="<?php _ex( 'Submit Payment', 'WPBDM' ); ?>" class="button submit" />
    </div>

</form>

<?php
/*
    <div class="wpbdp-msg error stripe-errors" style="display:none;"></div>
*/
