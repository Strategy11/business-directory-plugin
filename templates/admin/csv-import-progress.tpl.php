<div class="wpbdp-page-csv-import wpbdp-clearfix">

<div id="wpbdp-csv-import-fatal-error" class="error">
	<p class="with-reason" style="display: none;">
		<?php esc_html_e( 'A fatal error occurred during the import. The reason given was: "%s".', 'business-directory-plugin' ); ?>
	</p>

	<p class="no-reason" style="display: none;">
		<?php esc_html_e( 'A fatal error occurred during the import. If connection wasn\'t lost during the import, please make sure that you have enough free disk space and memory available to PHP. Check your error logs for details.', 'business-directory-plugin' ); ?>
	</p>

	<p><a href="" class="button"><?php esc_html_e( '← Return to CSV Import', 'business-directory-plugin' ); ?></a></p>
</div>

<!-- <screen: canceled import> -->
<div class="canceled-import">
	<h3><?php esc_html_e( 'Import Canceled', 'business-directory-plugin' ); ?></h3>
	<p><?php esc_html_e( 'The import has been canceled.', 'business-directory-plugin' ); ?></p>
	<p><a href="" class="button"><?php esc_html_e( '← Return to CSV Import', 'business-directory-plugin' ); ?></a></p>
</div>
<!-- </screen: canceled import> -->

<!-- <screen: import status> !-->
<div id="wpbdp-csv-import-state" data-import-id="<?php echo esc_attr( $import->get_import_id() ); ?>">
	<h3><?php esc_html_e( 'Import Progress', 'business-directory-plugin' ); ?></h3>

	<dl class="import-status">
		<dt><?php esc_html_e( 'Files', 'business-directory-plugin' ); ?></dt>
		<dd><?php echo implode( ', ', $sources ); ?></dd>

		<dt><?php esc_html_e( 'Rows in file', 'business-directory-plugin' ); ?></dt>
		<dd><?php echo esc_html( $import->get_import_rows_count() ); ?></dd>

		<dt><?php esc_html_e( 'Progress', 'business-directory-plugin' ); ?></dt>
		<dd>
			<div class="import-progress"></div>
			<div class="status-msg">
				<span class="not-started"><?php esc_html_e( 'Import has not started. Click "Start Import" to begin.', 'business-directory-plugin' ); ?></span>
				<span class="in-progress"><?php esc_html_e( 'Importing CSV file...', 'business-directory-plugin' ); ?></span>
			</div>
		</dd>
	</dl>

	<p class="submit">
		<a href="#" class="resume-import button button-primary"><?php esc_html_e( 'Start Import', 'business-directory-plugin' ); ?></a>
		<a href="#" class="cancel-import"><?php esc_html_e( 'Cancel Import', 'business-directory-plugin' ); ?></a>
	</p>
</div>
<!-- </screen: import status> !-->

<!-- <screen: import summary> ! -->
<div id="wpbdp-csv-import-summary">
	<h3><?php esc_html_e( 'Import finished', 'business-directory-plugin' ); ?></h3>

	<p class="no-warnings">
		<?php esc_html_e( 'Import was completed successfully.', 'business-directory-plugin' ); ?>
	</p>

	<p class="with-warnings">
		<?php esc_html_e( 'Import was completed but some rows were rejected.', 'business-directory-plugin' ); ?>
	</p>

	<h4><?php esc_html_e( 'Import Summary', 'business-directory-plugin' ); ?></h4>
	<dl>
		<dt><?php esc_html_e( 'Rows in file:', 'business-directory-plugin' ); ?></dt>
		<dd><?php echo esc_html( $import->get_import_rows_count() ); ?></dd>

		<dt><?php esc_html_e( 'Imported rows:', 'business-directory-plugin' ); ?></dt>
		<dd><span class="placeholder-imported-rows">0</span></dd>

		<dt><?php esc_html_e( 'Rejected rows:', 'business-directory-plugin' ); ?></dt>
		<dd><span class="placeholder-rejected-rows">0</span></dd>
	</dl>

	<div class="wpbdp-csv-import-warnings">
		<h4><?php esc_html_e( 'Import Warnings', 'business-directory-plugin' ); ?></h4>
		<table class="wp-list-table widefat">
			<thead><tr>
				<th class="col-line-no"><?php esc_html_e( 'Line #', 'business-directory-plugin' ); ?></th>
				<th class="col-line-content"><?php esc_html_e( 'Line', 'business-directory-plugin' ); ?></th>
				<th class="col-warning"><?php esc_html_e( 'Warning', 'business-directory-plugin' ); ?></th>
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

</div>
