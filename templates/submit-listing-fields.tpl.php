<?php
$args = array( 'listing' => $listing );
?>

<?php foreach ( $fields as $field ): ?>
    <?php $args['field_errors'] = ! empty( $validation_errors[ $field->get_id() ] ) ? $validation_errors[ $field->get_id() ] : false; ?>
    <?php $field_output = $field->render( $field_values[ $field->get_id() ], 'submit', $args ); ?>
    <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $field_output;
        ?>
<?php endforeach; ?>

<?php do_action( 'wpbdp_view_submit_listing-after_fields', $listing ); ?>

<a class="reset" href="#"><?php echo esc_html_x( 'Clear Form', 'submit listing', 'business-directory-plugin' ); ?></a>
