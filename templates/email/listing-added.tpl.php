<?php
    _ex( 'A new listing has been submitted to the directory. Listing details can be found below.', 'emails', 'WPBDM' );
?>

----

<?php _ex('ID', 'notify email', 'WPBDM'); ?>: <?php echo $listing->get_id(); ?>


<?php _ex('Title', 'notify email', 'WPBDM'); ?>: <?php echo $listing->get_title(); ?>


<?php _ex('URL', 'notify email', 'WPBDM'); ?>: <?php echo $listing->is_published() ? $listing->get_permalink() : _x( '(not published yet)', 'notify email', 'WPBDM' ); ?>


<?php _ex('Categories', 'notify email', 'WPBDM'); ?>: <?php foreach ( $listing->get_categories( 'all' ) as $category ): ?><?php echo $category->name; ?> / <?php endforeach; ?>


<?php _ex('Posted By', 'notify email', 'WPBDM'); ?>: <?php echo $listing->get_author_meta( 'user_login' ); ?> (<?php echo $listing->get_author_meta( 'user_email' ); ?>)
