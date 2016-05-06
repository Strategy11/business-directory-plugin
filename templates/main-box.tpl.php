<div id="wpbdp-main-box">

<div class="main-fields box-row">
    <form action="<?php echo $search_url; ?>" method="post">
        <label for="wpbdp-main-box-keyword-field">Find listings for:</label>
        <input type="text" id="wpbdp-main-box-keyword-field" name="q" placeholder="Keywords" />
        <?php echo $extra_fields; ?>
        <input type="submit" />
            <a href="<?php echo $search_url; ?>">Advanced Search</a>
    </form>
</div>

<div class="box-row with-separator">
    <?php wpbdp_the_main_links(); ?>
</div>

</div>
