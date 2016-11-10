<?php
$author = is_numeric( $note['who'] ) ? get_user_meta( (int) $note['who'], 'nickname', true ) : $note['who'];
$date = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $note['when'] );
?>
<div class="wpbdp-payment-note" data-id="<?php echo $note['key']; ?>">

    <a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=wpbdp_admin_ajax&handler=payments__delete_note&note=' . $note['key'] . '&payment_id=' . $payment_id ) ); ?>" class="wpbdp-admin-delete-link wpbdp-admin-confirm" data-confirm="<?php _ex( 'Are you sure you want to delete this note?', 'payments admin', 'WPBDM' ); ?>"><?php _ex( 'Delete', 'payments admin', 'WPBDM' ); ?></a>
    <div class="wpbdp-payment-note-meta">
        <span class="wpbdp-payment-note-meta-user"><?php echo $author; ?></span>
        <span class="sep"> - </span>
        <span class="wpbdp-payment-note-meta-date"><?php echo $date; ?></span>
    </div>

    <div class="wpbdp-payment-note-text">
        <?php echo esc_html( $note['text'] ); ?>
    </div>

</div>

