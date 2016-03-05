<?php _ex( 'Below you\'ll find the access keys for all the listings registered with your e-mail address on our site.', 'request_access_keys', 'WPBDM' ); ?>

<?php foreach ( $listings as $l ): ?>
    <?php echo $l->get_title(); ?>
      <?php _ex( 'Access Key:', 'request_access_keys', 'WPBDM' ); ?> <?php echo $l->get_access_key(); ?>
      <?php _ex( 'URL:', 'request_access_keys', 'WPBDM' ); ?> <?php echo $l->get_permalink(); ?>

<?php endforeach; ?>

<?php echo $site_title; ?>
