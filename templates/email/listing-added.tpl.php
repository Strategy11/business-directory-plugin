<?php
    _ex( 'A new listing has been submitted to the directory. Listing details can be found below.', 'emails', 'WPBDM' );
?>

----

<?php _ex('ID', 'notify email', 'WPBDM'); ?>: <?php echo $listing->get_id(); ?>


<?php _ex('Title', 'notify email', 'WPBDM'); ?>: <?php echo $listing->get_title(); ?>


<?php _ex('URL', 'notify email', 'WPBDM'); ?>: <?php echo $listing->is_published() ? $listing->get_permalink() : _x( '(not published yet)', 'notify email', 'WPBDM' ); ?>

<?php _ex( 'Admin URL', 'notify email', 'WPBDM' ); ?>: <?php echo wpbdp_get_edit_post_link( $listing->get_id() ); ?>

<?php $categories = array();
foreach ( $listing->get_categories() as $category ):
    $categories[] = $category->name;
endforeach; ?>
<?php echo _nx('Category', 'Categories', count( $listing->get_categories() ), 'notify email', 'WPBDM'); ?>: <?php echo implode( ' / ', $categories ); ?>


<?php
$name = $listing->get_author_meta( 'user_login' );
$email = $listing->get_author_meta( 'user_email' );
$author_text = _x( 'Posted By', 'notify email', 'WPBDM' ) . ': ';

if ( $name && $email ):
    echo $author_text . $name . ' ' . '&lt;' . $email . '&gt;';
elseif ( $name ):
    echo $author_text . $name;
elseif ( $email ):
    echo $author_text . '&lt;' . $email . '&gt;';
else:
    echo $author_text . _x( 'Annonymous User', 'notify email', 'WPBDM' );
endif; ?>
