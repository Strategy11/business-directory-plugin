<form id="wpbdp-listing-form-extra" class="wpbdp-listing-form" method="POST" action="" enctype="multipart/form-data">
    <input type="hidden" name="_state" value="<?php echo $_state; ?>" />
    <?php echo $output; ?>
    <input type="submit" name="continue-with-save" value="<?php _ex( 'Continue with listing submit', 'templates', 'WPBDM' ); ?> " class="submit" />  
</form>