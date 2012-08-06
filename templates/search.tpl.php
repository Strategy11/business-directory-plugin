<div id="wpbdp-search-page" class="wpbdp-search-page businessdirectory-search businessdirectory wpbdp-page">
    <div class="wpbdp-bar cf">
        <?php wpbdp_the_main_links(); ?>
    </div>

    <h2 class="title"><?php _ex('Search', 'search', 'WPBDM'); ?></h2>

<?php // if (!$searching): ?>
<h3><?php _ex('Find a listing', 'templates', 'WPBDM'); ?></h3>
<!-- <Search Form> -->
<form action="" id="wpbdp-search-form" method="POST">
    <div class="search-filter term">
        <div class="label">
            <label for="wpbdp-search-form-q">
                <?php _ex('Description/Title Keywords', 'search', 'WPBDM'); ?>
            </label>
        </div>
        <div class="field">
            <input type="text"
                   name="q"
                   id="wpbdp-search-form-q"
                   value="<?php echo wpbdp_getv($_POST, 'q', ''); ?>" />
        </div>
    </div>

    <?php
    foreach ($fields as $field):
        $post_values = isset($_POST['meta'][$field->id]) ? $_POST['meta'][$field->id] : array();
    ?>
    <div class="search-filter <?php echo $field->type; ?>">
        <?php if (in_array($field->type, array('checkbox', 'select', 'multiselect'))) : ?>
        <div class="label">
            <label><?php echo $field->label; ?></label>
        </div>

        <?php
        $options = isset($field->field_data['options']) ? $field->field_data['options'] : array();
        $use_select = count($options) > 10 ? true : false;
        ?>

        <div class="field">
            <?php if ($use_select) : ?>
            <select name="meta[<?php echo $field->id; ?>][options][]" multiple="multiple" class="field">
            <?php endif; ?>

            <?php foreach ($options as $option): ?>
                <?php if ($use_select): ?>
                <option value="<?php echo $option; ?>"
                        <?php echo (isset($post_values['options']) && in_array($option, $post_values['options'])) ? ' selected="selected"' : ''; ?>>
                    <?php echo $option; ?>
                </option>
                <?php else: ?>
                    <label>
                        <input type="checkbox" 
                               name="meta[<?php echo $field->id; ?>][options][]"
                               value="<?php echo $option; ?>"
                               <?php echo (isset($post_values['options']) && in_array($option, $post_values['options'])) ? ' checked="checked"' : ''; ?>
                               /> <?php echo $option; ?>
                    </label> <br />
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($use_select): ?>
            </select>
            <?php endif; ?>
        </div>

        <?php else : ?>
            <div class="label">
                <label for="wpbdp-search-form-<?php echo $field->id; ?>"><?php echo sprintf(_x('%s like', 'search', 'WPBDM'), $field->label); ?></label>
            </div>
            <div class="field">
                <input type="text" name="meta[<?php echo $field->id; ?>][q]" value="<?php echo esc_attr((isset($post_values['q'])) ? $post_values['q'] : ''); ?>" id="wpbdp-search-form-<?php echo $field->id; ?>" />
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <p>
        <input type="submit" value="<?php _ex('Search', 'search', 'WPBDM'); ?>" />
    </p>
</form>
<!-- </Search Form> -->
<?php // endif; ?>

<?php if ($searching): ?>
<h3><?php _ex('Search Results', 'search', 'WPBDM'); ?></h3>
<div class="search-results">
<?php if (have_posts()): ?>
    <?php echo wpbdp_render('businessdirectory-listings'); ?>
<?php else: ?>
    <?php _ex("No listings found.", 'templates', "WPBDM"); ?>
    <br />
    <?php echo sprintf('<a href="%s">%s</a>.', wpbdp_get_page_link('main'),
                       _x('Return to directory', 'templates', 'WPBDM')); ?>    
<?php endif; ?>
</div>
<?php endif; ?>

</div>