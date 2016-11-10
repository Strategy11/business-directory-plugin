<div class="wpbdp-payment-note" data-id="<?php echo $note['key']; ?>">
    <?php echo esc_html( $note['text'] ); ?>

    <a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=wpbdp_admin_ajax&handler=payments__delete_note&note=' . $note['key'] . '&payment_id=' . $payment_id ) ); ?>" class="wpbdp-admin-delete-link wpbdp-admin-confirm" data-confirm="<?php _ex( 'Are you sure you want to delete this note?', 'payments admin', 'WPBDM' ); ?>"><?php _ex( 'Delete', 'payments admin', 'WPBDM' ); ?></a>
</div>

