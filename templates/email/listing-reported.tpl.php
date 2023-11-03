<?php
	esc_html_e( 'A listing has been reported as inappropriate. Listing details can be found below.', 'business-directory-plugin' );
?>

----

<?php esc_html_e( 'Listing Information', 'business-directory-plugin' ); ?>:

<?php esc_html_e( 'ID', 'business-directory-plugin' ); ?>: <?php echo esc_html( $listing->get_id() ); ?>

<?php esc_html_e( 'Title', 'business-directory-plugin' ); ?>: <?php echo esc_html( $listing->get_title() ); ?>

<?php esc_html_e( 'URL', 'business-directory-plugin' ); ?>: <?php echo esc_html( $listing->is_published() ? $listing->get_permalink() : __( '(not published yet)', 'business-directory-plugin' ) ); ?>

<?php esc_html_e( 'Admin URL', 'business-directory-plugin' ); ?>: <?php echo esc_url_raw( wpbdp_get_edit_post_link( $listing->get_id() ) ); ?>

<?php esc_html_e( 'Categories', 'business-directory-plugin' ); ?>:
			<?php
			foreach ( $listing->get_categories() as $category ) :
				?>
				<?php echo esc_html( $category->name ); ?> / <?php endforeach; ?>

<?php esc_html_e( 'Posted By', 'business-directory-plugin' ); ?>: <?php echo esc_html( $listing->get_author_meta( 'user_login' ) ); ?> (<?php echo esc_html( $listing->get_author_meta( 'user_email' ) ); ?>)

<?php esc_html_e( 'Report Information', 'business-directory-plugin' ); ?>:

<?php if ( ! empty( $report['name'] ) ) : ?>
	<?php esc_html_e( 'User name', 'business-directory-plugin' ); ?>: <?php echo esc_html( $report['name'] ); ?>

<?php endif; ?>
<?php if ( ! empty( $report['email'] ) ) : ?>
	<?php esc_html_e( 'User Email', 'business-directory-plugin' ); ?>: <?php echo esc_html( $report['email'] ); ?>

<?php endif; ?>
<?php esc_html_e( 'Report IP', 'business-directory-plugin' ); ?>: <?php echo esc_html( $report['ip'] ); ?>

<?php esc_html_e( 'Report selected option', 'business-directory-plugin' ); ?>: <?php echo esc_html( $report['reason'] ); ?>

<?php echo isset( $report['comments'] ) && '' != $report['comments'] ? esc_html_x( 'Report additional info', 'notify email', 'business-directory-plugin' ) . ': ' . esc_html( $report['comments'] ) : ''; ?>
