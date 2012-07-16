<div id="wpbdmentry">

    <div id="lco">
        <div class="title"><?php _ex('Find a listing', 'search', 'WPBDM'); ?></div>
        <div class="button">
            <?php echo wpbusdirman_post_menu_button_viewlistings(); ?>
            <?php echo wpbusdirman_post_menu_button_directory(); ?>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="clear"></div>

<form action="" id="wpbdp-search-form" method="POST">
    <label>
        <?php _ex('Search terms:', 'search', 'WPBDM'); ?> <input type="text" name="q" value="<?php echo wpbdp_getv($_POST, 'q', ''); ?>" />
    </label>

    <?php
    foreach ($fields as $field):
        $post_values = isset($_POST['meta'][$field->id]) ? $_POST['meta'][$field->id] : array();
    ?>
    <div class="search-filter <?php echo $field->type; ?>">
        <h3 class="header">
            <label><input type="checkbox"
                          name="meta[<?php echo $field->id; ?>][enabled]"
                          value="1"
                          <?php echo isset($_POST['meta'][$field->id]['enabled']) ? ' checked="checked"' : ''; ?>
                          /> 
            <?php echo sprintf(_x('Filter by <i>%s</i>', 'search', 'WPBDM'), $field->label); ?></i></label>
        </h3>
        <div class="options">
            <?php if (in_array($field->type, array('checkbox', 'select', 'multiselect'))) : ?>
                <?php
                $options = isset($field->field_data['options']) ? $field->field_data['options'] : array();
                $use_select = count($options) > 10 ? true : false;
                ?>

                <?php if ($use_select) : ?>
                <select name="meta[<?php echo $field->id; ?>][options][]" multiple="multiple">
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

            <?php else : ?>
                <label>
                <?php echo sprintf(_x('%s like', 'search', 'WPBDM'), $field->label); ?>
                    <input type="text"
                           name="meta[<?php echo $field->id; ?>][q]"
                           value="<?php echo esc_attr((isset($post_values['q'])) ? $post_values['q'] : ''); ?>" />
                </label>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <input type="submit" value="<?php _ex('Search', 'search', 'WPBDM'); ?>"/>
</form>

<br class="clearfix" />

<?php if ($searching): ?>
<h2><?php _ex('Search Results', 'search', 'WPBDM'); ?></h2>
<div class="search-results">
<?php if (have_posts()): ?>
    <?php while(have_posts()): the_post(); ?>
        <?php echo wpbusdirman_post_excerpt(); ?>
    <?php endwhile; ?>
<?php else: ?>
    <?php _ex("No listings found.", 'templates', "WPBDM"); ?>
    <br />
    <?php echo sprintf('<a href="%s">%s</a>.', wpbdp_get_page_link('main'),
                       _x('Return to directory', 'templates', 'WPBDM')); ?>    
<?php endif; ?>
</div>
<?php endif; ?>

</div>