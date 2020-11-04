<div class="wpbdp-submit-rootline">
    <?php foreach ( array_keys( $sections ) as $id => $section_id ): ?>
        <?php $checked = $section_id === $submit->current_section || $submit->should_validate_section( $section_id ); ?>
        <div class="wpbdp-rootline-section wpbdp-submit-section-<?php echo esc_attr( $section_id ); ?>" data-section-pos="<?php echo esc_attr( $id + 1 ); ?>">
            <div class='rootline-bar bar-right'></div>
            <div class="rootline-circle <?php echo $checked ? 'wpbdp-submit-checked' : '' ?>">
                <div class="rootline-counter">
                <?php if ( $checked ) : ?>
                    <img src="<?php echo esc_attr( WPBDP_URL . 'assets/images/checkmark.svg' ); ?>" class="rootline-checkmark">
                <?php else : ?>
                        <?php echo esc_html( $id + 1 ); ?>
                <?php endif; ?>
                </div>
            </div>
            <div class="rootline-section-name"><?php echo esc_html( $sections[$section_id]['title'] );?></div>
        </div>
    <?php endforeach; ?>
</div>