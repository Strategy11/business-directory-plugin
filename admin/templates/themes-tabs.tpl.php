<?php
$active = isset( $active ) ? $active : 'themes';
?>

<h3 class="nav-tab-wrapper">
    <a class="nav-tab <?php echo 'themes' == $active ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wpbdp-themes' ) ); ?>">Available Themes</a>
    <a class="nav-tab <?php echo 'licenses' == $active ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wpbdp-themes&v=licenses' ) ); ?>">Licenses</a>
</h3>
