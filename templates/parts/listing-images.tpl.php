<?php
/**
 * Extra images template
 *
 * @package BDP/Templates/parts
 */

if ( ! $images ) {
	return;
}
?>
<div class="extra-images">
	<ul>
		<?php foreach ( $images as $img ) : ?>
			<li>
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $img->html;
				?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
