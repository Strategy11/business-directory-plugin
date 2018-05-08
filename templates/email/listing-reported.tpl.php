<?php
    _x( 'A listing has been reported as inappropriate. Listing details can be found below.', 'emails', 'WPBDM' );
?>

----

<?php _ex( 'Listing information', 'notify email', 'WPBDM' ); ?>:

<?php _ex( 'ID', 'notify email', 'WPBDM' ); ?>: <?php echo $listing->get_id(); ?>

<?php _ex( 'Title', 'notify email', 'WPBDM' ); ?>: <?php echo $listing->get_title(); ?>

<?php _ex( 'URL', 'notify email', 'WPBDM' ); ?>: <?php echo $listing->is_published() ? $listing->get_permalink() : _x( '(not published yet)', 'notify email', 'WPBDM' ); ?>

<?php _ex( 'Admin URL', 'notify email', 'WPBDM' ); ?>: <?php echo wpbdp_get_edit_post_link( $listing->get_id() ); ?>

<?php _ex( 'Categories', 'notify email', 'WPBDM' ); ?>: <?php foreach ( $listing->get_categories() as $category ): ?><?php echo $category->name; ?> / <?php endforeach; ?>

<?php _ex( 'Posted By', 'notify email', 'WPBDM' ); ?>: <?php echo $listing->get_author_meta( 'user_login' ); ?> (<?php echo $listing->get_author_meta( 'user_email' ); ?>)

<?php _ex( 'Report Information', 'notify email', 'WPBDM' ); ?>:

<?php if( ! empty( $report['name'] ) ): ?>
    <?php _ex( 'User name', 'notify email', 'WPBDM' ); ?>: <?php echo $report['name'] ?>

<?php endif; ?>
<?php if( ! empty( $report['email'] ) ): ?>
    <?php _ex( 'User Email', 'notify email', 'WPBDM' ); ?>: <?php echo $report['email'] ?>

<?php endif; ?>
<?php _ex( 'Report IP', 'notify email', 'WPBDM' ); ?>: <?php echo $report[ 'ip' ]; ?>

<?php _ex( 'Report selected option', 'notify email', 'WPBDM' ); ?>: <?php echo $report[ 'reason' ]; ?>

<?php echo isset( $report[ 'comments' ] ) && '' != $report[ 'comments' ] ? _x( 'Report additional info', 'notify email', 'WPBDM' ) . ': ' . $report[ 'comments' ] : ''; ?>