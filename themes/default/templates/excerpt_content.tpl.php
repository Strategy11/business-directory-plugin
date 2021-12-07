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

<div class="excerpt-content wpbdp-hide-title">
	<?php include WPBDP_PATH . 'templates/excerpt_content.tpl.php'; ?>
</div>
