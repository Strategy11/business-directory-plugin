<?php
/**
 * Listings except content template
 *
 * @package BDP/Themes/Default/Templates/Excerpt Content
 */

?>

<div class="listing-title">
	<?php echo $fields->t_title->value; ?>
</div>

<div class="excerpt-content">
	<?php wpbdp_x_part( 'parts/excerpt-details' ); ?>
</div>
