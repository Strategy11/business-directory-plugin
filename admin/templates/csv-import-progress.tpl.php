<?php echo wpbdp_admin_header( null, 'csv-import' ); ?>
<?php echo wpbdp_admin_notices(); ?>

<div id="wpbdp-csv-import-fatal-error" class="error">
    <p class="with-reason" style="display: none;">
        <?php _ex( 'A fatal error occurred during the import. The reason given was: "%s".', 'admin csv-import', 'WPBDM' ); ?>
    </p>

    <p class="no-reason" style="display: none;">
        <?php _ex( 'A fatal error occurred during the import. If connection wasn\'t lost during the import, please make sure that you have enough free disk space and memory available to PHP. Check your error logs for details.', 'admin csv-import', 'WPBDM' ); ?>
    </p>

    <p class="last-imported-line">
        <?php _ex( 'Last imported line from file:', 'admin csv-import', 'WPBDM' ); ?>
        <span class="placeholder">0</span>.
    </P>

    <p><a href="" class="button"><?php _ex( '← Return to CSV Import', 'admin csv-import', 'WPBDM' ); ?></a></p>
</div>

<!-- <screen: canceled import> -->
<div class="canceled-import">
    <h3><?php _ex( 'Import Canceled', 'admin csv-import', 'WPBDM' ); ?></h3>
    <p><?php _ex( 'The import has been canceled.', 'admin csv-import', 'WPBDM' ); ?></p>
    <p><a href="" class="button"><?php _ex( '← Return to CSV Import', 'admin csv-import', 'WPBDM' ); ?></a></p>
</div>
<!-- </screen: canceled import> -->

<!-- <screen: import status> !-->
<div id="wpbdp-csv-import-state" data-import-id="<?php echo $import->get_import_id(); ?>">
    <h3><?php _e( 'Import Progress', 'admin csv-import', 'WPBDM' ); ?></h3>

    <dl class="import-status">
        <dt><?php _ex( 'Files', 'admin csv-import', 'WPBDM' ); ?></dt>
        <dd><?php echo implode( ', ', $sources ); ?></dd>
        
        <dt><?php _ex( 'Rows in file', 'admin csv-import', 'WPBDM' ); ?></dt>
        <dd><?php echo $import->get_import_rows_count(); ?></dd>

        <dt><?php _ex( 'Progress', 'admin csv-import', 'WPBDM' ); ?></dt>
        <dd>
            <div class="import-progress"></div>
            <div class="status-msg">
                <span class="not-started"><?php _ex( 'Import has not started. Click "Start Import" to begin.', 'admin csv-import', 'WPBDM' ); ?></span>
                <span class="in-progress"><?php _ex( 'Importing CSV file...', 'admin csv-import', 'WPBDM' ); ?></span>
            </div>
        </dd>
    </dl>

    <p class="submit">
        <a href="#" class="resume-import button button-primary"><?php _ex( 'Start Import', 'admin csv-import', 'WPBDM' ); ?></a>
        <a href="#" class="cancel-import"><?php _ex( 'Cancel Import', 'admin csv-import', 'WPBDM' ); ?></a>
    </p>
</div>
<!-- </screen: import status> !-->

<!-- <screen: import summary> ! -->
<div id="wpbdp-csv-import-summary">
    <h3><?php _ex( 'Import finished', 'admin csv-import', 'WPBDM' ); ?></h3>

    <p class="no-warnings">
        <?php _ex( 'Import was completed successfully.', 'admin csv-import', 'WPBDM' ); ?>
    </p>

    <p class="with-warnings">
        <?php _ex( 'Import was completed but some rows were rejected.', 'admin csv-import', 'WPBDM' ); ?>
    </p>

    <h4><?php _ex( 'Import Summary', 'admin csv-import', 'WPBDM' ); ?></h4>
    <dl>
        <dt><?php _ex( 'Rows in file:', 'admin csv-import', 'WPBDM' ); ?></dt>
        <dd><?php echo $import->get_import_rows_count(); ?></dd>

        <dt><?php _ex( 'Imported rows:', 'admin csv-import', 'WPBDM' ); ?></dt>
        <dd><span class="placeholder-imported-rows">0</span></dd>

        <dt><?php _ex( 'Rejected rows:', 'admin csv-import', 'WPBDM' ); ?></dt>
        <dd><span class="placeholder-rejected-rows">0</span></dd>
    </dl>

    <div class="wpbdp-csv-import-warnings">
        <h4><?php _ex( 'Import Warnings', 'admin csv-import', 'WPBDM' ); ?></h4>
        <table class="wp-list-table widefat">
            <thead><tr>
                <th class="col-line-no"><?php _ex( 'Line #', 'admin csv-import', 'WPBDM' ); ?></th>
                <th class="col-line-content"><?php _ex( 'Line', 'admin csv-import', 'WPBDM' ); ?></th>
                <th class="col-warning"><?php _ex( 'Warning', 'admin csv-import', 'WPBDM' ); ?></th>
            </tr></thead>
            <tbody>
                <tr class="row-template" style="display: none;">
                    <td class="col-line-no">0</td>
                    <td class="col-line-content">...</td>
                    <td class="col-warning">...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<!-- </screen: import summary> ! -->

<?php echo wpbdp_admin_footer(); ?>
