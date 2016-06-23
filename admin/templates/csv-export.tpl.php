<?php
    echo wpbdp_admin_header( null, 'csv-export' );
?>

<?php echo wpbdp_admin_notices(); ?>

<a name="exporterror"></a>
<div class="error" style="display: none;"><p>
<?php _ex( 'An unknown error occurred during the export. Please make sure you have enough free disk space and memory available to PHP. Check your error logs for details.',
           'admin csv-export',
           'WPBDM' ); ?>
</p></div>

<div class="step-1">

<div class="wpbdp-note"><p>
<?php
$notice = _x( "Please note that the export process is a resource intensive task. If your export does not succeed try disabling other plugins first and/or increasing the values of the 'memory_limit' and 'max_execution_time' directives in your server's php.ini configuration file.",
              'admin csv-export',
              'WPBDM' );
$notice = str_replace( array( 'memory_limit', 'max_execution_time' ),
                       array( '<a href="http://www.php.net/manual/en/ini.core.php#ini.memory-limit" target="_blank">memory_limit</a>',
                              '<a href="http://www.php.net/manual/en/info.configuration.php#ini.max-execution-time" target="_blank">max_execution_time</a>' ),
                       $notice );
echo $notice;
?>
</p>
</div>

<!--<h3><?php _ex('Export Configuration', 'admin csv-export', 'WPBDM'); ?></h3>-->
<form id="wpbdp-csv-export-form" action="" method="POST">
    
    <h2><?php _ex( 'Export settings', 'admin csv-export', 'WPBDM' ); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label> <?php _ex('Which listings to export?', 'admin csv-export', 'WPBDM'); ?></label>
            </th>
            <td>
                <select name="settings[listing_status]">
                    <option value="all"><?php _ex( 'All', 'admin csv-export', 'WPBDM' ); ?></option>
                    <option value="publish"><?php _ex( 'Active Only', 'admin csv-export', 'WPBDM' ); ?></option>
                    <option value="publish+draft"><?php _ex( 'Active + Pending Renewal', 'admin csv-export', 'WPBDM' ); ?></option>
                </select>
            </td>
        </tr>      
        <tr>
            <th scope="row">
                <label> <?php _ex('Export images?', 'admin csv-export', 'WPBDM'); ?></label>
            </th>
            <td>
                <label><input name="settings[export-images]"
                       type="checkbox"
                       value="1" /> <?php _ex('Export images', 'admin csv-export', 'WPBDM'); ?></label> <br />
                <span class="description">
                    <?php _ex( 'When checked, instead of just a CSV file a ZIP file will be generated with both a CSV file and listing images.', 'admin csv-export', 'WPBDM' ); ?>
                </span>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label> <?php _ex('Additional metadata to export:', 'admin csv-export', 'WPBDM'); ?></label>
            </th>
            <td>
                <label><input name="settings[generate-sequence-ids]"
                       type="checkbox"
                       value="1" /> <?php _ex('Include unique IDs for each listing (sequence_id column).', 'admin csv-export', 'WPBDM' ); ?></label><br />
                <span class="description">
                <strong><?php _ex( 'If you plan to re-import the listings into BD and don\'t want new ones created, select this option!', 'admin csv-export', 'WPBDM'); ?></strong>
                </span> <br /><br />

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

    <h2><?php _ex('CSV File Settings', 'admin csv-export', 'WPBDM'); ?></h2>
    <table class="form-table">
            <tr class="form-required">
                <th scope="row">
                    <label> <?php _ex('Column Separator', 'admin csv-export', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms', 'WPBDM'); ?>)</span></label>
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
                    <label> <?php _ex('Image Separator', 'admin csv-export', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms', 'WPBDM'); ?>)</span></label>
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
                    <label> <?php _ex('Category Separator', 'admin csv-export', 'WPBDM'); ?> <span class="description">(<?php _ex('required', 'admin forms', 'WPBDM'); ?>)</span></label>
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
        <?php echo submit_button( _x( 'Export Listings', 'admin csv-export', 'WPBDM' ), 'primary', 'do-export', false ); ?>
    </p>
</form>
</div>

<div class="step-2">
    <h2><?php _ex( 'Export in Progress...', 'admin csv-export', 'WPBDM' ); ?></h2>
    <p><?php _ex( 'Your export file is being prepared. Please <u>do not leave</u> this page until the export finishes.', 'admin csv-export', 'WPBDM' ); ?></p>
    
    <dl>
        <dt><?php _ex( 'No. of listings:', 'admin csv-export', 'WPBDM' ); ?></dt>
        <dd class="listings">?</dd>
        <dt><?php _ex( 'Approximate export file size:', 'admin csv-export', 'WPBDM' ); ?></dt>
        <dd class="size">?</dd> 
    </dl>
    
    <div class="export-progress"></div>
    
    <p class="submit">
        <a href="#" class="cancel-import button"><?php _ex( 'Cancel Export', 'admin csv-export', 'WPBDM' ); ?></a>
    </p>
</div>

<div class="step-3">
    <h2><?php _ex( 'Export Complete', 'admin csv-export' )?></h2>
    <p><?php _ex( 'Your export file has been successfully created and it is now ready for download.', 'admin csv-export', 'WPBDM' ); ?></p>
    <div class="download-link">
        <a href="" class="button button-primary">
            <?php echo sprintf( _x( 'Download %s (%s)', 'admin csv-export', 'WPBDM' ),
                                '<span class="filename"></span>',
                                '<span class="filesize"></span>' ); ?>
        </a>
    </div>
    <div class="cleanup-link wpbdp-note">
        <p><?php _ex( 'Click "Cleanup" once the file has been downloaded in order to remove all temporary data created by Business Directory during the export process.', 'admin csv-export', 'WPBDM' ); ?><br />
        <a href="" class="button"><?php _ex( 'Cleanup', 'admin csv-export', 'WPBDM' ); ?></a></p>
    </div>    
</div>

<div class="canceled-export">
    <h2><?php _ex( 'Export Canceled', 'admin csv-export' )?></h2>
    <p><?php _ex( 'The export has been canceled.', 'admin csv-export', 'WPBDM' ); ?></p>
    <p><a href="" class="button"><?php _ex( '← Return to CSV Export', 'admin csv-export', 'WPBDM' ); ?></a></p>
</div>

<?php echo wpbdp_admin_footer(); ?>
