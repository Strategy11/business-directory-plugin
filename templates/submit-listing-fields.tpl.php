<?php foreach ( $fields as $field ): ?>
    <?php echo $field->render( $field_values[ $field->get_id() ], 'submit' ); ?>
<?php endforeach; ?>
<?php do_action( 'wpbdp_view_submit_listing-after_fields', $listing ); ?>
