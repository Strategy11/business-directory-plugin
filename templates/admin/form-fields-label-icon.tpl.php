<?php
/**
 * Form fields icon autocomplete
 *
 * @package BDP/Templates/Admin/Label Font autocomplete
 */

?>

<?php // TODO: Remove style attribute. ?>
<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" class="<?php echo esc_attr( implode( ' ', $class ) ); ?>" data-configuration="<?php echo esc_attr( wp_json_encode( $configuration ) ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>" style="width: 100%">
    <?php if ( $default ) : ?>
        <option value=""><?php echo esc_html( $default ); ?></option>
    <?php endif; ?>
    <?php foreach ( $icons as $key => $icon ) : ?>
        <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $selected, $key ); ?>><?php echo $icon; ?></option>
    <?php endforeach; ?>
</select>
