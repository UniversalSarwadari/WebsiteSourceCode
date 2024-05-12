<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Barn2\Plugin\WC_Bulk_Variations\Util\Settings;

global $post;

$global_settings  = Settings::get_setting( Settings::OPTION_VARIATIONS_DATA );
$global_structure = Settings::get_setting( Settings::OPTION_VARIATIONS_STRUCTURE );

$override = filter_var( get_post_meta( $post->ID, Settings::OPTION_VARIATIONS_DATA . '_override', true ), FILTER_VALIDATE_BOOLEAN );

$settings = get_post_meta( $post->ID, Settings::OPTION_VARIATIONS_DATA, true );

if ( ! $settings ) {
	$settings = [];
}

$settings = wp_parse_args( $settings, $global_settings );

$structure = get_post_meta( $post->ID, Settings::OPTION_VARIATIONS_STRUCTURE, true );

if ( ! $structure ) {
	$structure = [];
}

$structure = wp_parse_args( array_filter( $structure ), $global_structure );

$rows                = isset( $structure['rows'] ) ? $structure['rows'] : null;
$columns             = isset( $structure['columns'] ) ? $structure['columns'] : null;
$hide_add_to_cart    = isset( $settings['hide_add_to_cart'] ) ? $settings['hide_add_to_cart'] : 0;
$disable_purchasing  = isset( $settings['disable_purchasing'] ) ? $settings['disable_purchasing'] : 0;
$variation_images    = isset( $settings['variation_images'] ) ? $settings['variation_images'] : 'off';
$variation_attribute = isset( $settings['variation_attribute'] ) ? $settings['variation_attribute'] : 0;

$product    = wc_get_product( $post->ID );
$attributes = [];

$default_enable = false;

if ( is_a( $product, 'WC_Product_Variable' ) ) {
	$attributes = $product->get_variation_attributes();

	if ( count( $attributes ) < 3 ) {
		$default_enable = $settings['enable'];
	} else {
		$default_enable = $settings['enable_multivariation'];
	}
}

$enable = filter_var( get_post_meta( $post->ID, Settings::OPTION_VARIATIONS_DATA . '_enable', true ), FILTER_VALIDATE_BOOLEAN );

if ( ! $override ) {
	$enable = $default_enable;
}

$single_attribute  = ( count( $attributes ) === 1 ) ? true : false;
$attribute_options = [ '' => __( 'Select attribute', 'woocommerce-bulk-variations' ) ];

foreach ( $attributes as $name => $values ) {
	$attribute_taxonomy = get_taxonomy( $name );

	if ( $attribute_taxonomy ) {
		$attribute_options[ $attribute_taxonomy->name ] = $attribute_taxonomy->labels->singular_name;
	} else {
		$attribute_options[ $name ] = $name;
	}
}

?>

<div id="bulk_variations_product_data" class="panel woocommerce_options_panel hidden">

	<div class="options_group <?php echo $single_attribute ? 'single-attribute' : ''; ?>">

		<?php

		woocommerce_wp_checkbox(
			[
				'id'            => Settings::OPTION_VARIATIONS_DATA . '_override',
				'wrapper_class' => '',
				'label'         => __( 'Product-level control', 'woocommerce-bulk-variations' ),
				'description'   => __( 'Override the global settings for this product.', 'woocommerce-bulk-variations' ),
				'cbvalue'       => '1',
				'desc_tip'      => false,
				'value'         => $override,
			]
		);

		echo '<div id="' . Settings::OPTION_VARIATIONS_DATA . '_div">'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		woocommerce_wp_checkbox(
			[
				'id'            => Settings::OPTION_VARIATIONS_DATA . '_enable',
				'wrapper_class' => '',
				'label'         => __( 'Enable', 'woocommerce-bulk-variations' ),
				'description'   => __( 'Enable the bulk variation grid for this product', 'woocommerce-bulk-variations' ),
				'cbvalue'       => '1',
				'desc_tip'      => false,
				'value'         => $enable,
			]
		);

		echo '<div id="' . Settings::OPTION_VARIATIONS_DATA . '_hide_add_to_cart_div">'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		woocommerce_wp_checkbox(
			[
				'id'            => Settings::OPTION_VARIATIONS_DATA . '[hide_add_to_cart]',
				'wrapper_class' => '',
				'label'         => __( 'Hide default add to cart', 'woocommerce-bulk-variations' ),
				'description'   => __( 'Remove the default variation dropdowns and add to cart button (recommended if you are using a shortcode to insert the variations grid manually).', 'woocommerce-bulk-variations' ),
				'cbvalue'       => '1',
				'desc_tip'      => true,
				'value'         => $hide_add_to_cart,
			]
		);

		echo '</div>';

		woocommerce_wp_checkbox(
			[
				'id'            => Settings::OPTION_VARIATIONS_DATA . '[disable_purchasing]',
				'wrapper_class' => '',
				'label'         => __( 'Disable purchasing', 'woocommerce-bulk-variations' ),
				'description'   => __( 'Display the variations grid without quantity boxes or add to cart button.', 'woocommerce-bulk-variations' ),
				'cbvalue'       => '1',
				'desc_tip'      => true,
				'value'         => $disable_purchasing,
			]
		);

		woocommerce_wp_select(
			[
				'id'            => Settings::OPTION_VARIATIONS_STRUCTURE . '_columns',
				'wrapper_class' => 'hide-if-single',
				'name'          => Settings::OPTION_VARIATIONS_STRUCTURE . '[columns]',
				'label'         => __( 'Horizontal', 'woocommerce-bulk-variations' ),
				'options'       => $attribute_options,
				'description'   => 'Select which attribute to use as the columns for the variations grid.',
				'desc_tip'      => true,
				'value'         => $columns,
				'class'         => 'wcbvp-select wcbvp-attribute-selector hide-if-single',
			]
		);

		woocommerce_wp_select(
			[
				'id'            => Settings::OPTION_VARIATIONS_STRUCTURE . '_rows',
				'wrapper_class' => 'hide-if-single',
				'name'          => Settings::OPTION_VARIATIONS_STRUCTURE . '[rows]',
				'label'         => __( 'Vertical', 'woocommerce-bulk-variations' ),
				'options'       => $attribute_options,
				'description'   => 'Select which attribute to use as the rows for the variations grid.',
				'desc_tip'      => true,
				'value'         => $rows,
				'class'         => 'wcbvp-select wcbvp-attribute-selector hide-if-single',
			]
		);

		woocommerce_wp_select(
			[
				'id'            => Settings::OPTION_VARIATIONS_DATA . '_variation_attribute',
				'wrapper_class' => 'show-if-single',
				'name'          => Settings::OPTION_VARIATIONS_DATA . '[variation_attribute]',
				'label'         => __( 'Single attribute layout', 'woocommerce-bulk-variations' ),
				'options'       => [
					''     => __( 'Horizontal', 'woocommerce-bulk-variations' ),
					'vert' => __( 'Vertical', 'woocommerce-bulk-variations' ),
				],
				'description'   => 'Choose the orientation of the single-attribute grid',
				'desc_tip'      => true,
				'value'         => $variation_attribute,
				'class'         => 'wcbvp-select',
			]
		);

		woocommerce_wp_select(
			[
				'id'            => Settings::OPTION_VARIATIONS_DATA . '[variation_images]',
				'wrapper_class' => '',
				'label'         => __( 'Variation images', 'woocommerce-bulk-variations' ),
				'description'   => __( 'Display an image for each variation.', 'woocommerce-bulk-variations' ),
				'options'       => [
					'off' => __( 'Do not show any images', 'woocommerce-bulk-variations' ),
					'col' => __( 'Show horizontally', 'woocommerce-bulk-variations' ),
					'row' => __( 'Show vertically', 'woocommerce-bulk-variations' ),
				],
				'desc_tip'      => true,
				'value'         => $variation_images,
				'class'         => 'wcbvp-select',
			]
		);

		echo '</div>';

		?>
	</div>

</div>
