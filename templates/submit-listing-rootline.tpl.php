<div class="wpbdp-submit-rootline">
    <?php foreach ( array_keys( $sections ) as $id => $section_id ): ?>
        <?php $current = $section_id === $submit->current_section; ?>
        <?php $checked = $current || $submit->should_validate_section( $section_id ); ?>
        <div class="wpbdp-rootline-section wpbdp-submit-section-<?php echo esc_attr( $section_id ); ?> <?php echo $current ? 'wpbdp-submit-section-current' : ''; ?>" data-section-pos="<?php echo esc_attr( $id + 1 ); ?>">
            <div class='rootline-bar bar-right'></div>
            <div class="rootline-circle <?php echo $checked ? 'wpbdp-submit-checked' : ''; ?>">
                <div class="rootline-counter">
                <?php if ( $checked ) : ?>
                    <img src="<?php echo esc_attr( WPBDP_URL . 'assets/images/checkmark.svg' ); ?>" class="rootline-checkmark">
                <?php endif; ?>
                    <span class="rootline-pos"><?php echo esc_html( $id + 1 ); ?></span>
                </div>
            </div>
            <div class="rootline-section-name"><?php echo esc_html( $sections[$section_id]['title'] );?></div>
        </div>
    <?php endforeach; ?>
</div>