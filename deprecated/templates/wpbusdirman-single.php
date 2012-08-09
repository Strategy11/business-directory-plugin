<?php get_header(); ?>

	<div id="wpbdmentry"><div class="fixpadding">
<?php 	if (have_posts())
	{
		while (have_posts())
		{
			the_post();
?>

<div id="lco">
<div class="title"><?php the_title();?></div>
<div class="buttonform"><?php print(wpbusdirman_post_menu_buttons());?></div>
<div style="clear:both;"></div></div>
	<div class="wpbdmsingleimages">
<?php
			print(wpbusdirman_post_main_image());
			print(wpbusdirman_post_extra_thumbnails());
?>
	</div><!--end div wpbdmsingleimages -->
	<div class="wpbdmsingledetails">
<?php

			print(wpbusdirman_post_single_listing_details());
?>
	</div><!-- close div wpbdmsingledetails-->
	<div style="clear:both;"></div>
	<p class="postmetadata">
		<?php _e("This listing was submitted","WPBDM"); ?>
		<?php _e("on","WPBDM");?> <?php the_time('l, F jS, Y') ?> <?php _e("at","WPBDM");?> <?php the_time() ?>
	</p>
	<p>
		<?php _e("You can follow any responses to this listing through the","WPBDM");?> <?php post_comments_feed_link('RSS 2.0'); ?> <?php _e("feed","WPBDM");?>.
<?php
			if ( comments_open()
				&& pings_open() )
			{
?>
		<?php _e("You can","WPBDM");?> <a href="#respond"><?php _e("leave a response","WPBDM");?></a>, <?php _e("or","WPBDM");?> <a href="<?php trackback_url(); ?>" rel="trackback"><?php _e("trackback","WPBDM");?></a> <?php _e("from your own site","WPBDM");?>.
<?php
			}
			elseif ( !comments_open()
				&& pings_open() )
			{
?>
		<?php _e("Responses are currently closed, but you can","WPBDM");?> <a href="<?php trackback_url(); ?> " rel="trackback"><?php _e("trackback","WPBDM");?></a> <?php _e("from your own site","WPBDM");?>.
<?php
			}
			elseif ( comments_open()
				&& !pings_open() )
			{
?>
		<?php _e("You can skip to the end and leave a response. Pinging is currently not allowed","WPBDM");?>.
<?php
			}
			elseif ( !comments_open()
				&& !pings_open() )
			{
				// Neither Comments, nor Pings are open ?>
		<?php _e("Both comments and pings are currently closed","WPBDM");?>.
<?php
			}
?>
	</p>

	<?php comments_template(); ?>
	<div style="clear:both;"></div>

<?php
		} // End while
	}
	else
	{
?>
	<p><?php _e('Sorry, no posts matched your criteria.', 'WPBDM'); ?></p>
	<!--end wpbusdirmantemplate-->
<?php
	}
?>
	<div style="clear:both;"></div>
	</div></div><!--close div wpbdmentry--><!--close div fixpadding-->


<?php get_footer(); ?>