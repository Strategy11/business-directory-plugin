<?php
	echo wpbdp_admin_header(null, null, wpbdp_get_option('payments-on') ? array(
		array(_x('Add New Listing Fee', 'fees admin', 'WPBDM'), esc_url(add_query_arg('action', 'addfee'))),
	) : null);
?>
	<?php wpbdp_admin_notices(); ?>

	<?php if (!wpbdp_get_option('payments-on')): ?>
		<p><?php _ex('Payments are currently turned off. To manage fees you need to go to the Manage Options page and check the box next to \'Turn on payments\' under \'General Payment Settings\'', 'fees admin', 'WPBDM'); ?></p>
	<?php else: ?>

		<?php $table->views(); ?>
		<?php $table->display(); ?>

		<hr />
		<p>
			<b><?php __('Installed Payment Gateway Modules', 'WPBDM'); ?></b>

			<ul>
				<?php if (wpbdp_payments_api()->has_gateway('googlecheckout')): ?>
					<li style=\"background:url($wpbusdirman_imagesurl/check.png) no-repeat left center; padding-left:30px;\">" . __("Google Checkout","WPBDM") . "</li>"
				<?php endif; ?>
		{
			$html .= "<li style=\"background:url($wpbusdirman_imagesurl/check.png) no-repeat left center; padding-left:30px;\">" . __("Google Checkout","WPBDM") . "</li>";
		}
		if($wpbusdirman_haspaypalmodule == 1)
		{
			$html .= "<li style=\"background:url($wpbusdirman_imagesurl/check.png) no-repeat left center; padding-left:30px;\">" . __("PayPal","WPBDM") . "</li>";
		}
		if($wpbusdirman_hastwocheckoutmodule == 1)
		{
			$html .= "<li style=\"background:url($wpbusdirman_imagesurl/check.png) no-repeat left center; padding-left:30px;\">" . __("2Checkout","WPBDM") . "</li>";
		}				
			</ul>

		</p>
	<?php endif; ?>

<?php echo wpbdp_admin_footer(); ?>
<?php
/* TODO
		$html .= '<hr />';
		$html .= "<p><b>" . __("Installed Payment Gateway Modules","WPBDM") . "</b><ul>";
		if($wpbusdirman_hasgooglecheckoutmodule == 1)
		{
			$html .= "<li style=\"background:url($wpbusdirman_imagesurl/check.png) no-repeat left center; padding-left:30px;\">" . __("Google Checkout","WPBDM") . "</li>";
		}
		if($wpbusdirman_haspaypalmodule == 1)
		{
			$html .= "<li style=\"background:url($wpbusdirman_imagesurl/check.png) no-repeat left center; padding-left:30px;\">" . __("PayPal","WPBDM") . "</li>";
		}
		if($wpbusdirman_hastwocheckoutmodule == 1)
		{
			$html .= "<li style=\"background:url($wpbusdirman_imagesurl/check.png) no-repeat left center; padding-left:30px;\">" . __("2Checkout","WPBDM") . "</li>";
		}
		$html .= "</ul></p>";
		if(!$wpbusdirman_haspaypalmodule && !$wpbusdirman_hastwocheckoutmodule && !$wpbusdirman_hasgooglecheckoutmodule)
		{
			$hasnomodules=1;
			$html .= "<p>" . __("It does not appear you have any of the payment gateway modules installed. You need to purchase a payment gateway module in order to charge a fee for listings. To purchase payment gateways use the buttons below or visit","WPBDM") . "</p>";
			$html .= "<p><a href=\"http://businessdirectoryplugin.com/premium-modules/\">http://businessdirectoryplugin.com/premium-modules/</a></p>";
		}

			if($wpbusdirman_hastwocheckoutmodule != 1
				|| $wpbusdirman_haspaypalmodule != 1 )
			{
				$html .= '<div style="width:100%;padding:10px;">';
				if(!($wpbusdirman_haspaypalmodule == 1))
				{
					$html .= '<div style="float:left;width:22%;padding:10px;">' . __("You can buy the PayPal gateway module to add PayPal as a payment option for your users.","WPBDM") . '<span style="display:block;color:red;padding:10px 0;font-size:22px;font-weight:bold;text-transform:uppercase;"><a href="http://businessdirectoryplugin.com/premium-modules/paypal-module/" style="color:green;">' . __("$49.99","WPBDM") . '</a></span></div>';
				}
				if(!($wpbusdirman_hastwocheckoutmodule == 1))
				{
					$html .= '<div style="float:left;width:22%;padding:10px;">' . __("You can buy the 2Checkout gateway module to add 2Checkout as a payment option for your users.","WPBDM") . '<span style="display:block;padding:10px 0;font-size:22px;font-weight:bold;text-transform:uppercase;"><a href="http://businessdirectoryplugin.com/premium-modules/2checkout-module/" style="color:green;">' . __("$49.99","WPBDM") . '</a></span></div>';
				}
				if($wpbusdirman_hastwocheckoutmodule
					!= 1 && $wpbusdirman_haspaypalmodule != 1 )
				{
					$html .= '<div style="float:left;width:22%;padding:10px;"><span style="color:red;font-weight:bold;text-transform:uppercase;">' . __("Save $20","WPBDM") . '</span>' . __(" on your purchase of both the Paypal and the 2Checkout gateway modules","WPBDM") . '<br/><b>' . __('(Single Site License Combo Pack)', 'WPBDM') .'</b><span style="display:block;padding:10px 0;font-size:22px;color:red;font-weight:bold;text-transform:uppercase;"><a href="http://businessdirectoryplugin.com/premium-modules/business-directory-combo-pack/" style="color:green;">' . __("$79.99","WPBDM") . '</a></span></div>';
					$html .= '<div style="float:left;width:22%;padding:10px;"><span style="color:red;font-weight:bold;text-transform:uppercase;">' . __("Save","WPBDM") . '</span>' . __(" on your purchase of both the Paypal and the 2Checkout gateway modules","WPBDM") . '<br/><b>' . __('(Multi Site License Combo Pack)', 'WPBDM') .'</b><span style="display:block;padding:10px 0;font-size:22px;color:red;font-weight:bold;text-transform:uppercase;"><a href="http://businessdirectoryplugin.com/premium-modules/business-directory-combo-pack-multi-site/" style="color:green;">' . __("$119.00","WPBDM") . '</a></span></div>';
				}
				$html .= '</div><div style="clear:both;"></div>';
			}*/