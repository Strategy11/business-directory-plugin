<?php
/**
 * Listing detail view rendering template
 *
 * @package BDP/Templates/Single Content
 */

?>
<?php if ( $images->main || $images->thumbnail ) : ?>
    <?php echo $images->main ? $images->main->html : $images->thumbnail->html; ?>
<?php endif; ?>

<div class="listing-details cf<?php echo esc_attr( ( $images->main || $images->thumbnail ) ? '' : ' wpbdp-no-thumb' ); ?>">
	<?php if( function_exists( 'ADDTOANY_SHARE_SAVE_KIT' ) && get_option( 'addtoany_options', array() ) ['position'] !== null && get_option( 'addtoany_options', array() ) ['position'] == 'both' ||  get_option( 'addtoany_options', array() ) ['position'] == 'top') {
		ADDTOANY_SHARE_SAVE_KIT();
	} ?>
	<?php foreach ( $fields->not( 'social' ) as $field ) : ?>
        <?php echo $field->html; ?>
    <?php endforeach; ?>

	<?php wpbdp_x_part( 'parts/listing-socials' ); ?>
	<?php if( function_exists( 'ADDTOANY_SHARE_SAVE_KIT' ) && get_option( 'addtoany_options', array() ) ['position'] !== null && get_option( 'addtoany_options', array() ) ['position'] == 'both' ||  get_option( 'addtoany_options', array() ) ['position'] == 'bottom') {
		ADDTOANY_SHARE_SAVE_KIT();
	} ?>
</div>

<?php wpbdp_x_part( 'parts/listing-images' ); ?>
