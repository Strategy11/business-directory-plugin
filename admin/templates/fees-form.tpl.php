<form id="wpbdp-fee-form" action="" method="post">
    <!--<input type="hidden" name="fee[id]" value="<?php echo $fee->id; ?>" />-->

    <table class="form-table">
        <tbody>
            <tr class="form-field form-required">
                <th scope="row">
                    <label> <?php _ex('Fee Label', 'fees admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
                </th>
                <td>
                    <input name="fee[label]"
                           type="text"
                           aria-required="true"
                           value="<?php echo esc_attr( $fee->label ); ?>" />
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row">
                    <label> <?php _ex( 'Fee Description', 'fees admin', 'WPBDM' ); ?></label>
                </th>
                <td>
                    <textarea name="fee[description]" rows="5" cols="50"><?php echo esc_textarea( $fee->description ); ?></textarea>
                </td>
            </tr>
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Listing run in days', 'fees admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
                </th>
                <td>
                    <input type="radio" id="wpbdp-fee-form-days" name="_days" value="1" <?php echo absint($fee->days ) > 0 ? 'checked="checked"' : ''; ?>/> <label for="wpbdp-fee-form-days"><?php _ex('run listing for', 'fees admin', 'WPBDM'); ?></label>
                    <input id="wpbdp-fee-form-days-n"
                           type="text"
                           aria-required="true"
                           value="<?php echo absint( $fee->days ); ?>"
                           style="width: 80px;"
                           name="fee[days]"
                           <?php echo ( absint( $fee->days ) == 0 ) ? 'disabled="disabled"' : ''; ?>
                           />
                    <?php _ex('days', 'fees admin', 'WPBDM'); ?>
                    <span class="description">-- or --</span>

                    <input type="radio" id="wpbdp-fee-form-days-0" name="_days" value="0" <?php echo ( absint( $fee->days ) == 0 ) ? 'checked="checked"' : ''; ?>/> <label for="wpbdp-fee-form-days-0"><?php _ex('run listing forever', 'fees admin', 'WPBDM'); ?></label>                 
                </td>
            </tr>
            <tr class="form-field form-required">
                <th scope="row">
                    <label> <?php _ex('Number of images allowed', 'fees admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
                </th>
                <td>
                    <input name="fee[images]"
                           type="text"
                           aria-required="true"
                           value="<?php echo absint( $fee->images ); ?>"
                           style="width: 80px;" />
                </td>
            </tr>
            <tr class="form-field form-required">
                <th scope="row">
                    <label> <?php _ex('Is recurring?', 'fees admin', 'WPBDM'); ?></label>
                </th>
                <td>
                    <input name="fee[recurring]"
                           type="checkbox"
                           value="1"
                           <?php echo $fee->recurring ? 'checked="checked"' : ''; ?>
                           <?php echo ( 'free' == $fee->tag ) ? 'disabled="disabled"' : ''; ?> />
                </td>
            </tr>
            <tr class="form-field form-required">
                <th scope="row">
                    <label> <?php _ex('Is featured listing/sticky?', 'fees admin', 'WPBDM'); ?></label>
                </th>
                <td>
                    <input name="fee[sticky]"
                           type="checkbox"
                           value="1"
                           <?php echo $fee->sticky ? 'checked="checked"' : ''; ?>
                           <?php echo ( 'free' == $fee->tag ) ? 'disabled="disabled"' : ''; ?> />
                    <span class="description"><?php _ex( 'This floats the listing to the top of search results and browsing the directory when the user buys this plan.', 'fees admin', 'WPBDM' ); ?></span>
                </td>
            </tr>
            <tr class="form-field form-required">
                <th scope="row">
                    <label><?php _ex( 'Listing background color:', 'fees admin', 'WPBDM' ); ?></label>
                </th>
                <td>
                    <div id="fee-bgcolor-picker">
                        <input name="fee[extra_data][bgcolor]" id="fee-bgcolor-value" value="<?php echo isset( $fee->extra_data['bgcolor'] ) ? $fee->extra_data['bgcolor'] : ''; ?>" size="4" />

                        <div class="color-selection">
                            <div class="color-selection-btns">
                                <a href="#" class="reset-btn"><span class="no-color-img"></span> <?php _ex( 'Reset Color', 'fees admin', 'WPBDM'); ?></a>
                                <a href="#" class="close-btn">✖</a>
                            </div>
                            <div id="fee-bgcolor-picker-iris"></div>
                        </div>
                    </div>

                    <span class="description"><?php _ex( 'Used to differentiate listings inside this plan from others.', 'fees admin', 'WPBDM' ); ?></span>
                </td>
            </tr>
            <tr class="form-field limit-categories">
                <th scope="row">
                    <label><?php _ex( 'Plan Category Policy:', 'fees admin', 'WPBDM' ); ?></label>
                </th>
                <td>
                    <select name="limit_categories" class="wpbdp-js-toggle">
                        <option value="0"><?php _ex( 'Plan applies to all categories', 'fees admin', 'WPBDM' ); ?></option>
                        <option value="1" <?php selected( is_array( $fee->supported_categories ), true ); ?> data-toggles="limit-categories-list" class="wpbdp-js-toggle" data-toggles="limit-categories-list"><?php _ex( 'Plan applies to only certain categories', 'fees admin', 'WPBDM' ); ?></option>
                    </select>

                    <div id="limit-categories-list" class="<?php echo is_array( $fee->supported_categories ) ? '' : 'hidden'; ?>">
                        <p><span class="description"><?php _ex( 'Limit plan to the following categories:', 'fees admin', 'WPBDM' ); ?></span></p>

                        <?php
                        require_once( WPBDP_PATH . 'core/helpers/class-wp-taxonomy-term-list.php' );
                        $h = new WPBDP__WP_Taxonomy_Term_List( array( 'taxonomy' => WPBDP_CATEGORY_TAX,
                                                                      'input' => 'checkbox',
                                                                      'input_name' => 'fee[supported_categories]',
                                                                      'before' => '<div>',
                                                                      'after' => '</div>',
                                                                      'selected' => is_array( $fee->supported_categories ) ? $fee->supported_categories : array() ) );
                        $h->display();
                        ?>
                        </div>
                </td>
            </tr>
        </tbody>
    </table>

    <h2><?php _ex( 'Pricing', 'fees admin', 'WPBDM'); ?></h2>
    <table class="form-table">
        <tbody>
            <tr class="form-field pricing-info">
                <th scope="row">
                    <label><?php _ex( 'Pricing model', 'fees admin', 'WPBDM' ); ?>
                </th>
                <td>
                    <div class="pricing-options">
                        <label><input type="radio" class="wpbdp-js-toggle" data-toggles="pricing-details-flat" name="fee[pricing_model]" value="flat" <?php checked( $fee->pricing_model, 'flat' ); ?> /> <?php _ex( 'Flat price', 'fees admin', 'WPBDM' ); ?></label><br />
                        <label><input type="radio" class="wpbdp-js-toggle" data-toggles="pricing-details-variable" name="fee[pricing_model]" value="variable" <?php checked( $fee->pricing_model, 'variable' ); ?> /> <?php _ex( 'Different price for different categories', 'fees admin', 'WPBDM' ); ?></label><br />
                        <label><input type="radio" class="wpbdp-js-toggle" data-toggles="pricing-details-extra" name="fee[pricing_model]" value="extra" <?php checked( $fee->pricing_model, 'extra' ); ?> /> <?php _ex( 'Base price plus an extra amount per category', 'fees admin', 'WPBDM' ); ?></label>
                    </div>
                </td>
            </tr>
            <tr class="form-field fee-pricing-details pricing-details-flat pricing-details-extra <?php echo ( 'flat' == $fee->pricing_model || 'extra' == $fee->pricing_model ) ? '' : 'hidden'; ?>">
                <th scope="row">
                    <label><?php _ex( 'Fee Price', 'fees admin', 'WPBDM' ); ?></label>
                </th>
                <td>
                    <input type="text" name="fee[amount]" value="<?php echo $fee->amount; ?>" />
                </td>
            </tr>
            <tr class="form-field fee-pricing-details pricing-details-variable <?php echo 'variable' == $fee->pricing_model ? '' : 'hidden'; ?>">
                <th scope="row">
                    <label><?php _ex( 'Prices per category', 'fees admin', 'WPBDM' ); ?></label>
                </th>
                <td>
                    <table>
                        <thead>
                        <th><?php _ex( 'Category', 'fees admin', 'WPBDM' ); ?></th>
                        <th><?php _ex( 'Price', 'fees admin', 'WPBDM' ); ?></th>
                        </thead>
                        <tbody>
                            <?php
                            require_once( WPBDP_PATH . 'admin/helpers/class-variable-pricing-configurator.php' );
                            $c = new WPBDP__Admin__Variable_Pricing_Configurator( array( 'fee' => $fee ) );
                            $c->display();
                            ?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr class="form-field fee-pricing-details pricing-details-extra <?php echo 'extra' == $fee->pricing_model ? '' : 'hidden'; ?>">
                <th scope="row">
                    <label><?php _ex( 'Extra amount (per category)', 'fees admin', 'WPBDM' ); ?></label>
                </th>
                <td>
                    <input type="text" name="fee[pricing_details][extra]" value="<?php echo ( ! empty( $fee->pricing_details['extra'] ) ? $fee->pricing_details['extra'] : '' ); ?>" />
                </td>
            </tr>
        </tbody>
    </table>

    <?php echo submit_button( $fee->id ? _x( 'Save Changes', 'fees admin', 'WPBDM' ) : _x( 'Add Listing Fee', 'fees admin', 'WPBDM' ) ); ?>
</form>

