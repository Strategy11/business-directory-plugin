<div class="listing-actions">
<?php if (is_user_logged_in()): ?>
    <?php if ($view == 'single'): ?>
        <?php echo wpbusdirman_menu_button_editlisting(); ?>
        <?php echo wpbusdirman_menu_button_upgradelisting(); ?>
    <?php endif; ?>
<?php endif; ?>
</div>