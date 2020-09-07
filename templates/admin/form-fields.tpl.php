<?php
    $buttons = array(
        array(
            _x( 'Add New Form Field', 'form-fields admin', 'business-directory-plugin' ),
            esc_url(
                wpbdp_url(
                    'admin',
                    array(
                        'page'   => 'wpbdp_admin_formfields',
                        'action' => 'addfield',
                    )
                )
            ),
        ),
        array(
            _x( 'Preview Form', 'form-fields admin', 'business-directory-plugin' ),
            esc_url(
                wpbdp_url(
                    'admin',
                    array(
                        'page'   => 'wpbdp_admin_formfields',
                        'action' => 'previewform',
                    )
                )
            ),
        ),
        array(
            _x( 'Manage Theme Tags', 'form-fields admin', 'business-directory-plugin' ),
            esc_url(
                wpbdp_url(
                    'admin',
                    array(
                        'page'   => 'wpbdp_admin_formfields',
                        'action' => 'previewform',
                    )
                )
            ),
        ),
    );

    echo wpbdp_admin_header( null, null, $buttons );
    ?>
    <?php wpbdp_admin_notices(); ?>

    <?php
    _ex(
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
