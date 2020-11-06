<?php
$args = array( 'listing' => $listing );
?>

<?php foreach ( $fields as $field ): ?>
    <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $field->render( $field_values[ $field->get_id() ], 'submit', $listing );;
    ?>
<?php endforeach; ?>

<?php do_action( 'wpbdp_view_submit_listing-after_fields', $listing ); ?>

<a class="reset wpbdp-full" href="#"><?php echo esc_html_x( 'Clear Form', 'submit listing', 'business-directory-plugin' ); ?></a>
