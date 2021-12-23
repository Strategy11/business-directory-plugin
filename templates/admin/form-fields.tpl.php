<?php
    $buttons = array(
        _x( 'Add New Form Field', 'form-fields admin', 'business-directory-plugin' ) =>
            wp_nonce_url(
				admin_url( 'admin.php?page=wpbdp_admin_formfields&action=addfield' ),
				'editfield'
            ),
        __( 'Preview Form', 'business-directory-plugin' )      => admin_url( 'admin.php?page=wpbdp_admin_formfields&action=previewform' ),
        __( 'Manage Theme Tags', 'business-directory-plugin' ) => admin_url( 'admin.php?page=wpbdp_admin_formfields&action=updatetags' ),
    );

	WPBDP_Admin_Pages::show_tabs(
		array(
			'id'      => 'formfields',
			'sub'     => __( 'Form Fields', 'business-directory-plugin' ),
			'buttons' => $buttons,
		)
	);

	echo '<span class="howto">';
	esc_html_e(
        'Create new fields, edit existing fields, change the field order and visibility.',
        'business-directory-plugin'
	);

    echo ' ' . str_replace(
        '<a>',
        '<a href="https://businessdirectoryplugin.com/knowledge-base/manage-form-fields/" target="_blank" rel="noopener">',
        _x(
            'Please see the <a>Form Fields documentation</a> for more details.',
            'form-fields admin',
            'business-directory-plugin'
        )
    );
	echo '</span>';
    ?>

    <?php $table->views(); ?>
    <?php $table->display(); ?>

<?php echo wpbdp_admin_footer(); ?>
