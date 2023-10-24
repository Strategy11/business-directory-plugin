<?php
$args = array(
	'field_name'  => ! isset( $field_name ) ? 'listing_plan' : $field_name,
	'categories'  => ! isset( $categories ) ? array() : $categories,
	'selected'    => ! empty( $selected ) ? $selected : 0,
	'plans_count' => count( $plans ),
	'echo'        => true,
);

if ( 1 === $args['plans_count'] ) {
	$args['display_only'] = true;
}
?>
<div class="wpbdp-plan-selection-list">
	<?php
	foreach ( $plans as $plan ) {
		$args['plan'] = $plan;

		$args['disabled'] = $plan->recurring && wpbdp_user_is_admin();

		wpbdp_render( 'plan-selection-plan', $args );
	}
	?>
</div>
