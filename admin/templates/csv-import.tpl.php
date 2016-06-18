<?php
function _defaults_or( $defs, $k, $v ) {
    if ( array_key_exists( $k, $defs ) )
        return $defs[ $k ];

    return $v;
}
?>

<?php
echo wpbdp_admin_header(null, 'csv-import', array(
    array(_x('Help', 'admin csv-import', 'WPBDM'), '#help'),
    array(_x('See an example CSV import file', 'admin csv-import', 'WPBDM'), esc_url(add_query_arg('action', 'example-csv')))
    ) );
?>

<?php wpbdp_admin_notices(); ?>

<div class="wpbdp-note">
<p><?php
_ex( 'Here, you can import data into your directory using the CSV format.',
     'admin csv-import',
     'WPBDM' );
?><br />
<?php
echo str_replace(
    '<a>',
    '<a href="http://businessdirectoryplugin.com/docs/#admin-import" target="_blank">',
    _x( 'We strongly recommend reading our <a>CSV import documentation</a> first to help you do things in the right order.',
        'admin csv-import',
        'WPBDM' ) );
?></p>
</div>

<form id="wpbdp-csv-import-form" action="" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="do-import" />

    <h2><?php _ex('Import Files', 'admin csv-import', 'WPBDM' ); ?></h2>
    <table class="form-table">
        <tbody>
            <tr class="form-field form-required">
                <th scope="row">
                    <label> <?php _ex('CSV File', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex( 'required', 'admin forms', 'WPBDM' ); ?>)</span></label>
                </th>
                <td>
                    <input name="csv-file"
                           type="file"
                           aria-required="true" />

                    <?php if ( $files['csv'] ): ?>
                    <div class="file-local-selection">
                        <?php
                        echo str_replace( '<a>',
                                          '<a href="#" class="toggle-selection">',
                                          _x( '... or <a>select a file uploaded to the imports folder</a>', 'admin csv-import', 'WPBDM' ) );
                        ?>

                        <ul>
                            <?php foreach ( $files['csv'] as $f ): ?>
                            <li><label>
                                <input type="radio" name="csv-file-local" value="<?php echo basename( $f ); ?>" /> <?php echo basename( $f ); ?>
                            </label></li>
                            <?php endforeach; ?>
                            <li>
                                <label><input type="radio" name="csv-file-local" value="" class="dismiss" /> <?php _ex( '(Upload new file)', 'admin csv-import', 'WPBDM' ); ?></label>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
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

                    <?php if ( $files['images'] ): ?>
                    <div class="file-local-selection">
                        <?php
                        echo str_replace( '<a>',
                                          '<a href="#" class="toggle-selection">',
                                          _x( '... or <a>select a file uploaded to the imports folder</a>', 'admin csv-import', 'WPBDM' ) );
                        ?>

                        <ul>
                            <?php foreach ( $files['images'] as $f ): ?>
                            <li><label>
                                <input type="radio" name="images-file-local" value="<?php echo basename( $f ); ?>" /> <?php echo basename( $f ); ?>
                            </label></li>
                            <?php endforeach; ?>
                            <li>
                                <label><input type="radio" name="images-file-local" value="" class="dismiss" /> <?php _ex( '(Upload new file)', 'admin csv-import', 'WPBDM' ); ?></label>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
    </table>

    <h2><?php _ex('CSV File Settings', 'admin csv-import', 'WPBDM'); ?></h2>
    <table class="form-table">
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Column Separator', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex( 'required', 'admin forms', 'WPBDM' ); ?>)</span></label>
                </th>
                <td>
                    <input name="settings[csv-file-separator]"
                           type="text"
                           aria-required="true"
                           value="<?php echo _defaults_or( $defaults, 'csv-file-separator', ',' ); ?>" />
                </td>
            </tr>
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Image Separator', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms', 'WPBDM'); ?>)</span></label>
                </th>
                <td>
                    <input name="settings[images-separator]"
                           type="text"
                           aria-required="true"
                           value="<?php echo _defaults_or( $defaults, 'images-separator', ';' ); ?>" />
                </td>
            </tr>
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Category Separator', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms', 'WPBDM'); ?>)</span></label>
                </th>
                <td>
                    <input name="settings[category-separator]"
                           type="text"
                           aria-required="true"
                           value="<?php echo _defaults_or( $defaults, 'category-separator', ';' ); ?>" />
                </td>
            </tr>
    </table>

    <h2><?php _ex('Import settings', 'admin csv-import', 'WPBDM'); ?></h2>
    <table class="form-table">
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Post status of imported listings', 'admin csv-import', 'WPBDM'); ?></label>
                </th>
                <td>
                    <select name="settings[post-status]">
                        <?php foreach ( get_post_statuses() as $post_status => $post_status_label ): ?>
                        <option value="<?php echo $post_status; ?>" <?php echo _defaults_or( $defaults, 'post-status', 'publish' ) == $post_status ? 'selected="selected"' : ''; ?>><?php echo $post_status_label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Missing categories handling', 'admin csv-import', 'WPBDM'); ?> <span class="description">(<?php _ex( 'required', 'admin forms', 'WPBDM' ); ?>)</span></label>
                </th>
                <td>
                    <label><input name="settings[create-missing-categories]"
                           type="radio"
                           value="1" <?php echo ( _defaults_or( $defaults, 'create-missing-categories', 1 ) == 1 ) ? 'checked="checked"' : ''; ?> /> <?php _ex('Auto-create categories', 'admin csv-import', 'WPBDM'); ?></label>
                    <label><input name="settings[create-missing-categories]"
                           type="radio"
                           value="0" <?php echo ( _defaults_or( $defaults, 'create-missing-categories', 1 ) == 0 ) ? 'checked="checked"' : ''; ?> /> <?php _ex('Generate errors when a category is not found', 'admin csv-import', 'WPBDM'); ?></label>                           
                </td>
            </tr>
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex( 'Keep existing listing images?', 'admin csv-import', 'WPBDM' ); ?>
                </th>
                <td>
                    <label><input name="settings[append-images]"
                           type="checkbox"
                           value="1" checked="checked" /> <?php _ex( 'Keep existing images.', 'admin csv-import', 'WPBDM' ); ?></label>
                    <span class="description"><?php _ex( 'Appends new images while keeping current ones.', 'admin csv-import', 'WPBDM' ); ?></span>
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
                           value="1" <?php echo _defaults_or( $defaults, 'assign-listings-to-user', 1 ) ? 'checked="checked"' : ''; ?> /> <?php _ex('Assign listings to a user.', 'admin csv-import', 'WPBDM'); ?></label>
                </td>
            </tr>
            <tr class="form-required default-user-selection">
                <th scope="row">
                    <label> <?php _ex( 'Use a default user for listings?', 'admin csv-import', 'WPBDM' ); ?></label>
                </th>
                <td>
                    <label><input
                           type="checkbox"
                           class="use-default-listing-user"
                           value="1" <?php echo _defaults_or( $defaults, 'default-user', '' ) ? 'checked="checked"' : ''; ?> /> <?php _ex( 'Select a default user to be used if the username column is not present in the CSV file.', 'admin csv-import', 'WPBDM' ); ?></label>
                </td>
            </tr>
            <tr class="form-required default-user-selection">
                <th scope="row">
                    <label> <?php _ex('Default listing user', 'admin csv-import', 'WPBDM'); ?></label>
                </th>
                <td>
                    <label>
                        <?php echo wpbdp_render_user_field( array( 'class' => 'default-user', 'name' => 'settings[default-user]', 'value' => _defaults_or( $defaults, 'default-user', '' ) ) ); ?>
                    </label>
                    <span class="description"><?php _ex('This user will be used if the username column is not present in the CSV file.', 'admin csv-import', 'WPBDM'); ?></span>
                </td>
            </tr>
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex( 'Disable e-mail notifications during import?', 'admin csv-import', 'WPBDM' ); ?>
                </th>
                <td>
                    <label><input name="settings[disable-email-notifications]"
                           type="checkbox"
                           value="1" checked="checked" /> <?php _ex( 'Disable e-mail notifications.', 'admin csv-import', 'WPBDM' ); ?></label>
                </td>
            </tr>
    </table>

    <p class="submit">
        <?php echo submit_button(_x('Test Import', 'admin csv-import', 'WPBDM'), 'secondary', 'test-import', false); ?>
        <?php echo submit_button(_x('Import Listings', 'admin csv-import', 'WPBDM'), 'primary', 'do-import', false); ?>
    </p>
</form>

<hr />
<a name="help"></a>
<h2><?php _ex('Help', 'admin csv-import', 'WPBDM'); ?></h2>
<p>
    <?php echo sprintf(_x('The following are the valid header names to be used in the CSV file. Multivalued fields (such as category or tags) can appear multiple times in the file. Click <a href="%s">"See an example CSV import file"</a> to see how an import file should look like.', 'admin csv-import', 'WPBDM'),
                  esc_url(add_query_arg('action', 'example-csv'))); ?>
</p>

<table class="wpbdp-csv-import-headers">
    <thead>
        <tr>
            <th class="header-name"><?php _ex('Header name/label', 'admin csv-import', 'WPBDM'); ?></th>
            <th class="field-label"><?php _ex('Field', 'admin csv-import', 'WPBDM'); ?></th>
            <th class="field-type"><?php _ex('Type', 'admin csv-import', 'WPBDM'); ?></th>
            <th class="field-is-required"><?php _ex('Required?', 'admin csv-import', 'WPBDM'); ?></th>
            <th class="field-is-multivalued"><?php _ex('Multivalued?', 'admin csv-import', 'WPBDM'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php $i = 0; foreach ( wpbdp_get_form_fields() as $field ) : ?>
        <?php
            if ( 'custom' == $field->get_association() ):
                continue;
            endif
        ?>
        <tr class="<?php echo $i % 2 == 0 ? 'alt' : ''; ?>">
            <td class="header-name"><?php echo $field->get_short_name(); ?></td>
            <td class="field-label"><?php echo $field->get_label(); ?></td>
            <td class="field-type"><?php echo $field->get_field_type()->get_name(); ?></td>
            <td class="field-is-required"><?php echo $field->is_required() ? 'X' : ''; ?></td>
            <td class="field-is-multivalued">
                <?php echo ($field->get_association() == 'category' || $field->get_association() == 'tags') || ($field->get_field_type_id() == 'checkbox' || $field->get_field_type_id() == 'multiselect') ? 'X' : ''; ?>
            </td>
        </tr>
    <?php $i++; endforeach; ?>
        <tr class="<?php echo $i % 2 == 0 ? 'alt' : ''; ?>">
            <td class="header-name">images</td>
            <td class="field-label"><?php _ex('Semicolon separated list of listing images (from the ZIP file)', 'admin csv-import', 'WPBDM'); ?></td>
            <td class="field-type">-</td>
            <td class="field-is-required"></td>
            <td class="field-is-multivalued">X</td>
        </tr>
        <tr class="<?php echo ($i + 1) % 2 == 0 ? 'alt' : ''; ?>">
            <td class="header-name">username</td>
            <td class="field-label"><?php _ex('Listing author\'s username', 'admin csv-import', 'WPBDM'); ?></td>
            <td class="field-type">-</td>
            <td class="field-is-required"></td>
            <td class="field-is-multivalued"></td>
        </tr>
        <tr class="<?php echo ($i + 2) % 2 == 0 ? 'alt' : ''; ?>">
            <td class="header-name">sequence_id</td>
            <td class="field-label"><?php _ex( 'Internal Sequence ID used to allow listing updates from external sources.', 'admin csv-import', 'WPBDM' ); ?></td>
            <td class="field-type">-</td>
            <td class="field-is-required"></td>
            <td class="field-is-multivalued"></td>
        </tr>
        <tr class="<?php echo ($i + 3) % 2 == 0 ? 'alt' : ''; ?>">
            <td class="header-name">expires_on</td>
            <td class="field-label"><?php _ex( 'Date of listing expiration formatted as YYYY-MM-DD. Use this column when adding or updating listings from external sources.', 'admin csv-import', 'WPBDM' ); ?></td>
            <td class="field-type">-</td>
            <td class="field-is-required"></td>
            <td class="field-is-multivalued"></td>
        </tr>
    </tbody>
</table>

<?php echo wpbdp_admin_footer(); ?>
