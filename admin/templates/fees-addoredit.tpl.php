<?php
if (!function_exists('_wpbdp_render_category')) {
function _wpbdp_render_category($cat, $selected=array(), $level=0) {
    $html = '';

    $level_string = str_repeat('&mdash;&nbsp;', $level);
    $html .= sprintf('<option value="%s" %s>%s%s</option>', $cat->term_id,
                     in_array($cat->term_id, $selected) ? 'selected="selected"' : '',
                     $level_string, $cat->name);

    if ($cat->subcategories) {
        foreach ($cat->subcategories as $subcat) {
            $html .= _wpbdp_render_category($subcat, $selected, $level+1);
        }
    }

    return $html;   
}
}
?>

<?php
echo wpbdp_admin_header( $fee->is_new() ? _x( 'Add Listing Fee', 'fees admin', 'WPBDM' ) : _x( 'Edit Listing Fee', 'fees admin', 'WPBDM' ) );
?>
<?php wpbdp_admin_notices(); ?>

<form id="wpbdp-fee-form" action="" method="POST">
    <?php if ( $fee->id ): ?>
    <input type="hidden" name="fee[id]" value="<?php echo $fee->id; ?>" />
    <?php endif; ?>
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
            <tr class="form-field form-required">
                <th scope="row">
                    <label> <?php _ex('Fee Amount', 'fees admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
                </th>
                <td>
                    <?php if ( 'free' == $fee->tag ): ?>
                    0.0
                    <?php else: ?>
                    <input name="fee[amount]"
                           type="text"
                           aria-required="true"
                           value="<?php echo floatval( $fee->amount ); ?>"
                           style="width: 100px;" />
                    <?php endif; ?>
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
                    <label> <?php _ex('Apply to category', 'fees admin', 'WPBDM'); ?> <span class="description">(required)</span></label>
                </th>
                <td>
                    <?php if ( 'free' == $fee->tag ): ?>
                        <?php _ex('* All Categories *', 'fees admin', 'WPBDM'); ?>
                    <?php else: ?>
                    <select name="fee[categories][categories][]" multiple="multiple" size="10">
                        <option value="0" <?php echo ( $fee->categories['all'] ) ? 'selected="selected"' : ''; ?>><?php _ex('* All Categories *', 'fees admin', 'WPBDM'); ?></option>
                        <?php
                        $directory_categories = wpbdp_categories_list();

                        foreach ($directory_categories as &$dir_category) {
                            echo _wpbdp_render_category($dir_category, $fee->categories['categories']);
                        }
                        ?>
                    </select>
                    <?php endif; ?>
                </td>
            </tr>
    </table>

    <?php if ( $fee_extra_settings ): ?>
    <div class="extra-settings">
    <?php echo $fee_extra_settings; ?>
    </div>
    <?php endif; ?>

    <?php echo submit_button( $fee->is_new() ? _x('Add Fee', 'fees admin', 'WPBDM') : _x('Update Fee', 'fees admin', 'WPBDM' ) ); ?>
</form>

<?php
echo wpbdp_admin_footer();
?>
