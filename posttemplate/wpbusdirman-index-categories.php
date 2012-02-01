<div id="wpbdmentry">

<?php global $wpbdmposttype;?>
<div id="lco">
<div class="title">
<form id="wpbdmsearchform" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
<input id="intextbox" maxlength="150" name="s" size="20" type="text" value="" />
<input name="post_type" type="hidden" value="<?php echo $wpbdmposttype; ?>" />
<input id="wpbdmsearchsubmit" class="wpbdmsearchbutton" type="submit" value="Search Listings" />
</form>
</div>
<div class="button"><?php print(wpbusdirman_post_menu_button_submitlisting());?>
<?php print(wpbusdirman_post_menu_button_viewlistings());?></div>
<div style="clear:both;"></div></div>

<div id="wpbusdirmancats">
	<div style="clear:both;"></div>
	<ul><?php print(wpbusdirman_post_list_categories()); ?></ul>
</div>
<div style="clear:both;"></div>
<br />
</div>
