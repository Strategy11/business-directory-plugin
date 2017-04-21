<?php
/**
 * @since fees-revamp
 */
class WPBDP__Expiration_Notices_Setting {

    public function __construct() {
    }

    public function get_default() {
        $notices = array();

        /* renewal-pending-message, non-recurring only */
        $notices[] = array(
            'event' => 'expiration',
            'relative_time' => '+5 days', /* renewal-email-threshold, def: 5 days */
            'listings' => 'non-recurring',
            'subject' => '[[site-title]] [listing] - Expiration notice',
            'body' => 'Your listing "[listing]" is about to expire at [site]. You can renew it here: [link].'
        );
        //         array( 'placeholders' => array( 'listing' => _x( 'Listing\'s name (with link)', 'settings', 'WPBDM' ),
        //                                         'author' => _x( 'Author\'s name', 'settings', 'WPBDM' ),
        //                                         'expiration' => _x( 'Expiration date', 'settings', 'WPBDM' ),
        //                                         'category' => _x( 'Category that is going to expire', 'settings', 'WPBDM' ),
        //                                         'link' => _x( 'Link to renewal page', 'settings', 'WPBDM' ),
        //                                         'site' => _x( 'Link to your site', 'settings', 'WPBDM' )  ) )

        /* listing-renewal-message, non-recurring only */
        $notices[] = array(
            'event' => 'expiration',
            'relative_time' => '0 days', /* at time of expiration */
            'listings' => 'non-recurring',
            'subject' => '[[site-title]] [listing] - Expiration notice',
            'body' => "Your listing \"[listing]\" in category [category] expired on [expiration]. To renew your listing click the link below.\n[link]"
        );
        //                     array( 'placeholders' => array( 'listing' => _x( 'Listing\'s name (with link)', 'settings', 'WPBDM' ),
        //                                                     'author' => _x( 'Author\'s name', 'settings', 'WPBDM' ),
        //                                                     'expiration' => _x( 'Expiration date', 'settings', 'WPBDM' ),
        //                                                     'category' => _x( 'Category that expired', 'settings', 'WPBDM' ),
        //                                                     'link' => _x( 'Link to renewal page', 'settings', 'WPBDM' ),
        //                                                     'site' => _x( 'Link to your site', 'settings', 'WPBDM' )  ) )

        /* renewal-reminder-message, both recurring and non-recurring */
        $notices[] = array(
            'event' => 'expiration',
            'relative_time' => '-5 days', /* renewal-reminder-threshold */
            'listings' => 'both',
            'subject' => '[[site-title]] [listing] - Expiration reminder',
            'body' => "Dear Customer\nWe've noticed that you haven't renewed your listing \"[listing]\" for category [category] at [site] and just wanted to remind you that it expired on [expiration]. Please remember you can still renew it here: [link]."
        );
        //                     array( 'placeholders' => array( 'listing' => _x( 'Listing\'s name (with link)', 'settings', 'WPBDM' ),
        //                                                     'author' => _x( 'Author\'s name', 'settings', 'WPBDM' ),
        //                                                     'expiration' => _x( 'Expiration date', 'settings', 'WPBDM' ),
        //                                                     'category' => _x( 'Category that expired', 'settings', 'WPBDM' ),
        //                                                     'link' => _x( 'Link to renewal page', 'settings', 'WPBDM' ),
        //                                                     'site' => _x( 'Link to your site', 'settings', 'WPBDM' )  ) )

        /* listing-autorenewal-notice, recurring only, controlled by the send-autorenewal-expiration-notice setting */
        $notices[] = array(
            'event' => 'expiration',
            'relative_time' => '+5 days' /*  renewal-email-threshold, def: 5 days */,
            'listings' => 'recurring',
            'subject' => '[[site-title]] [listing] - Renewal reminder',
            'body' => "Hey [author],\n\nThis is just to remind you that your listing [listing] is going to be renewed on [date] for another period.\nIf you want to review or cancel your subscriptions please visit [link].\n\nIf you have any questions, contact us at [site]."
        );
        //                     array( 'placeholders' => array( 'listing' => _x( 'Listing\'s name (with link)', 'settings', 'WPBDM' ),
        //                                                     'author' => _x( 'Author\'s name', 'settings', 'WPBDM' ),
        //                                                     'date' => _x( 'Renewal date', 'settings', 'WPBDM' ),
        //                                                     'category' => _x( 'Category that is going to be renewed', 'settings', 'WPBDM' ),
        //                                                     'site' => _x( 'Link to your site', 'settings', 'WPBDM' ),
        //                                                     'link' => _x( 'Link to manage subscriptions', 'settings', 'WPBDM' ) ) )

        /* listing-autorenewal-message, after IPN notification of renewal of recurring */
        $notices[] = array(
            'event' => 'renewal',
            'listings' => 'recurring',
            'subject' => '[[site-title]] [listing] renewed',
            'body' => "Hey [author],\n\nThanks for your payment. We just renewed your listing [listing] on [date] for another period.\n\nIf you have any questions, contact us at [site]."
        );
        // $replacements['listing'] = sprintf( '<a href="%s">%s</a>',
        //                                     get_permalink( $payment->get_listing_id() ),
        //                                     get_the_title( $payment->get_listing_id() ) );
        // $replacements['author'] = get_the_author_meta( 'display_name', get_post( $payment->get_listing_id() )->post_author );
        // $replacements['category'] = wpbdp_get_term_name( $recurring_item->rel_id_1 );
        // $replacements['date'] = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
        //                                    strtotime( $payment->get_processed_on() ) );
        // $replacements['site'] = sprintf( '<a href="%s">%s</a>',
        //                                  get_bloginfo( 'url' ),
        //                                  get_bloginfo( 'name' ) );
        //


        return $notices;
    }

    public function setting_callback( $setting, $value ) {
        $blank_notice = array( 'event' => 'expiration', 'listings' => 'both', 'relative_time' => '0 days', 'subject' => '', 'body' => '' );

        echo '<div class="wpbdp-settings-expiration-notices">';
        echo '<button id="wpbdp-settings-expiration-notices-add-btn" class="button">' . _x( 'Add notice', 'expiration notices', 'WPBDM' ) . '</button>';
        echo '<div id="wpbdp-settings-expiration-notices-add">';
        echo wpbdp_render_page(
            WPBDP_PATH . 'admin/templates/settings-email.tpl.php',
            array(
                'setting_name' => 'new_notice[' . ( count( $value ) + 1 ) . ']',
                'uid' => '',
                'container_class' => 'wpbdp-expiration-notice-email',
                'extra_fields' => $this->setting_email_fields( 'new_notice[' . ( count( $value ) + 1 ) . ']', '', $blank_notice ),
                'editor_only' => true
            )
        );
        echo '</div>';


        if ( ! $value )
            echo '<p class="wpbdp-no-items">' . _x( 'No notices configured.', 'expiration notices', 'WPBDM' ) . '</p>';

        foreach ( $value as $i => $notice ) {
            $uid = uniqid( 'wpbdp-settings-email-' );
            $vars = array(
                'setting_name' => 'wpbdp-' . $setting->name . '[' . $i . ']',
                'uid' => $uid,
                'container_class' => 'wpbdp-expiration-notice-email',
                'email_subject' => $notice['subject'],
                'email_body' => $notice['body'],
                'extra_fields' => $this->setting_email_fields( 'wpbdp-' . $setting->name . '[' . $i . ']', $uid, $notice ),
                'after_container' => $this->setting_email_summary( $notice ),
                'before_buttons' => '<a href="#" class="delete">' . _x( 'Delete', 'expiration notices', 'WPBDM' ) . '</a>'
            );

            echo wpbdp_render_page( WPBDP_PATH . 'admin/templates/settings-email.tpl.php', $vars );
        }

        echo '</div>';
    }

    private function get_notices_schedule() {
        $expiration_notices_schedule = array(
            array( 'expiration', '0 days', _x( 'At the time of expiration', 'expiration notices', 'WPBDM' ) ),
            array( 'renewal', '0 days', _x( 'Right after a successful renewal', 'expiration notices', 'WPBDM' ) )
        );
        foreach ( array( '+', '-' ) as $mod ) {
            // Days.
            foreach ( array( 1, 2, 3, 4, 5 ) as $i ) {
                $expiration_notices_schedule[] = array(
                    'expiration',
                    $mod . $i . ' days',
                    sprintf( '+' == $mod ? _nx( '%d day before expiration', '%d days before expiration', $i, 'expiration notices', 'WPBDM' ) : _nx( '%d day after expiration', '%d days after expiration', $i, 'expiration notices', 'WPBDM' ), $i )
                );
            }

            // Weeks.
            foreach ( array( 1, 2 ) as $i ) {
                $expiration_notices_schedule[] = array(
                    'expiration',
                    $mod . $i . ' weeks',
                    sprintf( '+' == $mod ? _nx( '%d week before expiration', '%d weeks before expiration', $i, 'expiration notices', 'WPBDM' ) : _nx( '%d week after expiration', '%d weeks after expiration', $i, 'expiration notices', 'WPBDM' ), $i )
                );
            }

            // Months.
            foreach ( array( 1, 2 ) as $i ) {
                $expiration_notices_schedule[] = array(
                    'expiration',
                    $mod . $i . ' months',
                    sprintf( '+' == $mod ? _nx( '%d month before expiration', '%d months before expiration', $i, 'expiration notices', 'WPBDM' ) : _nx( '%d month after expiration', '%d months after expiration', $i, 'expiration notices', 'WPBDM' ), $i )
                );
            }
        }

        $expiration_notices_schedule = apply_filters( 'wpbdp_expiration_notices_schedule', $expiration_notices_schedule );
        return $expiration_notices_schedule;
    }

    private function setting_email_summary( $notice ) {
        $event = $notice['event'];
        $listings = $notice['listings'];
        $relative_time = ! empty( $notice['relative_time'] ) ? $notice['relative_time'] : '';

        if ( 'both' == $listings ) {
            $recurring_modifier = _x( 'recurring and non-recurring', 'expiration notices', 'WPBDM' );
        } elseif ( 'recurring' == $listings ) {
            $recurring_modifier = _x( 'recurring only', 'expiration notices', 'WPBDM' );
        } else {
            $recurring_modifier = _x( 'non-recurring only', 'expiration notices', 'WPBDM' );
        }

        if ( 'renewal' == $event ) {
            $summary = sprintf( _x( 'Sent when a listing (%s) is renewed.', 'expiration notices', 'WPBDM' ), $recurring_modifier );
        }

        if ( 'expiration' == $event ) {
            if ( '0 days' == $relative_time ) {
                $summary = sprintf( _x( 'Sent when a listing (%s) expires.', 'expiration notices', 'WPBDM' ), $recurring_modifier );
            } else {
                $relative_time_parts = explode( ' ', $relative_time );
                $relative_time_number = trim( str_replace( array( '+', '-' ), '', $relative_time_parts[0] ) );
                $relative_time_units = $relative_time_parts[1];

                switch ( $relative_time_units ) {
                case 'days':
                    $relative_time_h = sprintf( _nx( '%d day', '%d days', $relative_time_number, 'expiration notices', 'WPBDM' ), $relative_time_number );
                    break;
                case 'weeks':
                    $relative_time_h = sprintf( _nx( '%d week', '%d weeks', $relative_time_number, 'expiration notices', 'WPBDM' ), $relative_time_number );
                    break;
                case 'months':
                    $relative_time_h = sprintf( _nx( '%d month', '%d months', $relative_time_number, 'expiration notices', 'WPBDM' ), $relative_time_number );
                    break;
                }

                if ( $relative_time[0] == '+' ) {
                    $summary = sprintf( _x( 'Sent %1$s before a listing (%2$s) expires.', 'expiration notices', 'WPBDM' ), $relative_time_h, $recurring_modifier );
                } else {
                    $summary = sprintf( _x( 'Sent %1$s after a listing (%2$s) expires.', 'expiration notices', 'WPBDM' ), $relative_time_h, $recurring_modifier );
                }
            }
        }

        ob_start();
?>
<div class="wpbdp-expiration-notice-email-schedule-summary">
    <?php echo $summary; ?>
</div>
<?php
        return ob_get_clean();
    }

    private function setting_email_fields( $name, $uid, $notice ) {
        $expiration_notices_schedule = $this->get_notices_schedule();

        ob_start();
?>
    <tr>
        <th scope="row"><label for="<?php echo $uid; ?>-listings"><?php _ex( 'Applies to', 'expiration notices', 'WPBDM' ); ?></label></th>
        <td>
            <select id="<?php echo $uid; ?>-listings" name="<?php echo $name; ?>[listings]">
                <option value="non-recurring" <?php selected( 'non-recurring', $notice['listings'] ); ?>><?php _ex( 'Non-recurring listings', 'expiration notices', 'WPBDM' ); ?></option>
                <option value="recurring" <?php selected( 'recurring', $notice['listings'] ); ?>><?php _ex( 'Recurring listings', 'expiration notices', 'WPBDM' ); ?></option>
                <option value="both" <?php selected( 'both', $notice['listings'] ); ?>><?php _ex( 'Recurring and non-recurring listings', 'expiration notices', 'WPBDM' ); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="<?php echo $uid; ?>-relative-time-and-event"><?php _ex( 'When to send?', 'expiration notices', 'WPBDM' ); ?></label></th>
        <td>
            <input type="hidden" value="<?php echo $notice['event']; ?>" class="stored-notice-event" />
            <input type="hidden" value="<?php echo ! empty( $notice['relative_time'] ) ? $notice['relative_time'] : ''; ?>" class="stored-notice-relative-time" />

            <input type="hidden" name="<?php echo $name; ?>[event]" value="<?php echo $notice['event']; ?>" class="notice-event" />
            <input type="hidden" name="<?php echo $name; ?>[relative_time]" value="<?php echo ! empty( $notice['relative_time'] ) ? $notice['relative_time'] : ''; ?>" class="notice-relative-time" />

            <select id="<?php echo $uid; ?>-relative-time-and-event" class="relative-time-and-event">
                <?php foreach ( $expiration_notices_schedule as $item ): ?>
                    <?php if ( 'renewal' == $item[0] ): ?>
                    <option value="<?php echo $item[0]; ?>,<?php echo $item[1]; ?>" <?php selected( $item[0], $notice['event'] ); ?>><?php echo $item[2]; ?></option>
                    <?php else: ?>
                    <option value="<?php echo $item[0]; ?>,<?php echo $item[1]; ?>" <?php selected( $item[0] == $notice['event'] && ! empty( $notice['relative_time'] ) && $item[1] == $notice['relative_time'], true ); ?>><?php echo $item[2]; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
<?php
        return ob_get_clean();
    }

}
