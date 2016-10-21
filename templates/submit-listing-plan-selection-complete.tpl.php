<?php
$plan = $listing->get_fee_plan();
?>


<?php echo $plan->fee_label; ?><br />
<?php echo wpbdp_format_currency( $plan->fee_price ); ?>
