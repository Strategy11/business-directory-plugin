<div class="wpbdp-submit-listing-section wpbdp-submit-listing-section-<?php echo esc_attr( $section['id'] ); ?> <?php echo esc_attr( implode( ' ', $section['flags'] ) ); ?>" data-section-id="<?php echo esc_attr( $section['id'] ); ?>">
    <div class="wpbdp-submit-listing-section-header">
        <span class="collapse-indicator collapsed">►</span><span class="collapse-indicator expanded">▼</span><span class="title"><?php echo esc_html( $section['title'] ); ?></span>
    </div>
    <div class="wpbdp-submit-listing-section-content">
        <?php if ( $messages ): ?>
            <div class="wpbdp-submit-listing-section-messages">
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
        <div class="wpbdp-submit-listing-form-actions">
        <?php if ( ! empty( $section['prev_section'] ) ): ?>
            <?php if ( empty( $section['next_section'] ) ): ?>
                <button class="submit-back-button" data-previous-section="<?php echo esc_attr( $section['prev_section'] ); ?>"><?php esc_html_e( 'Back', 'business-directory-plugin' ); ?></button>
                <?php if ( $is_admin || ! wpbdp_payments_possible() || $submit->skip_plan_payment ): ?>
                <input type="submit" value="<?php _ex( 'Complete Listing', 'submit listing', 'business-directory-plugin' ); ?>" id="wpbdp-submit-listing-submit-btn" />
                <?php else: ?>
                    <?php if ( $editing ): ?>
                    <input type="submit" value="<?php _ex( 'Save Changes', 'submit listing', 'business-directory-plugin' ); ?>" id="wpbdp-submit-listing-submit-btn" />
                    <?php else: ?>
                    <input type="submit" value="<?php _ex( 'Continue to Payment', 'submit listing', 'business-directory-plugin' ); ?>" id="wpbdp-submit-listing-submit-btn" />
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
