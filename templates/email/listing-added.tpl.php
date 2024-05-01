<?php
	esc_html_e( 'A new listing has been submitted to the directory. Listing details can be found below.', 'business-directory-plugin' );
?>

----

<?php esc_html_e( 'ID', 'business-directory-plugin' ); ?>: <?php echo esc_html( $listing->get_id() ); ?>


<?php esc_html_e( 'Title', 'business-directory-plugin' ); ?>: <?php echo esc_html( $listing->get_title() ); ?>


<?php esc_html_e( 'URL', 'business-directory-plugin' ); ?>: <?php echo esc_html( $listing->is_published() ? $listing->get_permalink() : get_preview_post_link( $listing->get_id() ) ); ?>

<?php esc_html_e( 'Admin URL', 'business-directory-plugin' ); ?>: <?php echo esc_url_raw( wpbdp_get_edit_post_link( $listing->get_id() ) ); ?>

<?php
$categories = array();
foreach ( $listing->get_categories() as $category ) :
	$categories[] = $category->name;
endforeach;
?>
<?php echo esc_html( _n( 'Category', 'Categories', count( $listing->get_categories() ), 'business-directory-plugin' ) ); ?>: <?php echo esc_html( implode( ' / ', $categories ) ); ?>


<?php
$name  = $listing->get_author_meta( 'user_login' );
$email = $listing->get_author_meta( 'user_email' );

esc_html_e( 'Posted By', 'business-directory-plugin' ) . ': ';
if ( $name && $email ) :
	echo esc_html( $name ) . ' &lt;' . esc_html( $email ) . '&gt;';
elseif ( $name ) :
	echo esc_html( $name );
elseif ( $email ) :
	echo '&lt;' . esc_html( $email ) . '&gt;';
else :
	echo esc_html_x( 'Annonymous User', 'notify email', 'business-directory-plugin' );
endif;
?>
