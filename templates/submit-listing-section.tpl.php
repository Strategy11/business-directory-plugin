<div class="wpbdp-submit-listing-section wpbdp-submit-listing-section-<?php echo $section['id']; ?> <?php echo implode( ' ', $section['flags'] ); ?>" data-section-id="<?php echo $section['id']; ?>">
    <div class="wpbdp-submit-listing-section-header">
        <span class="collapse-indicator collapsed">►</span><span class="collapse-indicator expanded">▼</span><span class="title"><?php echo $section['title']; ?></span>
    </div>
    <div class="wpbdp-submit-listing-section-content">
        <?php if ( $messages ): ?>
            <div class="wpbdp-submit-listing-section-messages"><?php echo $messages; ?></div>
        <?php endif; ?>

        <?php echo $section['html']; ?>
    </div>
</div>
