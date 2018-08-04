<?php
/**
 * Listing Edited notification template
 *
 * @package BDP/Templates/Email/Listing Edited
 */

    echo esc_attr_x( 'A listing in the directory has been edited recently. Listing details can be found below.', 'emails', 'WPBDM' );
?>

----

<?php echo esc_attr_x( 'ID', 'notify email', 'WPBDM' ); ?>: <?php echo esc_attr( $listing->get_id() ); ?>


<?php echo esc_attr_x( 'Title', 'notify email', 'WPBDM' ); ?>: <?php echo esc_attr( $listing->get_title() ); ?>


<?php echo esc_attr_x( 'URL', 'notify email', 'WPBDM' ); ?>: <?php echo $listing->is_published() ? esc_attr( $listing->get_permalink() ) : esc_attr_x( '(not published yet)', 'notify email', 'WPBDM' ); ?>

<?php echo esc_attr_x( 'Admin URL', 'notify email', 'WPBDM' ); ?>: <?php echo esc_attr( wpbdp_get_edit_post_link( $listing->get_id() ) ); ?>

<?php
$categories = array();
foreach ( $listing->get_categories() as $category ) :
    $categories[] = $category->name;
endforeach;
?>
<?php echo esc_attr( _nx( 'Category', 'Categories', count( $listing->get_categories() ), 'notify email', 'WPBDM' ) ); ?>: <?php echo esc_attr( implode( ' / ', $categories ) ); ?>


<?php echo esc_attr_x( 'Posted By', 'notify email', 'WPBDM' ); ?>: <?php echo esc_attr( $listing->get_author_meta( 'user_login' ) ); ?> (<?php echo esc_attr( $listing->get_author_meta( 'user_email' ) ); ?>)
