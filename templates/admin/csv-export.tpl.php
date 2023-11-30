<div class="wpbdp-page-csv-export wpbdp-admin-page-settings">

<div class="error" id="exporterror" style="display: none;"><p>
<?php
	esc_html_e( 'An unknown error occurred during the export. Please make sure you have enough free disk space and memory available to PHP. Check your error logs for details.', 'business-directory-plugin' ); ?>
</p></div>

<div class="step-1">

<p class="howto wpbdp-settings-subtab-description wpbdp-setting-description">
<?php
$notice = _x( 'Please note that the export process is a resource intensive task. If your export does not succeed try disabling other plugins first and/or increasing the values of the \'memory_limit\' and \'max_execution_time\' directives in your server\'s php.ini configuration file.', 'admin csv-export', 'business-directory-plugin' );
echo str_replace(
	array( 'memory_limit', 'max_execution_time' ),
	array(
		'<a href="http://www.php.net/manual/en/ini.core.php#ini.memory-limit" target="_blank" rel="noopener">memory_limit</a>',
		'<a href="http://www.php.net/manual/en/info.configuration.php#ini.max-execution-time" target="_blank" rel="noopener">max_execution_time</a>',
	),
	$notice
);
?>
</p>

<form id="wpbdp-csv-export-form" action="" method="POST">

	<div class="wpbdp-settings-form-title">
		<h3><?php esc_html_e( 'Export settings', 'business-directory-plugin' ); ?></h3>
	</div>
	<div class="form-table wpbdp-settings-form wpbdp-grid">
		<div class="wpbdp-setting-row">
			<div class="wpbdp-setting-label">
				<label for="wpbdp-listing-status">
					<?php esc_html_e( 'Which listings to export?', 'business-directory-plugin' ); ?>
				</label>
			</div>
			<select name="settings[listing_status]" id="wpbdp-listing-status">
				<option value="all"><?php esc_html_e( 'All', 'business-directory-plugin' ); ?></option>
				<option value="publish"><?php esc_html_e( 'Active Only', 'business-directory-plugin' ); ?></option>
				<option value="publish+draft"><?php esc_html_e( 'Active + Pending Renewal', 'business-directory-plugin' ); ?></option>
			</select>
		</div>
		<div class="wpbdp-setting-row wpdb-checkbox">
			<label>
				<input name="settings[export-images]"
					type="checkbox"
					value="1" />
				<?php esc_html_e( 'Export images', 'business-directory-plugin' ); ?>
			</label>
			<div class="wpbdp-setting-description">
				<?php esc_html_e( 'Create a ZIP file with both a CSV file and listing images.', 'business-directory-plugin' ); ?>
			</div>
		</div>
		<div class="wpbdp-setting-row wpbdp-settings-multicheck-options">
			<div class="wpbdp-setting-label">
				<label><?php esc_html_e( 'Additional metadata to export', 'business-directory-plugin' ); ?></label>
			</div>
			<div class="wpbdp-settings-multicheck-option">
				<label>
					<input name="settings[generate-sequence-ids]"
						type="checkbox"
						value="1" />
					<?php esc_html_e( 'Include unique IDs for each listing (sequence_id column).', 'business-directory-plugin' ); ?>
				</label>
				<span class="wpbdp-setting-description">
					<?php esc_html_e( 'If you plan to re-import the listings into your directory and don\'t want new ones created, select this option!', 'business-directory-plugin' ); ?>
				</span>
			</div>

			<div class="wpbdp-settings-multicheck-option">
				<label>
					<input name="settings[include-users]"
						type="checkbox"
						value="1"
						checked="checked" />
					<?php esc_html_e( 'Author information (username)', 'business-directory-plugin' ); ?>
				</label>
			</div>

			<div class="wpbdp-settings-multicheck-option">
				<label>
					<input name="settings[include-expiration-date]"
						type="checkbox"
						value="1"
						checked="checked" />
					<?php esc_html_e( 'Listing expiration date', 'business-directory-plugin' ); ?>
				</label>
			</div>

			<div class="wpbdp-settings-multicheck-option">
				<label>
					<input name="settings[include-created-date]"
						type="checkbox"
						value="1" />
						<?php esc_html_e( 'Listing created date', 'business-directory-plugin' ); ?>
				</label>
			</div>

			<div class="wpbdp-settings-multicheck-option">
				<label>
					<input name="settings[include-modified-date]"
						type="checkbox"
						value="1" />
					<?php esc_html_e( 'Listing last updated date', 'business-directory-plugin' ); ?>
				</label>
			</div>

			<div class="wpbdp-settings-multicheck-option">
				<label>
					<input name="settings[include-tos-acceptance-date]"
						type="checkbox"
						value="1" />
					<?php esc_html_e( 'Listing T&C acceptance date', 'business-directory-plugin' ); ?>
				</label>
			</div>
		</div>
	</div>

	<div class="wpbdp-settings-form-title">
		<h3><?php esc_html_e( 'CSV File Settings', 'business-directory-plugin' ); ?></h3>
	</div>
	<div class="form-table wpbdp-settings-form wpbdp-grid">
		<div class="wpbdp-setting-row form-required">
			<div class="wpbdp-setting-label">
				<label for="settings[target-os]">
					<?php esc_html_e( 'What operating system will you use to edit the CSV file?', 'business-directory-plugin' ); ?> *
				</label>
			</div>
			<div class="wpbdp-setting-description">
				<?php esc_html_e( 'Windows and macOS versions of MS Excel handle CSV files differently. To make sure all your listings information is displayed properly when you view or edit the CSV file, we need to generate different versions of the file for each operating system.', 'business-directory-plugin' ); ?>
			</div>
			<label>
				<input name="settings[target-os]"
					type="radio"
					aria-required="true"
					value="windows"
					checked="checked" />
				<?php esc_html_e( 'Windows', 'business-directory-plugin' ); ?>
			</label>
			<br />
			<label>
				<input name="settings[target-os]"
					type="radio"
					aria-required="true"
					value="macos" />
				<?php esc_html_e( 'macOS', 'business-directory-plugin' ); ?>
			</label>
		</div>
		<div class="wpbdp-setting-row form-required wpbdp6">
			<div class="wpbdp-setting-label">
				<label><?php esc_html_e( 'Image Separator', 'business-directory-plugin' ); ?> *</label>
			</div>
			<input name="settings[images-separator]"
				type="text"
				aria-required="true"
				value=";" />
		</div>
		<div class="wpbdp-setting-row form-required wpbdp6">
			<div class="wpbdp-setting-label">
				<label><?php esc_html_e( 'Category Separator', 'business-directory-plugin' ); ?> *</label>
			</div>
			<input name="settings[category-separator]"
				type="text"
				aria-required="true"
				value=";" />
		</div>
	</div>

	<p class="submit">
		<?php submit_button( _x( 'Export Listings', 'admin csv-export', 'business-directory-plugin' ), 'primary', 'do-export', false ); ?>
	</p>
</form>
</div>

<div class="step-2">
	<h2><?php esc_html_e( 'Export in Progress...', 'business-directory-plugin' ); ?></h2>
	<p><?php esc_html_e( 'Your export file is being prepared. Please <u>do not leave</u> this page until the export finishes.', 'business-directory-plugin' ); ?></p>

	<dl>
		<dt><?php esc_html_e( 'No. of listings:', 'business-directory-plugin' ); ?></dt>
		<dd class="listings">?</dd>
		<dt><?php esc_html_e( 'Approximate export file size:', 'business-directory-plugin' ); ?></dt>
		<dd class="size">?</dd>
	</dl>

	<div class="export-progress"></div>

	<p class="submit">
		<a href="#" class="cancel-import button"><?php esc_html_e( 'Cancel Export', 'business-directory-plugin' ); ?></a>
	</p>
</div>

<div class="step-3">
	<h2><?php esc_html_e( 'Export Complete', 'business-directory-plugin' ); ?></h2>
	<p><?php esc_html_e( 'Your export file has been successfully created and it is now ready for download.', 'business-directory-plugin' ); ?></p>
	<div class="download-link">
		<a href="" class="button button-primary">
			<?php
			printf(
				esc_html_x( 'Download %1$s (%2$s)', 'admin csv-export', 'business-directory-plugin' ),
				'<span class="filename"></span>',
				'<span class="filesize"></span>'
			);
			?>
		</a>
	</div>
	<div class="cleanup-link wpbdp-note">
		<p><?php esc_html_e( 'Click "Cleanup" once the file has been downloaded in order to remove all temporary data created by Business Directory during the export process.', 'business-directory-plugin' ); ?><br />
		<a href="" class="button"><?php esc_html_e( 'Cleanup', 'business-directory-plugin' ); ?></a></p>
	</div>
</div>

<div class="canceled-export">
	<h2><?php esc_html_e( 'Export Canceled', 'business-directory-plugin' ); ?></h2>
	<p><?php esc_html_e( 'The export has been canceled.', 'business-directory-plugin' ); ?></p>
	<p><a href="" class="button"><?php esc_html_e( '← Return to CSV Export', 'business-directory-plugin' ); ?></a></p>
</div>

</div>
