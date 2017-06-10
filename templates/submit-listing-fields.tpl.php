<?php
// TODO: We might want to integrate the validation part inside the $field itself.
?>

<?php foreach ( $fields as $field ): ?>
    <?php $field_output = $field->render( $field_values[ $field->get_id() ], 'submit', $listing ); ?>
    <?php $field_errors = ! empty( $validation_errors[ $field->get_id() ] ) ? $validation_errors[ $field->get_id() ] : false; ?>

    <?php if ( $field_errors ): ?>
    <div class="wpbdp-form-field-validation-error-wrapper">
        <div class="wpbdp-form-field-validation-errors wpbdp-clearfix">
            <!-- <span class="dashicons dashicons&#45;warning"></span> -->
            <?php echo implode( '<br />', $field_errors ); ?>
        </div>
        <?php echo $field_output; ?>
    </div>
    <?php else: ?>
        <?php echo $field_output; ?>
    <?php endif; ?>
<?php endforeach; ?>

<?php do_action( 'wpbdp_view_submit_listing-after_fields', $listing ); ?>
