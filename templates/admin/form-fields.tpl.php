<?php
    $buttons = array(
		'addfield'    => array(
			'label' => __( 'Add New Form Field', 'business-directory-plugin' ),
			'url'   => wp_nonce_url( admin_url( 'admin.php?page=wpbdp_admin_formfields&action=addfield' ), 'editfield' ),
		),
		'previewform' => array(
			'label' => __( 'Preview Form', 'business-directory-plugin' ),
			'url'   => admin_url( 'admin.php?page=wpbdp_admin_formfields&action=previewform' ),
		),
		'updatetags'  => array(
			'label' => __( 'Manage Theme Tags', 'business-directory-plugin' ),
			'url'   => admin_url( 'admin.php?page=wpbdp_admin_formfields&action=updatetags' ),
		),
    );

    echo wpbdp_admin_header( null, null, $buttons );

	wpbdp_admin_notices();

    echo esc_html_x(
        'Here, you can create new fields for your listings, edit or delete existing ones, change the order and visibility of the fields as well as configure special options for them.',
        'form-fields admin',
        'business-directory-plugin'
    );
    ?>
               <br />
    <?php
    echo str_replace(
        '<a>',
        '<a href="https://businessdirectoryplugin.com/knowledge-base/manage-form-fields/" target="_blank" rel="noopener">',
        _x(
            'Please see the <a>Form Fields documentation</a> for more details.',
            'form-fields admin',
            'business-directory-plugin'
        )
    );
    ?>

    <?php $table->views(); ?>
    <?php $table->display(); ?>

<?php echo wpbdp_admin_footer(); ?>
