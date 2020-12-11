<?php
/**
 * Social list template
 *
 * @package BDP/Templates/parts
 */

$social_fields = $fields->filter( 'social' );
$html          = $social_fields->html;
if ( $social_fields && ! empty( $html ) ) {
	?>
	<div class="social-fields cf">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
		?>
	</div>
	<?php
}
