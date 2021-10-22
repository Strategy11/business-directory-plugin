<?php wpbdp_admin_header( array( 'echo' => true ) ); ?>

<div id="wpbdp-admin-debug-info-page">
<p>
	<?php esc_html_e( 'The following information can help our team debug possible problems with your setup.', 'business-directory-plugin' ); ?>
</p>
<p style="text-align: right;">
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpbdp-debug-info&download=1' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Download Debug Information', 'business-directory-plugin' ); ?></a>
</p>

<h3 class="nav-tab-wrapper">
<?php foreach ( $debug_info as $section_id => &$section ) : ?>
	<a class="nav-tab" href="<?php echo esc_attr( $section_id ); ?>"><?php echo esc_html( $section['_title'] ); ?></a>
<?php endforeach; ?>
</h3>

<?php foreach ( $debug_info as $section_id => &$section ) : ?>
<table class="wpbdp-debug-section" data-id="<?php echo esc_attr( $section_id ); ?>" style="display: none;">
	<tbody>
		<?php
		foreach ( $section as $k => $v ) :
			if ( wpbdp_starts_with( $k, '_' ) ) {
				continue;
			}
            ?>
		<tr>
			<th scope="row"><?php echo esc_attr( $k ); ?></th>
            <td>
				<?php
				if ( is_array( $v ) ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo isset( $v['html'] ) ? $v['html'] : esc_attr( $v['value'] );
				} else {
					echo esc_attr( $v );
				}
				?>
            </td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php endforeach; ?>
</div>

<?php wpbdp_admin_footer( 'echo' ); ?>
