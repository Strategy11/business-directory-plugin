<div class="wpbdp-submit-rootline">
    <?php foreach ( array_keys( $sections ) as $id => $section_id ): ?>
        <?php $current = $section_id === $submit->current_section; ?>
        <?php $checked = $current || $submit->should_validate_section( $section_id ); ?>
        <div class="wpbdp-rootline-section wpbdp-submit-section-<?php echo esc_attr( $section_id ); ?> <?php echo $current ? 'wpbdp-submit-section-current' : ''; ?> <?php echo $checked ? 'wpbdp-submit-checked' : ''; ?>" data-section-pos="<?php echo esc_attr( $id + 1 ); ?>">
            <div class="wpbdp-rootline-bar"></div>
            <div class="wpbdp-rootline-circle">
                <div class="wpbdp-rootline-counter">
                <?php if ( $checked && ! $current ) : ?>
                    <svg width="19" height="14" viewBox="0 0 19 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.4473 1.22559L6.44727 12.2256L1.44727 7.22559" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?php endif; ?>
                    <span class="wpbdp-rootline-pos"><?php echo esc_html( $id + 1 ); ?></span>
                </div>
            </div>
            <div class="wpbdp-rootline-section-name"><?php echo esc_html( $sections[$section_id]['title'] );?></div>
        </div>
    <?php endforeach; ?>
</div>