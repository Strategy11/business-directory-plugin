<?php do_action( 'wpbdp_before_submit_listing_page', $listing ); ?>

<div id="wpbdp-submit-listing" class="wpbdp-submit-page wpbdp-page" data-breakpoints='{"tiny": [0,410], "small": [410,560], "medium": [560,710], "large": [710,999999]}' data-breakpoints-class-prefix="wpbdp-submit-page">
    <form action="" method="post" data-ajax-url="<?php echo wpbdp_ajax_url(); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field( 'listing submit' ); ?>
        <input type="hidden" name="listing_id" value="<?php echo $listing->get_id(); ?>" />
        <input type="hidden" name="editing" value="<?php echo $editing ? '1' : '0'; ?>" />
        <input type="hidden" name="save_listing" value="1" />
        <input type="hidden" name="reset" value="" />
        <input type="hidden" name="current_section" value="<?php echo esc_attr( $submit->current_section ); ?>" />

            <h3><?php echo esc_html_x( 'Add Listing', 'view', 'business-directory-plugin' ); ?></h3>
            <?php $submit->render_rootline(); ?>
            <?php echo $messages['general']; ?>

            <?php foreach ( $sections as $section ): ?>
                <?php echo wpbdp_render(
                    'submit-listing-section',
                    array(
                        'section'  => $section,
                        'listing'  => $listing,
                        'messages' => ( ! empty( $messages[ $section['id'] ] ) ? $messages[ $section['id'] ] : '' ),
                        'is_admin' => $is_admin,
                        'submit'   => $submit,
                        'editing'  => $editing
                    )
                );
                ?>
            <?php endforeach; ?>
    </form>
</div>
<?php do_action( 'wpbdp_after_submit_listing_page', $listing ); ?>
