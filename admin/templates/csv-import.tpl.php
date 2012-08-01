<?php
    echo wpbdp_admin_header(null, null, array(
        array(_x('See an example CSV import file', 'admin csv-import', 'WPBDM'), esc_url(add_query_arg('action', 'example-csv')))
        ) );
?>

<?php wpbdp_admin_notices(); ?>

<form id="wpbdp-csv-import-form" action="" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="do-import" />

    <h3><?php _ex('Import Files', 'admin csv-import'); ?></h3>
    <table class="form-table">
        <tbody>
            <tr class="form-field form-required">
                <th scope="row">
                    <label> <?php _ex('CSV File', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms'); ?>)</span></label>
                </th>
                <td>
                    <input name="csv-file"
                           type="file"
                           aria-required="true" />
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row">
                    <label> <?php _ex('ZIP file containing images', 'admin csv-import', 'WPBDM'); ?></label>
                </th>
                <td>
                    <input name="images-file"
                           type="file"
                           aria-required="true" />
                </td>
            </tr>            
    </table>

    <h3><?php _ex('CSV File Settings', 'admin csv-import', 'WPBDM'); ?></h3>
    <table class="form-table">
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Column Separator', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms'); ?>)</span></label>
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
                    <label> <?php _ex('Image Separator', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms'); ?>)</span></label>
                </th>
                <td>
                    <input name="settings[images-separator]"
                           type="text"
                           aria-required="true"
                           value=";" />
                </td>
            </tr>            
    </table>

    <h3><?php _ex('Import settings', 'admin csv-import', 'WPBDM'); ?></h3>
    <table class="form-table">
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Missing categories handling', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms'); ?>)</span></label>
                </th>
                <td>
                    <label><input name="settings[create-missing-categories]"
                           type="radio"
                           value="1" checked="checked" /> <?php _ex('Auto-create categories', 'admin csv-import', 'WPBDM'); ?></label>
                    <label><input name="settings[create-missing-categories]"
                           type="radio"
                           value="0" /> <?php _ex('Generate errors when a category is not found', 'admin csv-import', 'WPBDM'); ?></label>                           
                </td>
            </tr>
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Assign listings to a user?', 'admin csv-import', 'WPBDM'); ?>
                </th>
                <td>
                    <label><input name="settings[assign-listings-to-user]"
                           type="checkbox"
                           class="assign-listings-to-user"
                           value="1" checked="checked" /> <?php _ex('Assign listings to a user.', 'admin csv-import', 'WPBDM'); ?></label>
                </td>
            </tr>
            <tr class="form-required default-user-selection">
                <th scope="row">
                    <label> <?php _ex('Default listing user', 'admin csv-import', 'WPBDM'); ?>
                </th>
                <td>
                    <label>
                        <select name="settings[default-user]" class="default-user">
                            <option value="0"><?php _ex('Use spreadsheet information only.', 'admin csv-import', 'WPBDM'); ?></option>
                            <?php foreach (get_users('orderby=display_name') as $user): ?>
                            <option value="<?php echo $user->ID; ?>"><?php echo $user->display_name; ?> (<?php echo $user->user_login; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <span class="description"><?php _ex('This user will be used if the username column is not present in the CSV file.', 'admin csv-import', 'WPBDM'); ?></span>
                </td>
            </tr>            
    </table>

    <?php echo submit_button(_x('Import Listings', 'admin csv-import', 'WPBDM')); ?>
</form>

<?php echo wpbdp_admin_footer(); ?>