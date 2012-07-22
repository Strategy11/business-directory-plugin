<?php
?>
<div class="sidebar">
	<div class="meta-box-sortables metabox-holder ui-sortable" id="side-sortables">
		<!-- Like this plugin? -->
		<div class="postbox">
			<h3 class="hndle"><span>Like this plugin?</span></h3>
			<div class="inside">
				<p>Why not do any or all of the following:</p>
				<ul>
				    <li class="li_link"><a href="http://wordpress.org/extend/plugins/business-directory-plugin/">Give it a good rating on WordPress.org.</a></li>
				    <li class="li_link"><a href="http://wordpress.org/extend/plugins/business-directory-plugin/">Let other people know that it works with your WordPress setup.</a></li>
				    <li class="li_link"><a href="http://businessdirectoryplugin.com/premium-modules/">Buy a Premium Module</a></li>
			    </ul>
			</div>
		</div>

		<!-- Premium modules -->
		<div class="postbox premium-modules">
			<h3 class="hndle"><span>Get a Premium Module</span></h3>
			<div class="inside">
				<ul>
				    <li class="li_link"><a href="http://businessdirectoryplugin.com/premium-modules/paypal-module/">PayPal Payment Gateway Module</a></li>
				    <li class="li_link"><a href="http://businessdirectoryplugin.com/premium-modules/2checkout-module/">2Checkout Payment Gateway Module</a></li>
				    <li class="li_link"><a href="http://businessdirectoryplugin.com/premium-modules/business-directory-combo-pack/">Single Site License Combo Pack</a></li>
				    <li class="li_link"><a href="http://businessdirectoryplugin.com/premium-modules/business-directory-combo-pack-multi-site/">Multi Site License Combo Pack</a></li>
				    <li class="li_link"><a href="http://businessdirectoryplugin.com/premium-modules/google-maps-module/"><?php _ex('Google Maps Module', 'admin sidebar', 'WPBDM'); ?></a></li>
			    </ul>
			</div>
		</div>

		<!-- Installed modules -->
		<div class="postbox installed-modules">
			<h3 class="hndle"><span>Installed Modules</span></h3>
			<div class="inside">
				<ul>
				    <li class="li_link">
				    	<a href="http://businessdirectoryplugin.com/premium-modules/paypal-module/">PayPal Payment Gateway</a>:<br />
				    	<?php echo wpbdp()->has_module('paypal') ? _x('Installed', 'admin sidebar', 'WPBDM') : _x('Not Installed', 'admin sidebar', 'WPBDM'); ?>
				    </li>
				    <li class="li_link">
				    	<a href="http://businessdirectoryplugin.com/premium-modules/2checkout-module/">2Checkout Payment Gateway</a>:<br />
				    	<?php echo wpbdp()->has_module('2checkout') ? _x('Installed', 'admin sidebar', 'WPBDM') : _x('Not Installed', 'admin sidebar', 'WPBDM'); ?>
				    </li>
				    <li class="li_link">
				    	<a href="http://businessdirectoryplugin.com/premium-modules/google-maps-module/"><?php _ex('Google Maps Module', 'admin sidebar', 'WPBDM'); ?></a>:<br />
				    	<?php echo wpbdp()->has_module('googlemaps') ? _x('Installed', 'admin sidebar', 'WPBDM') : _x('Not Installed', 'admin sidebar', 'WPBDM'); ?>
				    </li>				    
			    </ul>
			</div>
		</div>

		<!-- Support -->
		<div class="postbox">
			<h3 class="hndle"><span>Found a bug? Need support?</span></h3>
			<div class="inside">
				<p>If you've found a bug or need support <a href="http://businessdirectoryplugin.com/forums/" target="_blank">visit the forums!</a></p>
			</div>
		</div>
	</div>
</div>