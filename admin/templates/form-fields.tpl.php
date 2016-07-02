<?php
    $buttons = array(
        array( _x('Add New Form Field', 'form-fields admin', 'WPBDM'), esc_url(add_query_arg('action', 'addfield')) ),
        array( _x('Preview Form', 'form-fields admin', 'WPBDM'), esc_url(add_query_arg('action', 'previewform')) ) );

    $buttons[] = array( _x( 'Manage Theme Tags', 'form-fields admin', 'WPBDM' ), esc_url( add_query_arg( 'action', 'updatetags' ) ) );

    echo wpbdp_admin_header( null, null, $buttons );
?>
    <?php wpbdp_admin_notices(); ?>

    <?php _ex( 'Here, you can create new fields for your listings, edit or delete existing ones, change the order and visibility of the fields as well as configure special options for them.',
               'form-fields admin',
               'WPBDM' ); ?><br />
    <?php
    echo str_replace( '<a>',
                      '<a href="http://businessdirectoryplugin.com/docs/#admin-form-fields" target="_blank">',
                      _x( 'Please see the <a>Form Fields documentation</a> for more details.',
                          'form-fields admin',
                          'WPBDM' ) ); ?>

    <?php $table->views(); ?>
    <?php $table->display(); ?>

<?php echo wpbdp_admin_footer(); ?>
