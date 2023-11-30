<?php
$name  = empty( $value['name'] ) ? '' : $value['name'];
$email = empty( $value['email'] ) ? '' : $value['email'];

if ( ! $name && ! $email && 0 !== $value['user_id'] ) :
	$user  = get_user_by( 'ID', $value['user_id'] );
	$name  = $user->data->user_login;
	$email = $user->data->user_email;
endif;
?>
<tr data-id="<?php echo esc_attr( $key ); ?>">
	<td class="authoring-info">
		<?php echo esc_html( $name ? $name : __( 'Visitor', 'business-directory-plugin' ) ); ?>
		<br/>
		<?php echo esc_html( $email ? $email : '' ); ?>
		<div class="row-actions">
			<span class="trash">
				<a href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'wpbdmaction' => 'delete-flagging',
							'listing_id'  => $listing->get_id(),
							'meta_pos'    => $key,
						)
					)
				);
				?>
				" class="delete">
					<?php esc_html_e( 'Delete', 'business-directory-plugin' ); ?>
				</a>
			</span>
		</div>
	</td>
	<td class="report">
		<div class="submitted-on">
			<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), $value['date'] ) ); ?>
		</div>
		<div class="report-reasons">
			<?php esc_html_e( 'Selected Option: ', 'business-directory-plugin' ) . esc_html( $value['reason'] ); ?>
			<br/>
			<?php
			if ( ! empty( $value['comments'] ) ) :
				esc_html_e( 'Additional Info: ', 'business-directory-plugin' ) . esc_html( $value['comments'] );
			endif;
			?>
		</div>
	</td>
</tr>


