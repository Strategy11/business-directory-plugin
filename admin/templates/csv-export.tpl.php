<?php
    echo wpbdp_admin_header();
?>

<?php wpbdp_admin_notices(); ?>

<form id="wpbdp-csv-export-form" action="" method="POST">
    <input type="hidden" name="action" value="do-export" />

    <h4><?php _ex('Export settings', 'admin csv-export', 'WPBDM'); ?></h4>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label> <?php _ex('Export images?', 'admin csv-export', 'WPBDM'); ?></label>
            </th>
            <td>
                <label><input name="settings[export-images]"
                       type="checkbox"
                       value="1" /> <?php _ex('Export images', 'admin csv-export', 'WPBDM'); ?></label> <br />
                <span class="description">
                    When checked, instead of just a CSV file a ZIP file will be generated with both a CSV file and listing images.
                </span>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label> <?php _ex('Additional metadata to export:', 'admin csv-export', 'WPBDM'); ?></label>
            </th>
            <td>
                <label><input name="settings[include-users]"
                       type="checkbox"
                       value="1"
                       checked="checked" /> <?php _ex('Author information (username)', 'admin csv-export', 'WPBDM'); ?></label> <br />

                <label><input name="settings[include-sticky-status]"
                       type="checkbox"
                       value="1"
                       checked="checked" /> <?php _ex('Sticky/featured status', 'admin csv-export', 'WPBDM'); ?></label> <br />

                <label><input name="settings[include-expiration-date]"
                       type="checkbox"
                       value="1"
                       checked="checked" /> <?php _ex('Listing expiration date', 'admin csv-export', 'WPBDM'); ?></label> <br />
            </td>
        </tr>
    </table>

    <h4><?php _ex('CSV File Settings', 'admin csv-export', 'WPBDM'); ?></h4>
    <table class="form-table">
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Column Separator', 'admin csv-export', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms'); ?>)</span></label>
                </th>
                <td>
                    <input name="settings[csv-file-separator]"
                           type="text"
                           aria-required="true"
                           value="," />
                </td>
            </tr>
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Image Separator', 'admin csv-export', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms'); ?>)</span></label>
                </th>
                <td>
                    <input name="settings[images-separator]"
                           type="text"
                           aria-required="true"
                           value=";" />
                </td>
            </tr>
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Category Separator', 'admin csv-export', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms'); ?>)</span></label>
                </th>
                <td>
                    <input name="settings[category-separator]"
                           type="text"
                           aria-required="true"
                           value=";" />
                </td>
            </tr>
    </table>

    <p class="submit">
        <?php echo submit_button( _x( 'Test Export', 'admin csv-export', 'WPBDM' ), 'secondary', 'test-export', false ); ?>
        <?php echo submit_button( _x( 'Export Listings', 'admin csv-export', 'WPBDM' ), 'primary', 'do-export', false ); ?>
    </p>
</form>

<?php echo wpbdp_admin_footer(); ?>