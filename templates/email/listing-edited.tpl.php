<?php
/**
 * Listing Edited notification template
 *
 * @package BDP/Templates/Email/Listing Edited
 */

echo esc_html_x( 'A listing in the directory has been edited recently. Listing details can be found below.', 'emails', 'business-directory-plugin' );
?>

----

<?php esc_html_e( 'ID', 'business-directory-plugin' ); ?>: <?php echo esc_html( $listing->get_id() ); ?>


<?php esc_html_e( 'Title', 'business-directory-plugin' ); ?>: <?php echo esc_html( $listing->get_title() ); ?>


<?php esc_html_e( 'URL', 'business-directory-plugin' ); ?>: <?php echo esc_html( $listing->is_published() ? $listing->get_permalink() : __( '(not published yet)', 'business-directory-plugin' ) ); ?>

<?php esc_html_e( 'Admin URL', 'business-directory-plugin' ); ?>: <?php echo esc_html( wpbdp_get_edit_post_link( $listing->get_id() ) ); ?>

<?php
$categories = array();
foreach ( $listing->get_categories() as $category ) :
	$categories[] = $category->name;
endforeach;
?>
<?php echo esc_html( _n( 'Category', 'Categories', count( $listing->get_categories() ), 'business-directory-plugin' ) ); ?>: <?php echo esc_html( implode( ' / ', $categories ) ); ?>


<?php esc_html_e( 'Posted By', 'business-directory-plugin' ); ?>: <?php echo esc_html( $listing->get_author_meta( 'user_login' ) ); ?> (<?php echo esc_html( $listing->get_author_meta( 'user_email' ) ); ?>)
