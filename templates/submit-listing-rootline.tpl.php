<div class="wpbdp-submit-rootline">
    <?php foreach ( array_keys( $sections ) as $id => $section_id ): ?>
        <?php $checked = $section_id === $submit->current_section || $submit->should_validate_section( $section_id ); ?>
        <div class="wpbdp-rootline-section wpbdp-submit-section-<?php echo esc_html( $section_id ); ?>" data-section-pos="<?php echo esc_html( $id + 1 ); ?>">
            <div class='rootline-bar bar-left'></div>
            <div class='rootline-bar bar-right'></div>
            <div class="rootline-circle <?php echo $checked ? 'wpbdp-submit-checked' : '' ?>">
                <span class="rootline-counter"><?php echo $checked ? '✓' : esc_html( $id + 1 ); ?></span>
            </div>
            <div class="rootline-section-name"><?php echo esc_html( $sections[$section_id]['title'] );?></div>
        </div>
    <?php endforeach; ?>
</div>