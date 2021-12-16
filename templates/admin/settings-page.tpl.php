<?php
$original_uri = wpbdp_get_server_value( 'REQUEST_URI' );
$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'tab', 'subtab' ) );

wpbdp_admin_bootstrap_header(
    array(
        'echo' => true,
    )
);
?>
<div class="wpbdp-no-padding wpbdp-menu-area">
	<?php
		wpbdp_admin_title(
			array(
				'title' => esc_html__( 'Directory Settings', 'business-directory-plugin' ),
				'echo'  => true,
			)
		);
	?>
	<ul class="wpbdp-nav-items">
		<?php foreach ( $tabs as $tab_id => $tab ) : ?>
			<li class="wpbdp-nav-item">
				<a class="<?php echo $active_tab == $tab_id ? 'active ' : ''; ?><?php echo sanitize_html_class( apply_filters( 'wpbdp_settings_tab_css', '', $tab_id ) ); ?>" href="<?php echo esc_url( add_query_arg( 'tab', $tab_id ) ); ?>" title="<?php echo esc_html( $tab['title'] ); ?>">
					<span class="wpbdp-nav-item-icon <?php echo esc_attr( $tab['icon'] ); ?>"></span>
					<span class="wpbdp-nav-item-name"><?php echo esc_html( $tab['title'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<div class="wpbdp-nav-toggle hide-if-no-js">
		<div class="wpbdp-grid">
			<div class="wpbdp-col-2 wpbdp-nav-item-icon">
				<img src="<?php echo esc_url( WPBDP_ASSETS_URL . 'images/icons/caret-left.svg' ); ?>" class="wpbdp-icon-maximized" width="24" height="24"/>
				<img src="<?php echo esc_url( WPBDP_ASSETS_URL . 'images/icons/caret-right.svg' ); ?>" class="wpbdp-icon-minimized" width="24" height="24"/>
			</div>
			<div class="wpbdp-col-10 wpbdp-nav-item-name">
				<?php esc_html_e( 'Minimize Navigation', 'business-directory-plugin' ); ?>
			</div>
		</div>
	</div>
</div>
<main class="wpbdp-content-area">
	<?php if ( ! $custom_form ) : ?>
	<form action="options.php" method="post">
	<?php endif; ?>
	<div class="wpbdp-content-area-header">
		<h2 class="wpbdp-sub-section-title"><?php echo esc_html( ucfirst( $active_tab ) ); ?></h2>

		<div class="wpbdp-content-area-header-actions">
			<?php if ( ! $custom_form ) :
				// Submit button shouldn't use 'submit' as name to avoid conflicts with
				// actual properties of the parent form.
				//
				// See http://kangax.github.io/domlint/
				submit_button( null, 'primary', 'save-changes' );
			endif; ?>
		</div>
	</div>
	<div class="wpbdp-content-area-body">
		<?php if ( count( $subtabs ) > 1 || 'modules' == $active_tab ) : ?>
		<div class="wpbdp-settings-tab-subtabs wpbdp-clearfix">
			<ul class="subsubsub wpbdp-sub-menu">
				<?php
				$n = 0;
				foreach ( $subtabs as $subtab_id => $subtab ) :
					$n++;
				?>
					<?php
					$subtab_url = add_query_arg( 'tab', $active_tab );
					$subtab_url = add_query_arg( 'subtab', $subtab_id, $subtab_url );
					?>
					<li>
						<a class="<?php echo $active_subtab == $subtab_id ? 'current' : ''; ?>" href="<?php echo esc_url( $subtab_url ); ?>"><?php echo esc_html( $subtab['title'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>

		<?php settings_errors(); ?>

		<?php if ( $active_subtab_description ) : ?>
		<p class="wpbdp-settings-subtab-description wpbdp-setting-description"><?php echo wp_kses_post( $active_subtab_description ); ?></p>
		<?php endif; ?>

		

		<?php
			$_SERVER['REQUEST_URI'] = $original_uri;

			if ( ! $custom_form ) :
				settings_fields( 'wpbdp_settings' );
			endif;

			wpbdp_admin_do_settings_sections( 'wpbdp_settings_subtab_' . $active_subtab );
			do_action( 'wpbdp_settings_subtab_' . $active_subtab );

			if ( ! $custom_form ) :
				// Submit button shouldn't use 'submit' as name to avoid conflicts with
				// actual properties of the parent form.
				//
				// See http://kangax.github.io/domlint/
				submit_button( null, 'primary', 'save-changes' );
			endif;
		?>

		<?php if ( ! $custom_form ) : ?>
		</form>
		<?php endif; ?>
	</div>
	<?php wpbdp_admin_notification_bell( true ); ?>
</div>
<?php
    wpbdp_admin_footer( 'echo' );

    /*
<h3 class="nav-tab-wrapper">
<?php if (isset($_REQUEST['settings-updated'])): ?>
	<div class="updated fade">
		<p><?php _e('Settings updated.', 'business-directory-plugin' ); ?></p>
	</div>
<?php endif; ?>
</h3>
	<?php if ($group->help_text): ?>
		<p class="description"><?php echo $group->help_text; ?></p>
	<?php endif; ?>
<?php
	echo wpbdp_admin_footer();
?>
 */
        // $reset_defaults = ( isset( $_GET['action'] ) && 'reset' == $_GET['action'] );
        // if ( $reset_defaults ) {
        //     echo wpbdp_render_page( WPBDP_PATH . 'templates/admin/settings-reset.tpl.php' );
        //     return;
        // }
?>
