<?php
$uid = ! empty( $uid ) ? $uid : uniqid( 'wpbdp-settings-email-' );

$editor_only = isset( $editor_only ) ? (bool) $editor_only : false;

$container_class = ! empty( $container_class ) ? $container_class : '';
$setting_name = ! empty( $setting_name ) ? $setting_name : '';
$email_subject = ! empty( $email_subject ) ? $email_subject : _x( 'Untitled', 'settings email', 'WPBDM' );
$email_body = ! empty( $email_body ) ? $email_body : '';
$email_body_display = strip_tags( $email_body );

$placeholders = ! empty( $placeholders ) ? $placeholders : array();
$before_container = ! empty( $before_container ) ? $before_container : '';
$after_container = ! empty( $after_container ) ? $after_container : '';
$before_preview = ! empty( $before_preview ) ? $before_preview : '';
$after_preview = ! empty( $after_preview ) ? $after_preview : '';
$extra_fields = ! empty( $extra_fields ) ? $extra_fields : '';

$before_buttons = isset( $before_buttons ) ? $before_buttons : '';
$after_buttons = isset( $after_buttons ) ? $after_buttons : '';
?>

<?php echo $before_container; ?>
<div class="wpbdp-settings-email <?php echo $container_class; ?>">
    <?php if ( ! $editor_only ): ?>
    <?php echo $before_preview; ?>
    <div class="wpbdp-settings-email-preview" title="<?php _ex( 'Click to edit e-mail', 'settings email', 'WPBDM' ); ?>">
        <a href="#" class="wpbdp-settings-email-edit-btn wpbdp-tag"><?php _ex( 'Click to edit', 'settings email', 'WPBDM' ); ?></a>
        <h4><?php echo $email_subject; ?></h4>
        <?php if ( strlen( $email_body_display ) > 200 ): ?>
            <?php echo substr( $email_body_display, 0, 200 ); ?>...
        <?php else: ?>
            <?php echo $email_body_display; ?>
        <?php endif; ?>
    </div>
    <?php echo $after_preview; ?>
    <?php endif; ?>

    <div class="wpbdp-settings-email-editor">
        <input type="hidden" value="<?php echo esc_attr( $email_subject ); ?>" class="stored-email-subject" />
        <input type="hidden" value="<?php echo esc_attr( $email_body ); ?>" class="stored-email-body" />

        <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><label for="<?php echo $uid; ?>-subject"><?php _ex( 'E-Mail Subject', 'settings email', 'WPBDM' ); ?></label></th>
                <td>
                    <input name="<?php echo $setting_name; ?>[subject]" value="<?php echo esc_attr( $email_subject ); ?>" type="text" value="<?php echo esc_attr( $email_subject ); ?>" id="<?php echo $uid; ?>-subject" class="email-subject" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="<?php echo $uid; ?>-body"><?php _ex( 'E-Mail Body', 'settings email', 'WPBDM' ); ?></label></th>
                <td>
                    <textarea name="<?php echo $setting_name; ?>[body]"  id="<?php echo $uid; ?>-body" class="email-body" placeholder="<?php _ex( 'E-mail body text', 'expiration notices', 'WPBDM' ); ?>"><?php echo esc_textarea( $email_body ); ?></textarea>

                    <?php if ( $placeholders ): ?>
                    <div class="placeholders">
                        <p><?php _ex( 'You can use the following placeholders:', 'settings email', 'WPBDM' ); ?></p>

                        <?php
                        $added_sep = false;

                        foreach ( $placeholders as $placeholder => $placeholder_data ):
                            $description = is_array( $placeholder_data ) ? $placeholder_data[0] : $placeholder_data;
                            $is_core_placeholder = is_array( $placeholder_data ) && isset( $placeholder_data[2] ) && $placeholder_data[2];

                            if ( $is_core_placeholder && ! $added_sep ):
                        ?>
                            <div class="placeholder-separator"></div>
                        <?php
                                $added_sep = true;
                            endif;
                        ?>
                            <div class="placeholder" data-placeholder="<?php echo esc_attr( $placeholder ); ?>"><span class="placeholder-code">[<?php echo $placeholder; ?>]</span> - <span class="placeholder-description"><?php echo $description; ?></span></div>
                        <?php
                        endforeach;
                        ?>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php echo $extra_fields; ?>
        </tbody>
        </table>

        <div class="buttons">
            <?php echo $before_buttons; ?>
            <!-- <a href="#" class="button preview-email"><?php _ex( 'Preview e-mail', 'settings email', 'WPBDM' ); ?></a> -->
            <a href="#" class="button cancel"><?php _ex( 'Cancel', 'settings email', 'WPBDM' ); ?></a> 
            <input type="submit" class="button button-primary" value="<?php _ex( 'Save Changes', 'settings email', 'WPBDM' ); ?>" />
            <?php echo $after_buttons; ?>
        </div>
    </div>
</div>
<?php echo $after_container; ?>
