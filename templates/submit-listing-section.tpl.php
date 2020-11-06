<div class="wpbdp-submit-listing-section wpbdp-submit-listing-section-<?php echo esc_attr( $section['id'] ); ?> <?php echo esc_attr( implode( ' ', $section['flags'] ) ); ?>" data-section-id="<?php echo esc_attr( $section['id'] ); ?>">
    <div class="wpbdp-submit-listing-section-header">
        <span class="collapse-indicator collapsed">►</span><span class="collapse-indicator expanded">▼</span><span class="title"><?php echo esc_html( $section['title'] ); ?></span>
    </div>
    <div class="wpbdp-submit-listing-section-content <?php echo ! empty( $section['content_css_classes'] ) ? esc_attr( $section['content_css_classes'] ) : ''; ?>">
        <?php if ( $messages ): ?>
            <div class="wpbdp-submit-listing-section-messages wpbdp-full">
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $messages;
                ?>
            </div>
        <?php endif; ?>

        <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $section['html'];
        ?>
        <div class="wpbdp-submit-listing-form-actions wpbdp-full">
        <?php if ( ! empty( $section['prev_section'] ) ): ?>
            <?php if ( empty( $section['next_section'] ) ): ?>
                <button class="submit-back-button" data-previous-section="<?php echo esc_attr( $section['prev_section'] ); ?>"><?php esc_html_e( 'Back', 'business-directory-plugin' ); ?></button>
                <?php if ( $is_admin || ! wpbdp_payments_possible() || $submit->skip_plan_payment ): ?>
                    <button type="submit" id="wpbdp-submit-listing-submit-btn"><?php esc_html_e( 'Complete Listing', 'business-directory-plugin' ); ?></button>
                <?php else: ?>
                    <?php if ( $editing ): ?>
                    <button type="submit" id="wpbdp-submit-listing-submit-btn"><?php esc_html_e( 'Save Changes', 'business-directory-plugin' ); ?></button>
                    <?php else: ?>
                    <button type="submit" id="wpbdp-submit-listing-submit-btn"><?php esc_html_e( 'Continue to Payment', 'business-directory-plugin' ); ?></button>
                    <?php endif; ?>
                <?php endif; ?>
            <?php else : ?>
                <button class="submit-back-button" data-previous-section="<?php echo esc_attr( $section['prev_section'] ); ?>"><?php esc_html_e( 'Back', 'business-directory-plugin' ); ?></button>
                <button class="submit-next-button" data-next-section="<?php echo esc_attr( $section['next_section'] ); ?>"><?php esc_html_e( 'Next', 'business-directory-plugin' ); ?></button>
            <?php endif; ?>
        <?php else : ?>
            <button class="submit-next-button" data-next-section="<?php echo esc_attr( $section['next_section'] ); ?>"><?php esc_html_e( 'Next', 'business-directory-plugin' ); ?></button>
        <?php endif; ?>
        </div>
    </div>
</div>
