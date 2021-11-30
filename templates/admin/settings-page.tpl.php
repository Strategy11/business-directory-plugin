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
	<ul class="nav nav-pills flex-column mb-auto wpbdp-nav-items">
		<?php foreach ( $tabs as $tab_id => $tab ) : ?>
			<li class="nav-item wpbdp-nav-item">
				<a class="nav-link <?php echo $active_tab == $tab_id ? 'active' : ''; ?> <?php echo esc_attr( apply_filters( 'wpbdp_settings_tab_css', '', $tab_id ) ); ?>" href="<?php echo esc_url( add_query_arg( 'tab', $tab_id ) ); ?>" title="<?php echo esc_html( $tab['title'] ); ?>">
					<div class="row">
						<div class="col wpbdp-nav-item-icon wpbdp-nav-item-icon-<?php echo esc_attr( $tab_id ); ?>">
							<span class="wpbdp-nav-item-icon dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
						</div>
						<div class="col wpbdp-nav-item-name wpbdp-nav-item-name-<?php echo esc_attr( $tab_id ); ?>">
							<?php echo esc_html( $tab['title'] ); ?>
							<div class="wpbdp-nav-item-meta">
								<?php echo esc_html( $tab['desc'] ); ?>
							</div>
						</div>
					</div>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<div class="wpbdp-nav-toggle">
		<div class="row">
			<div class="col-3 wpbdp-nav-item-icon">
				<svg width="24" height="24" class="wpbdp-icon-maximized" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<rect width="24" height="24" rx="12" fill="white"/>
					<path d="M14 16L10 12L14 8" stroke="black" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<svg width="24" class="wpbdp-icon-minimized" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<rect width="24" height="24" rx="12" transform="matrix(-1 0 0 1 24 0)" fill="white"/>
					<path d="M10 16L14 12L10 8" stroke="black" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>

			</div>
			<div class="col wpbdp-nav-item-name">
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
		<div class="wpbdp-content-area-header-title">
			<h1 class="wpbdp-sub-section-title"><?php echo esc_html( ucfirst( $active_tab ) ); ?></h1>
		</div>
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
