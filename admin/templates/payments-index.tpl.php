<?php echo wpbdp_admin_header(); ?>
<?php echo wpbdp_admin_notices(); ?>


<?php $table->views(); ?>

<form action="" method="get">
    <p class="search-box">
        <label class="screen-reader-text" for="payment-search-input"><?php _ex( 'Search Payments:', 'admin payments', 'WPBDM' ); ?></label>
        <input type="search" id="payment-search-input" name="s" value="<?php echo ! empty( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : ''; ?>" />
        <input type="submit" id="search_submit" class="button" value="<?php _ex( 'Search', 'admin payments', 'WPBDM' ); ?>" />
    </p>

    <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
    <input type="hidden" name="status" value="<?php echo ! empty( $_GET['status'] ) ? $_GET['status'] : 'all'; ?>" />

<?php $table->display(); ?>

</form>

<?php echo wpbdp_admin_footer(); ?>
