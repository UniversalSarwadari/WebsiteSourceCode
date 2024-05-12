<?php

namespace WPO\WC\PDF_Invoices_Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\\WPO\\WC\\PDF_Invoices_Templates\\Main' ) ) :

class Main {
	
	protected static $_instance = null;
	
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function __construct() {
		// Load custom styles from settings
		add_action( 'wpo_wcpdf_custom_styles', array( $this, 'custom_template_styles' ) );

		// hook custom blocks to template actions
		add_action( 'wpo_wcpdf_before_document', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_before_shop_name', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_after_shop_name', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_before_shop_address', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_after_shop_address', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_before_document_label', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_after_document_label', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_before_billing_address', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_after_billing_address', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_before_shipping_address', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_after_shipping_address', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_before_order_data', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_after_order_data', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_before_customer_notes', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_after_customer_notes', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_before_order_details', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_after_order_details', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_before_footer', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_after_footer', array( $this, 'custom_blocks_data' ), 10, 2 );
		add_action( 'wpo_wcpdf_after_document', array( $this, 'custom_blocks_data' ), 10, 2 );

		// make replacements in template settings fields
		add_action( 'wpo_wcpdf_footer_settings_text', array( $this, 'settings_fields_replacements' ), 999, 2 );
		add_action( 'wpo_wcpdf_extra_1_settings_text', array( $this, 'settings_fields_replacements' ), 999, 2 );
		add_action( 'wpo_wcpdf_extra_2_settings_text', array( $this, 'settings_fields_replacements' ), 999, 2 );
		add_action( 'wpo_wcpdf_extra_3_settings_text', array( $this, 'settings_fields_replacements' ), 999, 2 );
		add_action( 'wpo_wcpdf_shop_name_settings_text', array( $this, 'settings_fields_replacements' ), 999, 2 );
		add_action( 'wpo_wcpdf_shop_address_settings_text', array( $this, 'settings_fields_replacements' ), 999, 2 );

		// store regular price in item meta
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_regular_item_price' ), 10, 2 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_regular_price_itemmeta' ) );

		// store rate percentage in tax meta
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_tax_rate_percentage_frontend' ), 10, 2 );
		add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'save_tax_rate_percentage_recalculate' ), 10, 2 );

		// sort items on documents
		add_action( 'wpo_wcpdf_order_items_data', array( $this, 'sort_items' ), 10, 3 );

		// add PHP intl extension check to system configuration
		add_filter( 'wpo_wcpdf_server_configs', array( $this, 'php_intl_check' ), 10, 1 );
	}

	/**
	 * Load custom styles from settings
	 */
	public function custom_template_styles ( $template_type, $document = null ) {
		$editor_settings = get_option('wpo_wcpdf_editor_settings');
		if (isset($editor_settings['custom_styles'])) {
			echo $editor_settings['custom_styles'];
		}
	}

	public function sort_items ( $items, $order, $template_type ) {
		$editor_settings = get_option('wpo_wcpdf_editor_settings');
		if ( is_array( $editor_settings ) && array_key_exists( 'sort_items', $editor_settings ) ) {
			if ( is_array( $editor_settings['sort_items'] ) && isset($editor_settings['sort_items'][$template_type]) ) {
				$sort_by = $editor_settings['sort_items'][$template_type];

				switch ($sort_by) {
					case "product":
						uasort($items, function ($a, $b) { return strnatcasecmp($a['name'], $b['name']); });
						break;
					case "sku":
						uasort($items, function ($a, $b) {
							$sku_a = !empty( $a['sku'] ) ? $a['sku'] : "";
							$sku_b = !empty( $b['sku'] ) ? $b['sku'] : "";
							return strnatcasecmp($sku_a, $sku_b);
						});
						break;
					case "category":
						uasort($items, function ($a, $b) {
							$categories_a = strip_tags( wc_get_product_category_list( $a['product_id'] ) );
							$categories_b = strip_tags( wc_get_product_category_list( $b['product_id'] ) );
							return strnatcasecmp($categories_a, $categories_b);
						});
						break;
				}
			}
		}
		return $items;
	}

	public function get_totals_table_data ( $total_settings, $document ) {
		$customizer_total_blocks = WPO_WCPDF_Templates()->settings->get_totals_field_options();
		$totals_table_data = array();
		foreach ($total_settings as $total_key => $total_setting) {
			// reset possibly absent vars
			$method = $percent = $base = $show_unit = $only_discounted = $label = $single_total = NULL;
			// extract vars
			extract($total_setting);

			// remove label if empty!
			if( empty($total_setting['label']) ) {
				unset($total_setting['label']);
			} elseif ( !in_array( $type, array( 'fees' ) ) ) {
				$label = $total_setting['label'] = __( $total_setting['label'], 'woocommerce-pdf-invoices-packing-slips' ); // not proper gettext, but it makes it possible to reuse po translations!
			}

			switch ($type) {
				case 'subtotal':
					// $tax, $discount, $only_discounted
					$order_discount = $document->get_order_discount( 'total', 'incl' );
					if ( !$order_discount && isset($only_discounted) ) {
						break;
					}
					switch ($discount) {
						case 'before':
							$totals_table_data[$total_key] = (array) $total_setting + $document->get_order_subtotal( $tax );
							break;

						case 'after':
							// avoid recalculating if we don't have a discount anyway
							if ( !$order_discount ) {
								$totals_table_data[$total_key] = (array) $total_setting + $document->get_order_subtotal( $tax );
							} else {
								$subtotal_value = 0;
								$items = $document->order->get_items();
								if( sizeof( $items ) > 0 ) {
									foreach( $items as $item ) {
										$item_price = $item['line_total'];
										if ( $tax == 'incl' ) {
											// follow WC settings for tax rounding
											if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
												$item_price += wc_round_tax_total( $item['line_tax'] );
											} else {
												$item_price += $item['line_tax'];
											}
										}
										// allow rounding
										if ( apply_filters( 'wpo_wcpdf_templates_subtotal_round_line_items', false ) ) {
											$item_price = round( $item_price, 2 );
										}
										$subtotal_value += $item_price;
									}
								}
								$subtotal_data = array(
									'label'	=> __('Subtotal', 'woocommerce-pdf-invoices-packing-slips'),
									'value'	=> $document->format_price( $subtotal_value ),
								);
								$totals_table_data[$total_key] = (array) $total_setting + $subtotal_data;
							}
							break;
					}
					break;
				case 'discount':
					// $tax, $show_codes, $show_percentage, $breakdown_coupons
					if ( $discount = $document->get_order_discount( 'total', $tax ) ) {
						if ( isset( $discount['raw_value'] ) ) {
							// support for positive discount (=more expensive/price corrections)
							$discount['value'] = $document->format_price( $discount['raw_value'] * -1 );
						} else {
							$discount['value'] = '-'.$discount['value'];
						}

						$discount['label'] = $raw_label = ! empty( $label ) ? $label : $discount['label'];
						unset($total_setting['label']);

						$discount_percentage = $this->get_discount_percentage( $document->order );
						if (isset($show_percentage) && $discount_percentage) {
							$precision = apply_filters( 'wpo_wcpdf_discount_percentage_precision', 0 );
							$discount_percentage = number_format( $discount_percentage, $precision, wc_get_price_decimal_separator(), '' );
							$discount['label'] = "{$discount['label']} ({$discount_percentage}%)";
						}

						if ( version_compare( WOOCOMMERCE_VERSION, '3.7', '<' ) ) { // backwards compatibility
							$used_coupons = implode(', ', $document->order->get_used_coupons() );
						} else {
							$used_coupons = implode(', ', $document->order->get_coupon_codes() );
						}

						// breakdown coupon discounts
						$coupons = $document->order->get_items( 'coupon' );
						if ( isset( $breakdown_coupons ) && ! empty( $coupons ) ) {
							$round_precision = apply_filters( 'wpo_wcpdf_discount_round_precision', 2 );
							$coupon_row      = array();
							$discount_value  = isset( $discount['raw_value'] ) ? floatval( $discount['raw_value'] ) : floatval( $discount['value'] );

							foreach( $coupons as $coupon_id => $coupon ) {
								$coupon_discount      = floatval( $coupon->get_discount() );
								$coupon_discount_tax  = floatval( $coupon->get_discount_tax() );
								$coupon_data          = $coupon->get_meta( 'coupon_data' );
								$coupon_value         = ( $tax == 'incl' ) ? $coupon_discount + $coupon_discount_tax : $coupon_discount;
								$discount_value       = round( $discount_value - $coupon_value, $round_precision );

								// coupon row
								$coupon_row['label']  = $discount['label'];
								$coupon_row['label'] .= isset( $show_percentage ) && isset( $coupon_data['discount_type'] ) && $coupon_data['discount_type'] == 'percent' ? " ({$coupon_data['amount']}%)" : '';
								$coupon_row['label'] .= isset( $show_codes ) ? " ({$coupon->get_code()})" : '';
								$coupon_row['label']  = apply_filters( 'wpo_wcpdf_templates_coupon_total_label', $coupon_row['label'], $coupon, $document ); 
								$coupon_row['value']  = $document->format_price( $coupon_value * -1 );

								$totals_table_data[$total_key.$coupon_id] = (array) $total_setting + $coupon_row;
							}

							// fix $discount value removing coupon values
							if( $discount_value != 0 ) {
								$discount['value'] = $document->format_price( $discount_value * -1 );

								// recalculate percentage
								$percentage = $this->get_discount_percentage( $document->order, $discount_value );
								if( isset( $show_percentage ) && $percentage ) {
									$precision           = apply_filters( 'wpo_wcpdf_discount_percentage_precision', 0 );
									$percentage          = number_format( $percentage, $precision, wc_get_price_decimal_separator(), '' );
									$discount['label']   = "{$raw_label} ({$percentage}%)";
								}
							} else {
								break;
							}
						} elseif ( isset( $show_codes ) && ! empty( $used_coupons ) ) {
							$discount['label'] = "{$discount['label']} ({$used_coupons})";
						}

						$totals_table_data[$total_key] = (array) $total_setting + $discount;
					}
					break;
				case 'shipping':
					// $tax, $method, $hide_free
					$shipping_cost = $document->order->get_shipping_total();
					if ( !(round( $shipping_cost, 3 ) == 0 && isset($hide_free)) ) {
						$totals_table_data[$total_key] = (array) $total_setting + $document->get_order_shipping( $tax );
						if (!empty($method)) {
							$totals_table_data[$total_key]['value'] = $document->order->get_shipping_method();
						}
						if ( strpos($totals_table_data[$total_key]['label'], '{{method}}') !== false ) {
							$totals_table_data[$total_key]['label'] = str_replace('{{method}}', $document->order->get_shipping_method(), $totals_table_data[$total_key]['label']);
						}
					}
					break;
				case 'fees':
					// $tax
					if ( $fees = $document->get_order_fees( $tax ) ) {

						// WooCommerce Checkout Add-Ons compatibility
						if ( function_exists('wc_checkout_add_ons')) {
							$wc_checkout_add_ons = wc_checkout_add_ons();
							// we're adding a 'fee_' prefix because that's what woocommerce does in its
							// order total keys and wc_checkout_add_ons uses this to determine the total type (fee)
							$fees = $this->array_keys_prefix($fees, 'fee_', 'add');
							if (method_exists($wc_checkout_add_ons, 'get_frontend_instance')) {
								$wc_checkout_add_ons_frontend = $wc_checkout_add_ons->get_frontend_instance();
								$fees = $wc_checkout_add_ons_frontend->append_order_add_on_fee_meta( $fees, $document->order );
							} elseif ( is_object(wc_checkout_add_ons()->frontend) && method_exists(wc_checkout_add_ons()->frontend, 'append_order_add_on_fee_meta') ) {
								$fees = wc_checkout_add_ons()->frontend->append_order_add_on_fee_meta( $fees, $document->order );
							}
							$fees = $this->array_keys_prefix($fees, 'fee_', 'remove');
						}

						reset($fees);
						$first = key($fees);
						end($fees);
						$last = key($fees);

						foreach( $fees as $fee_key => $fee ) {
							$class = 'fee-line';
							if ($fee_key == $first) $class .= ' first';
							if ($fee_key == $last) $class .= ' last';

							$totals_table_data[$total_key.$fee_key] = (array) $total_setting + $fee;
							$totals_table_data[$total_key.$fee_key]['class'] = $class;
						}
					}
					break;
				case 'vat':
					// $percent, $base
					$total_tax = $document->order->get_total_tax();
					$shipping_tax = $document->order->get_shipping_tax();

					if ( isset ( $single_total ) ) {
						$tax = array();

						// override label if set
						// unset($total_setting['label']);
						$tax['label'] = !empty($label) ? $label : __( 'VAT', 'wpo_wcpdf_templates' );


						if ( isset($tax_type) && $tax_type == 'product' ) {
							$tax['value'] = $document->format_price( $total_tax - $shipping_tax );
						} elseif ( isset($tax_type) && $tax_type == 'shipping' ) {
							$tax['value'] = $document->format_price( $shipping_tax );
						} else {
							$tax['value'] = $document->format_price( $total_tax );
						}

						$totals_table_data[$total_key] = (array) $total_setting + (array) $tax;
						$totals_table_data[$total_key]['class'] = 'vat tax-line';
					} elseif ($taxes = $document->get_order_taxes()) {
						$taxes = $this->add_tax_base( $taxes, $document->order );

						reset($taxes);
						$first = key($taxes);
						end($taxes);
						$last = key($taxes);

						foreach( $taxes as $tax_key => $tax ) {
							$class = 'tax-line';
							if ($tax_key == $first) $class .= ' first';
							if ($tax_key == $last) $class .= ' last';

							// prepare label format based on settings
							$label_format = '{{label}}';
							if (isset($percent)) $label_format .= ' {{rate}}';

							// prevent errors if base not set
							if ( empty( $tax['base'] ) ) $tax['base'] = 0;

							// override label if set
							$tax_label = !empty($label) ? $label : $tax['label'];
							unset($total_setting['label']);

							if ( isset($tax_type) && $tax_type == 'product' ) {
								if ( apply_filters( 'woocommerce_order_hide_zero_taxes', true ) && $tax['tax_amount'] == 0 ) {
									continue;
								}
								$tax_amount = $tax['tax_amount'];
							} elseif ( isset($tax_type) && $tax_type == 'shipping' ) {
								if ( apply_filters( 'woocommerce_order_hide_zero_taxes', true ) && $tax['shipping_tax_amount'] == 0 ) {
									continue;
								}
								$tax_amount = $tax['shipping_tax_amount'];
							} else {
								$tax_amount = $tax['tax_amount'] + $tax['shipping_tax_amount'];
								if ( apply_filters( 'woocommerce_order_hide_zero_taxes', true ) && $tax_amount == 0 ) {
									continue;
								}
								if (isset($base) && !empty($tax['base'])) $label_format .= ' ({{base}})'; // add base to label
							}
							$tax['value'] = $document->format_price( $tax_amount );

							// fallback to tax calculation if we have no rate
							// if ( empty( $tax['rate'] ) && method_exists( $document, 'calculate_tax_rate' ) ) {
							// 	$tax['rate'] = $document->calculate_tax_rate( $tax['base'], $tax_amount );
							// }

							$label_format = apply_filters( 'wpo_wcpdf_templates_tax_total_label_format', $label_format );

							if ( isset( $tax['stored_rate'] ) ) {
								$tax_rate = $tax['stored_rate'];
							} else {
								$tax_rate = isset($tax['calculated_rate']) ? $tax['calculated_rate'] : null;
							}

							$tax['label'] = apply_filters( 'wpo_wcpdf_templates_tax_total_label', str_replace(
								array(
									'{{label}}',
									'{{rate}}',
									'{{base}}',
									'{{name}}',
								),
								array(
									$tax_label,
									$tax_rate,
									$document->format_price( $tax['base'] ),
									$tax['label'],
								),
								$label_format
							), $tax, $document );

							$totals_table_data[$total_key.$tax_key] = (array) $total_setting + $tax;
							$totals_table_data[$total_key.$tax_key]['class'] = $class;
						}
					}
					break;
				case 'vat_base':
					// $percent
					if ($taxes = $document->get_order_taxes()){
						$taxes = $this->add_tax_base( $taxes, $document->order );

						reset($taxes);
						$first = key($taxes);
						end($taxes);
						$last = key($taxes);

						if (empty($total_setting['label'])) {
							$total_setting['label'] = $label = __( 'Total ex. VAT', 'woocommerce-pdf-invoices-packing-slips' );
						}

						foreach( $taxes as $tax_key => $tax ) {
							// prevent errors if base not set
							if ( empty( $tax['base'] ) ) continue;

							$class = 'tax-base-line';
							if ($tax_key == $first) $class .= ' first';
							if ($tax_key == $last) $class .= ' last';

							// prepare label format based on settings
							$label_format = '{{label}}';
							if (isset($percent)) $label_format .= ' ({{rate}})';
							$label_format = apply_filters( 'wpo_wcpdf_templates_tax_base_total_label_format', $label_format, $tax );

							$tax['value'] = $document->format_price( $tax['base'] );

							$total_setting['label'] = str_replace( array( '{{label}}', '{{rate}}', '{{rate_label}}' ) , array( $label, $tax['rate'], $tax['label'] ), $label_format );

							$totals_table_data[$total_key.$tax_key] = (array) $total_setting + $tax;
							$totals_table_data[$total_key.$tax_key]['class'] = $class;
						}
					}
					break;
				case 'total':
					// $tax
					if ( $tax == 'excl' && apply_filters( 'wpo_wcpdf_add_up_grand_total_excl', false ) ) {
						// alternative calculation method that adds up product prices, fees & shipping
						// rather than subtracting tax from the grand total => WC3.0+ only!
						$grand_total_ex = 0;
						foreach ( $document->order->get_items() as $item_id => $item ) {
							$grand_total_ex += $item->get_total(); // total = after discount!
						}
						foreach ( $document->order->get_fees() as $item_id => $item ) {
							$grand_total_ex += $item->get_total(); // total = after discount!
						}
						$grand_total_ex += $document->order->get_shipping_total();
						$grand_total_row = array(
							'label' => __( 'Total ex. VAT', 'woocommerce-pdf-invoices-packing-slips' ),
							'value' => wc_price( $grand_total_ex, array( 'currency' => $document->order->get_currency() ) ),
						);
						$totals_table_data[$total_key] = (array) $total_setting + $grand_total_row;
					} else {
						$totals_table_data[$total_key] = (array) $total_setting + $document->get_order_grand_total( $tax );
					}
					if ( $tax == 'incl') {
						$totals_table_data[$total_key]['class'] = 'total grand-total';
					}
					break;
				case 'order_weight':
					// $show_unit
					$order_weight = array (
						'label'	=> __( 'Total weight', 'wpo_wcpdf_templates' ),
						'value'	=> $this->get_order_weight( $document->order, $document, isset( $show_unit) ),
					);

					$totals_table_data[$total_key] = (array) $total_setting + $order_weight;
					break;
				case 'total_qty':
					$total_qty_total = array (
						'label'	=> __( 'Total quantity', 'wpo_wcpdf_templates' ),
						'value'	=> $this->get_order_total_qty( $document->order, $document ),
					);

					$totals_table_data[$total_key] = (array) $total_setting + $total_qty_total;
					break;
				case 'text':
					$static_text = array (
						'label'	=> isset( $total_setting['label'] ) ? $this->make_replacements( $total_setting['label'], $document->order, $document ) : '',
						'value'	=> isset( $total_setting['text'] ) ? $this->make_replacements( $total_setting['text'], $document->order, $document ) : '',
					);

					$totals_table_data[$total_key] = (array) $total_setting + $static_text;
					break;
				case 'custom_function':
				default:
					// third party total blocks pass the function to the block parameters, not the settings
					if ( $type !== 'custom_function' && ! empty( $customizer_total_blocks[$type]['callback'] ) ) {
						$total_setting['function'] = $customizer_total_blocks[$type]['callback'];
					}
					if ( ! empty ( $total_setting['function'] ) ) {
						$callback = is_string( $total_setting['function'] ) ? trim( $total_setting['function'] ) : $total_setting['function'];
						if ( is_callable( $callback ) ) {
							$class = is_string( $callback ) ? $callback : $type;
							$total_setting['class'] = sprintf( 'custom %s', sanitize_html_class( $class ) );
							$custom_total = call_user_func( $callback, $document, $total_setting );
							if ( is_array( $custom_total ) ) {
								// we only use the label from the function
								unset( $total_setting['label'] );
								if ( isset( $custom_total['label'] ) && isset( $custom_total['value'] ) ) {
									// single row
									$totals_table_data[$total_key] = (array) $total_setting + $custom_total;
								} else {
									// assume multiple rows
									foreach ( $custom_total as $custom_total_key => $custom_total_row ) {
										if ( isset( $custom_total_row['label'] ) && isset( $custom_total_row['value'] ) ) {
											$totals_table_data["{$total_key}_{$custom_total_key}"] = (array) $total_setting + $custom_total_row;
										}
									}
								}
							}
						}
					}
					break;
			}

		}

		$totals_table_data = $this->exclude_hidden_products( $totals_table_data, $document );

		// apply filters and defaults
		foreach ( $totals_table_data as $total_key => $total_setting ) {
			// set class if not set. note that fees and taxes have modified keys!
			if ( ! isset( $total_setting['class'] ) ) {
				$totals_table_data[ $total_key ]['class'] = $total_setting['type'];
			}
			$totals_table_data[ $total_key ] = apply_filters( 'wpo_wcpdf_templates_total_row_data', $totals_table_data[ $total_key ], $total_setting, $document );
		}

		return $totals_table_data;
	}

	/**
	 * Excludes virtual and downloadable products from the provided fields for a specific document.
	 *
	 * @param array $fields
	 * @param \WPO\WC\PDF_Invoices\Documents\Order_Document_Methods $document
	 *
	 * @return array
	 */
	private function exclude_hidden_products( $fields, $document ) {
		if ( empty( $document ) || 'packing-slip' !== $document->get_type() ) {
			return $fields;
		}

		$packing_slip_settings = get_option( 'wpo_wcpdf_documents_settings_packing-slip' );
		if ( ! isset( $packing_slip_settings['hide_virtual_products'] ) ) {
			return $fields;
		}

		$hidden_items_total = 0;
		foreach ( $fields as $field_key => $field_data ) {
			foreach ( $document->order->get_items() as $item ) {
				$product = $item->get_product();
				if ( $product && ( $product->is_virtual() || $product->is_downloadable() ) ) {
					$hidden_items_total += $item->get_total() + $item->get_total_tax();
					switch ( $field_data['type'] ) {
						case 'formatted_order_total_ex':
						case 'formatted_subtotal_ex':
						case 'vat_base':
							$fields[ $field_key ]['value'] = $this->decrement_total_value(
								$fields[ $field_key ]['value'],
								$item->get_total(),
								true
							);
							break;
						case 'total':
						case 'subtotal':
						case 'formatted_order_total':
						case 'formatted_subtotal':
							$fields[ $field_key ]['value'] = $this->decrement_total_value(
								$fields[ $field_key ]['value'],
								$item->get_total() + $item->get_total_tax(),
								true
							);
							break;
						case 'vat':
							$fields[ $field_key ]['value'] = $this->decrement_total_value(
								$fields[ $field_key ]['value'],
								$item->get_total_tax(),
								true
							);
							break;
						case 'total_qty':
						case 'order_qty':
							$fields[ $field_key ]['value'] = $this->decrement_total_value(
								$fields[ $field_key ]['value'],
								$item->get_quantity()
							);
							break;
						case 'order_weight':
							$weight                        = (float) wc_format_decimal( $product->get_weight() );
							$fields[ $field_key ]['value'] = $this->decrement_total_value(
								$fields[ $field_key ]['value'],
								$weight * $item->get_quantity()
							);
							break;
					}
				}
			}

			if ( 'spelled_out_total' === $field_data['type'] && extension_loaded( 'intl' ) && class_exists( 'NumberFormatter' ) ) {
				$number_formatter              = new \NumberFormatter( determine_locale(), \NumberFormatter::SPELLOUT );
				$fields[ $field_key ]['value'] = $number_formatter->format(
					number_format(
						$document->order->get_total() - $hidden_items_total,
						wc_get_price_decimals()
					)
				);
			}
		}

		return $fields;
	}

	/**
	 * Decrements a given value and returns the result.
	 * If the value is formatted, it parses the HTML content and updates the price accordingly.
	 *
	 * @param string $field_value
	 * @param string $decrement_value
	 * @param bool $is_formatted
	 *
	 * @return string
	 */
	private function decrement_total_value( $field_value, $decrement_value, $is_formatted = false ) {
		if ( ! $is_formatted ) {
			return strval( floatval( $field_value ) - floatval( $decrement_value ) );
		}

		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );

		if ( ! $dom->loadHTML( $field_value, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
			return $field_value;
		}

		libxml_clear_errors();
		$xpath     = new \DOMXPath( $dom );
		$node_list = $xpath->query( '//span[@class="woocommerce-Price-amount amount"]' );

		if ( $node_list->length <= 0 ) {
			return trim( $dom->saveHTML() );
		}

		$bdi_nodes = $xpath->query( './bdi', $node_list->item( 0 ) );

		if ( $bdi_nodes->length <= 0 ) {
			return trim( $dom->saveHTML() );
		}

		foreach ( $bdi_nodes->item( 0 )->childNodes as $child_node ) {
			if ( $child_node->nodeType !== XML_TEXT_NODE ) {
				continue;
			}

			$node_value = $child_node->wholeText;

			if ( ! preg_match( '/(\d+[^0-9]*\d*)/', $node_value, $match ) && ! empty( $match ) ) {
				continue;
			}

			$current_total = floatval( str_replace( wc_get_price_decimal_separator(), '.', $match[0] ) );
			$new_value     = $current_total - floatval( $decrement_value );

			$formatted_price_dom = new \DOMDocument();
			if ( function_exists( 'wcpdf_convert_encoding' ) ) {
				$formatted_price_dom->loadHTML( wcpdf_convert_encoding( wc_price( $new_value ) ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
			} else {
				$formatted_price_dom->loadHTML( mb_convert_encoding( wc_price( $new_value ), 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
			}
			$formatted_price_node = $dom->importNode( $formatted_price_dom->getElementsByTagName( 'bdi' )->item( 0 ), true );

			$node_list->item( 0 )->replaceChild( $formatted_price_node, $node_list->item( 0 )->firstChild );

			break;
		}

		return trim( $dom->saveHTML() );
	}

	public function get_order_details_header ( $column_setting, $document ) {
		extract( $column_setting );

		if ( ! empty( $label ) ) {
			$header['title'] = __( $label, 'woocommerce-pdf-invoices-packing-slips' ); // not proper gettext, but it makes it possible to reuse po translations!
		} else {
			switch ( $type ) {
				case 'position':
					$header['title'] = '';
					break;
				case 'sku':
					$header['title'] = __( 'SKU', 'woocommerce-pdf-invoices-packing-slips' );
					break;
				case 'thumbnail':
					$header['title'] = '';
					break;
				case 'description':
					$header['title'] = __( 'Product', 'woocommerce-pdf-invoices-packing-slips' );
					break;
				case 'quantity':
					$header['title'] = __( 'Quantity', 'woocommerce-pdf-invoices-packing-slips' );
					break;
				case 'price':
					switch ($price_type) {
						case 'single':
							$header['title'] = __( 'Price', 'woocommerce-pdf-invoices-packing-slips' );
							$header['class'] = 'price';
							break;
						case 'total':
							$header['title'] = __( 'Total', 'woocommerce-pdf-invoices-packing-slips' );
							$header['class'] = 'total';
							break;
					}
					break;
				case 'regular_price':
					$header['title'] = __( 'Regular price', 'wpo_wcpdf_templates' );
					break;
				case 'discount':
					$header['title'] = __( 'Discount', 'woocommerce-pdf-invoices-packing-slips' );
					break;
				case 'vat':
					$header['title'] = ( isset( $split ) && isset( $title ) ) ? __( $title, 'wpo_wcpdf_templates' ) : __( 'VAT', 'wpo_wcpdf_templates' );
					break;
				case 'tax_rate':
					$header['title'] = __( 'Tax rate', 'wpo_wcpdf_templates' );
					break;
				case 'weight':
					$header['title'] = __( 'Weight', 'woocommerce-pdf-invoices-packing-slips' );
					break;
				case 'dimensions':
					$header['title'] = __( 'Dimensions', 'wpo_wcpdf_templates' );
					break;
				case 'product_attribute':
					$header['title'] = '';
					break;
				case 'product_custom':
					$header['title'] = '';
					break;
				case 'product_description':
					$header['title'] = __( 'Product description', 'wpo_wcpdf_templates' );
					break;
				case 'product_categories':
					$header['title'] = __( 'Categories', 'wpo_wcpdf_templates' );
					break;
				case 'all_meta':
					$header['title'] = __( 'Variation', 'wpo_wcpdf_templates' );
					break;
				case 'item_meta':
					$header['title'] = isset( $meta_key ) ? $meta_key : '';
					break;
				case 'cb':
					$header['title'] = '';
					break;
				case 'static_text':
					$header['title'] = '';
					break;
				case 'custom_function':
					$header['title'] = '';
					break;
				default:
					$header['title'] = $type;
					break;
			}
		}

		// set class if not set;
		if ( ! isset( $header['class'] ) ) {
			$header['class'] = $type;
		}

		// column specific classes
		switch ( $type ) {
			case 'product_attribute':
				if ( ! empty( $attribute_name ) ) {
					$attribute_name_class = sanitize_html_class( $attribute_name );
					$header['class']      = "{$type} {$attribute_name_class}";
				}
				break;
			case 'product_custom':
				if ( ! empty( $field_name ) ) {
					$field_name_class = sanitize_html_class( $field_name );
					$header['class'] .= " {$field_name_class}";
				}
				break;
			case 'custom_function':
				if ( ! empty( $function ) ) {
					$function_class   = sanitize_html_class( $function );
					$header['class'] .= " {$function_class}";
				}
				break;
			default:
				break;
		}

		// mark first and last column
		if ( isset( $position ) ) {
			$header['class'] .= " {$position}-column";
		}

		return $header;
	}

	public function get_order_details_data ( $column_setting, $item, $document ) {
		extract( $column_setting );

		$item_dependent_columns = array(
			'regular_price',
			'discount',
			'dimensions',
			'product_attribute',
			'product_custom',
			'product_description',
			'product_categories',
			'all_meta',
			'item_meta',
			'static_text',
			'custom_function',
		);
		
		if ( in_array( $type, $item_dependent_columns ) && empty( $item['item'] ) ) {
			$column[$type]['data']  = null;
		} else {
			switch ($type) {
				case 'position':
					$column['data'] = $line_number;
					break;
				case 'sku':
					$column['data'] = isset($item['sku']) ? $item['sku'] : '';
					break;
				case 'thumbnail':
					$column['data'] = isset($item['thumbnail']) ? $item['thumbnail'] : '';
					break;
				case 'description':
					// $show_sku, $show_weight, $show_meta, $show_external_plugin_meta, $custom_text
					ob_start();
					?>
					<span class="item-name"><?php echo $item['name']; ?></span>
					<?php if ( isset($show_external_plugin_meta) ) : ?>
					<div class="external-meta-start">
					<?php do_action( 'woocommerce_order_item_meta_start', $item['item_id'], $item['item'], $document->order, false ); ?>
					</div>
					<?php endif; ?>
					<?php do_action( 'wpo_wcpdf_before_item_meta', $document->get_type(), $item, $document->order ); ?>
					<?php if ( isset($show_meta) ) : ?>
					<span class="item-meta"><?php echo $item['meta']; ?></span>
					<?php endif; ?>
					<?php if ( isset($show_sku) || isset($show_weight) ) : ?>
					<dl class="meta">
						<?php $description_label = __( 'SKU', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
						<?php if( !empty( $item['sku'] ) && isset($show_sku) ) : ?><dt class="sku"><?php _e( 'SKU:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt><dd class="sku"><?php echo $item['sku']; ?></dd><?php endif; ?>
						<?php if( !empty( $item['weight'] ) && isset($show_weight) ) : ?><dt class="weight"><?php _e( 'Weight:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt><dd class="weight"><?php echo $this->format_weight ( $item['weight'] );  ?></dd><?php endif; ?>
					</dl>
					<?php endif; ?>
					<?php do_action( 'wpo_wcpdf_after_item_meta', $document->get_type(), $item, $document->order ); ?>
					<?php if ( isset($show_external_plugin_meta) ) : ?>
					<div class="external-meta-end">
					<?php do_action( 'woocommerce_order_item_meta_end', $item['item_id'], $item['item'], $document->order, false ); ?>
					</div>
					<?php endif; ?>
					<?php if ( isset($custom_text) ) : ?>
					<div class="custom-text">
					<?php echo nl2br( wptexturize( $this->make_item_replacements( $custom_text, $item, $document ) ) ); ?>
					</div>
					<?php endif; ?>
					<?php
					$column['data'] = ob_get_clean();
					break;
				case 'quantity':
					$column['data'] = $item['quantity'];
					if ( absint( $item['quantity'] ) > 1 ) {
						$column['class'] = "{$type} multiple";
					}
					break;
				case 'price':
					// $price_type, $tax, $discount
					// using a combined value to make this more readable...
					$price_type_full = "{$price_type}_{$tax}_{$discount}";
					switch ($price_type_full) {
						// before discount
						case 'single_incl_before':
							$column['data'] = $item['single_price'];
							break;
						case 'single_excl_before':
							$column['data'] = $item['ex_single_price'];
							break;
						case 'total_incl_before':
							$column['data'] = $item['price'];
							break;
						case 'total_excl_before':
							$column['data'] = $item['ex_price'];
							break;

						// after discount
						case 'single_incl_after':
							if ( ! empty( $item['item'] ) ) {
								$price = ( $item['item']['line_total'] + $item['item']['line_tax'] ) / max( 1, abs( $item['quantity'] ) );
								$column['data'] = $document->format_price( $price );
							} else {
								$column['data'] = '';
							}
							break;
						case 'single_excl_after':
							$column['data'] = $item['single_line_total'];
							break;
						case 'total_incl_after':
							if ( ! empty( $item['item'] ) ) {
								$price = $item['item']['line_total'] + $item['item']['line_tax'];
								$column['data'] = $document->format_price( $price );
							} else {
								$column['data'] = '';
							}
							break;
						case 'total_excl_after':
							$column['data'] = $item['line_total'];
							break;
					}

					if ($price_type == 'total') {
						$column['class'] = 'total';
					}
					break;
				case 'regular_price':
					// $price_type, $tax, $only_sale
					$regular_prices = $this->get_regular_item_price( $item['item'], $item['item_id'], $document->order );

					// check if item price is different from sale price
					$single_item_price = ( $item['item']['line_subtotal'] + $item['item']['line_subtotal_tax'] ) / max( 1, $item['quantity'] );
					if ( isset($only_sale) && round( $single_item_price, 2 ) == round( $regular_prices['incl'], 2 ) ) {
						$column['data'] = '';
					} else {
						// get including or excluding tax
						$regular_price = $regular_prices[$tax];
						// single or total
						if ($price_type == 'total') {
							$regular_price = (float) $regular_price * $item['quantity'];
						}
						$column['data'] = $document->format_price( $regular_price );
					}
					break;
				case 'discount':
					// $price_type, $tax
					if ($price_type == 'percent') {
						$subtotal = $item['item']['line_subtotal'] + $item['item']['line_subtotal_tax'];
						if( $subtotal != 0 ) {
							$discount = $subtotal - ( $item['item']['line_total'] + $item['item']['line_tax'] );
							if ($discount > 0) {
								$percent = ( $discount / $subtotal ) * 100;
								$precision = apply_filters( 'wpo_wcpdf_discount_percentage_precision', 0 );
								$percent = number_format( $percent, $precision, wc_get_price_decimal_separator(), '' );
								$column['data'] = "{$percent}%";
							} else {
								$column['data'] = "";
							}
						} else {
							$column['data'] = "";
						}
						break;
					}

					$price_type = "{$price_type}_{$tax}";
					switch ($price_type) {
						case 'single_incl':
							$price = ( ($item['item']['line_subtotal'] + $item['item']['line_subtotal_tax']) - ( $item['item']['line_total'] + $item['item']['line_tax'] ) ) / max( 1, abs( $item['quantity'] ) );
							$column['data'] = $document->format_price( (float) $price * -1 );
							break;
						case 'single_excl':
							$price = ( $item['item']['line_subtotal'] - $item['item']['line_total'] ) / max( 1, abs( $item['quantity'] ) );
							$column['data'] = $document->format_price( (float) $price * -1  );
							break;
						case 'total_incl':
							$price = ($item['item']['line_subtotal'] + $item['item']['line_subtotal_tax']) - ( $item['item']['line_total'] + $item['item']['line_tax'] );
							$column['data'] = $document->format_price( (float) $price * -1  );
							break;
						case 'total_excl':
							$price = $item['item']['line_subtotal'] - $item['item']['line_total'];
							$column['data'] = $document->format_price( (float) $price * -1  );
							break;
					}
					break;
				case 'vat':
					$column['data'] = '';
					// $price_type, $discount
					if ( isset( $price_type ) && isset( $discount ) ) {
						$price_type     = "{$price_type}_{$discount}";
						$split          = isset( $split ) && is_array( $split ) ? $split : array();
						$dash_for_zero  = isset( $dash_for_zero );
						$column['data'] = $this->get_item_vat_column_data( $price_type, $item, $document, $split, $dash_for_zero );
					}
					break;
				case 'tax_rate':
					$show_name = isset( $show_tax_name ) ? true : false;
					if ( version_compare( WOOCOMMERCE_VERSION, '3.7', '<' ) || ! $show_name ) {
						$column['data'] = $item['tax_rates'];
					} else {
						$column['data'] = $this->get_item_tax_rate_name( $item['item'], $document->order );
					}
					break;
				case 'weight':
					if ( !isset($qty) ) {
						$qty = 'single';
					}

					switch ($qty) {
						case 'single':
							$column['data'] = !empty($item['weight']) ? $item['weight'] : '';
							break;
						case 'total':
							$column['data'] = !empty($item['weight']) ? $item['weight'] * $item['quantity'] : '';
							break;
					}
					if (isset($show_unit) && !empty($item['weight'])) {
						$column['data'] = $this->format_weight ( $column['data'] );
					}
					break;
				case 'dimensions':
					$column['data'] = $this->get_product_dimensions( $item['product'] );
					break;
				case 'product_attribute':
					if (isset($item['product'])) {
						$attribute_name_class = sanitize_title( $attribute_name );
						$column['class'] = "{$type} {$attribute_name_class}";
						$column['data'] = nl2br( wptexturize ( $document->get_product_attribute( $attribute_name, $item['product'] ) ));
					} else {
						$column['data'] = '';
					}
					break;
				case 'product_custom':
					// setup
					$meta_key_class = sanitize_title( $field_name );
					$column['class'] = "{$type} {$meta_key_class}";
					$column['data'] = nl2br( wptexturize ( $this->get_product_custom_field( $item['product'], $field_name ) ));
					break;
				case 'product_description':
					$column['data'] = nl2br( wptexturize( $this->get_product_description( $item['product'], $description_type, isset( $use_variation_description ) ? 1 : null ) ) );
					break;
				case 'product_categories':
					$column['data'] = $this->get_product_categories( $item['product'] );
					break;
				case 'all_meta':
					// $product_fallback
					// For an order added through the admin we can display
					// the formatted variation data (if fallback enabled)
					if ( isset( $product_fallback ) && empty( $item['meta'] ) && isset( $item['product'] ) && $item['product']->get_type() == 'variation' && function_exists( 'wc_get_formatted_variation' ) ) {
						$item['meta'] = wc_get_formatted_variation( $item['product'], true );
					}
					$column['data'] = '<span class="item-meta">'.$item['meta'].'</span>';
					break;
				case 'item_meta':
					// $field_name
					if ( !empty($field_name) ) {
						$column['data'] = nl2br( wptexturize (  $this->get_order_item_meta( $item, $field_name ) ));
					} else {
						$column['data'] = '';
					}
					break;
				case 'cb':
					$column['data'] = '<span class="checkbox"></span>';
					break;
				case 'static_text':
					// $text
					$column['data'] = !empty( $text ) ? nl2br( wptexturize( $this->make_item_replacements( $text, $item, $document ) ) ) : '';
					break;
				case 'custom_function':
					$function_name = trim( $function );
					if ( function_exists( $function_name ) ) {
						$column['data'] = nl2br( wptexturize ( call_user_func( $function_name, $item, $document ) ) );
					} else {
						$column['data'] = '';
					}
					break;
				default:
					$column['data'] = '';
					break;
			}
		}

		// set class if not set;
		if (!isset($column['class'])) {
			$column['class'] = $type;
		}

		// mark first and last column
		if (isset($position)) {
			$column['class'] .= " {$position}-column";
		}

		return apply_filters( 'wpo_wcpdf_templates_item_column_data', $column, $column_setting, $item, $document );
	}

	/**
	 * Output custom blocks (if set for template)
	 */
	public function custom_blocks_data( $template_type, $order = null ) {
		$custom_blocks = WPO_WCPDF_Templates()->settings->get_settings( $template_type, 'custom', null );

		if ( ! empty($custom_blocks) ) {
			foreach ($custom_blocks as $key => $custom_block) {
				// echo "<pre>";var_dump($custom_block);echo "</pre>";die();
				if ( current_filter() != $custom_block['position']) {
					continue;
				}

				// only process blocks with input
				if ( ( $custom_block['type'] == 'custom_field' || $custom_block['type'] == 'user_meta' ) && empty( $custom_block['meta_key'] ) ) {
					continue;
				} elseif ( $custom_block['type'] == 'text' && empty( $custom_block['text'] ) ) {
					continue;
				}

				switch ($custom_block['type']) {
					case 'custom_field':
						if ( empty( $order ) ) {
							continue 2;
						}
						if ( $this->check_custom_block_condition( $custom_block, $order ) == false ) {
							continue 2;
						}

						$class = $custom_block['meta_key'];

						// support for array data
						$array_key_position = strpos( $custom_block['meta_key'], '[' );
						if ( $array_key_position !== false ) {
							$array_key  = trim( substr( $custom_block['meta_key'], $array_key_position), "[]'");
							$meta_key   = strtok( $custom_block['meta_key'], '[' );
							$array_data = $order->get_meta( $meta_key );
							// parent order fallback
							if ( empty( $array_data ) && 'shop_order_refund' == $order->get_type() && is_callable( array( $order, 'get_parent_id' ) ) ) {
								$parent_order = wc_get_order( $order->get_parent_id() );
								$array_data   = $parent_order->get_meta( $meta_key );
							}
							if ( is_array( $array_data ) && ! empty( $array_data[$array_key] ) ) {
								$data = $array_data[$array_key];
								break;
							}
						}
						
						$data = $order->get_meta( $custom_block['meta_key'] );
						// parent order fallback
						if ( empty( $data ) && 'shop_order_refund' == $order->get_type() && is_callable( array( $order, 'get_parent_id' ) ) ) {
							$parent_order = wc_get_order( $order->get_parent_id() );
							$data         = $parent_order->get_meta( $custom_block['meta_key'] );
						}

						// format date fields with WC format automatically
						$data = $this->maybe_format_date_field( $data, $custom_block['meta_key'] );

						// format array data
						if ( is_array( $data ) ) {
							$data_strings = array();
							foreach ($data as $key => $value) {
								if ( !is_array($value) && !is_object($value) ) {
									$data_strings[] = "$key: $value";
								}
							}
							$data = implode(', ', $data_strings);
						}

						// WC3.0+ fallback to properties
						$property = str_replace('-', '_', sanitize_title( ltrim( $custom_block['meta_key'], '_' ) ) );
						if ( empty( $data ) && is_callable( array( $order, "get_{$property}" ) ) ) {
							$data = $order->{"get_{$property}"}( 'view' );
						}

						break;
					case 'user_meta':
						if ( empty( $order ) ) {
							continue 2;
						}
						if ( $this->check_custom_block_condition( $custom_block, $order ) == false ) {
							continue 2;
						}
						if ( 'shop_order_refund' == $order->get_type() && is_callable( array( $order, 'get_parent_id' ) ) ) {
							$parent_order = wc_get_order( $order->get_parent_id() );
							$user_id      = $parent_order->get_user_id();
						} else {
							$user_id = $order->get_user_id();
						}
						if ( ! empty( $user_id ) ) {
							$meta_key = $custom_block['meta_key'];
							$user_properties = array(
								'user_login',
								'user_nicename',
								'user_email',
								'user_url',
								'user_registered',
								'user_status',
								'display_name',
							);

							// check properties first
							if ( in_array( $meta_key, $user_properties ) && $user = get_user_by( 'id', $user_id ) ) {
								$data = $user->{"$meta_key"};
							} else {
								$data = get_user_meta( $user_id, $meta_key, true );
							}
						} else {
							$data = '';
						}
						$class = $custom_block['meta_key'];
						break;
					case 'text':
						if ( !empty( $order ) && $this->check_custom_block_condition( $custom_block, $order ) == false ) {
							continue 2;
						}
						if ( !empty( $order ) ) {
							$document = wcpdf_get_document( $template_type, $order );
							$formatted_text = $this->make_replacements( $custom_block['text'], $order, $document );
						} else {
							$formatted_text = $custom_block['text'];
						}
						if ( empty( $custom_block['html_mode'] ) ) {
							$data = nl2br( wptexturize( $formatted_text ) );
						} else {
							$data = $formatted_text;
						}
						$class = 'custom-block-text';
						break;
				}

				// Hide if empty option
				if ( !empty($custom_block['hide_if_empty']) ) {
					if ( $custom_block['type'] == 'text' && empty( strip_tags( $data ) ) ) {
						continue;
					} elseif ( $custom_block['type'] != 'text' && empty( $data ) ){
						continue;
					}
				}

				// output table rows if in order data table
				if ( in_array( current_filter(), array( 'wpo_wcpdf_before_order_data', 'wpo_wcpdf_after_order_data') ) ) {
					printf('<tr class="%s"><th>%s</th><td>%s</td></tr>', $class, $custom_block['label'], $data );
				} else {
					if (!empty($custom_block['label'])) {
						printf('<h3 class="%s-label">%s</h3>', $class, $custom_block['label'] );
					}
					// only apply div wrapper if not already in div
					if ( stripos($data, '<div') !== false || !empty($custom_block['html_mode']) ) {
						echo $data;
					} else {
						printf('<div class="%s">%s</div>', $class, $data );
					}
				}
			};
		}
	}

	public function check_custom_block_condition( $custom_block, $order ) {
		// we're always checking against the parent order data for refunds
		if ( $order->get_type() == 'shop_order_refund' ) {
			$order = wc_get_order( $order->get_parent_id() );
		}

		// var_dump( !empty($custom_block['order_statuses']) && is_array($custom_block['order_statuses']) );die();
		// Order status
		if ( !empty($custom_block['order_statuses']) && is_array($custom_block['order_statuses']) && is_callable(array($order,'get_status')) ) {
			// Standardise status names (make sure wc-prefix is used)
			$order_status = 'wc-' === substr( $order->get_status(), 0, 3 ) ? $order->get_status() : 'wc-' . $order->get_status();
			if ( !in_array($order_status, $custom_block['order_statuses']) ) {
				return false;
			}
		}

		// Payment Method
		if ( !empty($custom_block['payment_methods']) && is_array($custom_block['payment_methods']) && is_callable(array($order,'get_payment_method')) ) {
			if ( !in_array($order->get_payment_method(), $custom_block['payment_methods']) ) {
				return false;
			}
		}

		// Billing Country
		if ( !empty($custom_block['billing_country']) && is_array($custom_block['billing_country']) && is_callable(array($order,'get_billing_country')) ) {
			if ( !in_array($order->get_billing_country(), $custom_block['billing_country']) ) {
				return false;
			}
		}

		// Shipping Country
		if ( !empty($custom_block['shipping_country']) && is_array($custom_block['shipping_country']) && is_callable(array($order,'get_shipping_country')) ) {
			if ( !in_array($order->get_shipping_country(), $custom_block['shipping_country']) ) {
				return false;
			}
		}

		// VAT reverse charge
		if ( !empty($custom_block['vat_reverse_charge']) ) {
			$is_eu_vat = in_array( $order->get_billing_country(), WC()->countries->get_european_union_countries( 'eu_vat' ) );
			if ( $is_eu_vat && $order->get_total() > 0 && $order->get_total_tax() == 0 ) {
				// Try fetching VAT Number from meta
				$vat_meta_keys = array (
					'_vat_number',            // WooCommerce EU VAT Number
					'_billing_vat_number',    // WooCommerce EU VAT Number 2.3.21+
					'VAT Number',             // WooCommerce EU VAT Compliance
					'_eu_vat_evidence',       // Aelia EU VAT Assistant
					'_billing_eu_vat_number', // EU VAT Number for WooCommerce (WP Whale/former Algoritmika)
					'yweu_billing_vat',       // YITH WooCommerce EU VAT
					'billing_vat',            // German Market
					'_billing_vat_id',        // Germanized Pro
					'_shipping_vat_id',       // Germanized Pro (alternative)
					'_billing_dic',           // EU/UK VAT Manager for WooCommerce
				);

				foreach ($vat_meta_keys as $meta_key) {
					if ( $vat_number = $order->get_meta( $meta_key ) ) {
						// Aelia EU VAT Assistant stores the number in a multidimensional array
						if ($meta_key == '_eu_vat_evidence' && is_array($vat_number)) {
							$vat_number = !empty($vat_number['exemption']['vat_number']) ? $vat_number['exemption']['vat_number'] : '';
						}
						break;
					}
				}

			}
			// if we got here and we don't have a VAT number,
			// this is NOT a 0 tax order from the EU either
			if ( ! apply_filters( 'wpo_wcpdf_vat_reverse_charge_order', ! empty( $vat_number ), $order ) ) {
				return false;
			}
		}

		// 's all good man
		return apply_filters( 'wpo_wcpdf_custom_block_condition', true, $custom_block, $order );
	}

	public function settings_fields_replacements( $text, $document ) {
		// make replacements if placeholders present
		if ( strpos( $text, '{{' ) !== false ) {
			$text = $this->make_replacements( $text, $document->order, $document );
		}

		return $text;
	}

	public function make_replacements ( $text, $order, $document = null ) {
		// load parent order for refunds
		if ( 'shop_order_refund' == $order->get_type() && is_callable( array( $order, 'get_parent_id' ) ) ) {
			$parent_order = wc_get_order( $order->get_parent_id() );
		}

		$text = ! is_null( $text ) ? $text : '';
		
		// make an index of placeholders used in the text
		preg_match_all('/\{\{.*?\}\}/', $text, $placeholders_used);
		$placeholders_used = array_shift($placeholders_used); // we only need the first match set

		// load countries & states
		$countries = new \WC_Countries;

		// loop through placeholders and make replacements
		foreach ($placeholders_used as $placeholder) {
			$placeholder_clean = trim($placeholder,"{{}}");
			$ignore = array( '{{PAGE_NUM}}', '{{PAGE_COUNT}}' );
			if (in_array($placeholder, $ignore)) {
				continue;
			}

			// first try to read data from order, fallback to parent order (for refunds)
			$data_sources = array( 'order', 'parent_order' );
			foreach ($data_sources as $data_source) {
				if (empty($$data_source)) {
					continue;
				}

				// custom/third party filters
				if ( strpos($placeholder_clean, '|') !== false ) {
					$filter = "wpo_wcpdf_templates_replace_".sanitize_title( substr( $placeholder_clean, 0, strpos($placeholder_clean, '|') ) );
				} else {
					$filter = "wpo_wcpdf_templates_replace_".sanitize_title( $placeholder_clean );
				}
				if ( has_filter( $filter ) ) {
					$custom_filtered = ''; // we always want to replace these tags, regardless of errors/output
					ob_start(); // in case a plugin outputs data instead of returning it
					try {
						$custom_filtered = apply_filters( $filter, $custom_filtered, $$data_source, $placeholder_clean );
					} catch (\Throwable $e) { // For PHP 7
						if (function_exists('wcpdf_log_error')) {
							wcpdf_log_error( $e->getMessage(), 'critical', $e );
						}
					} catch (\Exception $e) { // For PHP 5
						if (function_exists('wcpdf_log_error')) {
							wcpdf_log_error( $e->getMessage(), 'critical', $e );
						}
					}
					ob_get_clean();
					$text = str_replace($placeholder, $custom_filtered, $text);
					continue 2;
				}

				// special treatment for country & state
				$country_placeholders = array( 'shipping_country', 'billing_country' );
				$state_placeholders   = array( 'shipping_state', 'billing_state' );
				foreach ( array_merge( $country_placeholders, $state_placeholders ) as $country_state_placeholder ) {
					if ( strpos( $placeholder_clean, $country_state_placeholder ) !== false ) {
						// check if formatting is needed
						if ( strpos( $placeholder_clean, '_code' ) !== false ) {
							// no country or state formatting
							$placeholder_clean = str_replace( '_code', '', $placeholder_clean );
							$format = false;
						} else {
							$format = true;
						}

						$country_or_state = call_user_func( array( $$data_source, "get_{$placeholder_clean}" ) );

						if ( $format === true ) {
							// format country or state
							if ( in_array( $placeholder_clean, $country_placeholders ) ) {
								$country_or_state = ( $country_or_state && isset( $countries->countries[ $country_or_state ] ) ) ? $countries->countries[ $country_or_state ] : $country_or_state;
							} elseif ( in_array( $placeholder_clean, $state_placeholders ) ) {
								// get country for address
								$callback         = 'get_'.str_replace( 'state', 'country', $placeholder_clean );
								$country          = call_user_func( array( $$data_source, $callback ) );
								$country_or_state = ( $country && $country_or_state && isset( $countries->states[ $country ][ $country_or_state ] ) ) ? $countries->states[ $country ][ $country_or_state ] : $country_or_state;
							}
						}

						if ( ! empty( $country_or_state ) ) {
							$text = str_replace( $placeholder, $country_or_state, $text );
							continue 3;
						}
					}
				}

				// date offset placeholders
				if ( strpos( $placeholder_clean, '|+' ) !== false ) {
					$calculated_date  = '';
					$placeholder_args = explode( '|+', $placeholder_clean );
					if ( ! empty( $placeholder_args[1] ) ) {
						$date_name   = $placeholder_args[0];
						$date_offset = $placeholder_args[1];
						switch ( $date_name ) {
							case 'order_date':
								$order_date       = $$data_source->get_date_created();
								$date_format      = function_exists( 'wcpdf_date_format' ) ? wcpdf_date_format( $document, 'order_date_created' ) : wc_date_format();
								$calculated_date  = date_i18n( $date_format, strtotime( $order_date->date_i18n('Y-m-d H:i:s') . " + {$date_offset}") );
								break;
							case 'invoice_date':
								$invoice_date_set = $$data_source->get_meta( '_wcpdf_invoice_date' );
								// prevent creating invoice date when not already set
								if ( ! empty( $invoice_date_set ) && ! empty( $document ) ) {
									$invoice_date    = $document->get_date( 'invoice' );
									$date_format     = function_exists( 'wcpdf_date_format' ) ? wcpdf_date_format( $document, 'invoice_date' ) : wc_date_format();
									$calculated_date = date_i18n( $date_format, strtotime( $invoice_date->date_i18n('Y-m-d H:i:s') . " + {$date_offset}" ) );
								}
								break;
							case 'due_date':
								if ( ! empty( $document ) && 'placeholder' === $document->get_setting( 'due_date' ) && is_callable( array( $document, 'get_due_date' ) ) ) {
									// Setting the number of days to 1, so that the "get_due_date" method doesn't return 0. Then, subtract 1 day.
									add_filter( 'wpo_wcpdf_due_date_days', function ( $due_date ) {
										return 1;
									} );

									$due_date_timestamp = $document->get_due_date();
									$due_date           = ( new \WC_DateTime() )->setTimestamp( $due_date_timestamp );
									$date_format        = function_exists( 'wcpdf_date_format' ) ? wcpdf_date_format( $document, 'due_date' ) : wc_date_format();
									$calculated_date    = date_i18n( $date_format, strtotime( $due_date->date_i18n( 'Y-m-d H:i:s' ) . " + {$date_offset} - 1 day" ) );
								}
								break;
						}
					}
					if ( ! empty( $calculated_date ) ) {
						$text = str_replace( $placeholder, $calculated_date, $text );
						continue 2;
					}
				}

				// Custom placeholders
				$custom = '';
				switch ( $placeholder_clean ) {
					case 'invoice_number':
						if ( ! empty( $document ) ) {
							$custom = $document->get_number( 'invoice', $$data_source, 'view', true );
						}
						break;
					case 'invoice_date':
						$invoice_date = $$data_source->get_meta( '_wcpdf_invoice_date' );
						// prevent creating invoice date when not already set
						if ( ! empty( $invoice_date ) && ! empty( $document ) ) {
							$custom = $document->get_date( 'invoice', $$data_source, 'view', true );
						}
						break;
					case 'invoice_notes':
						if ( ! empty( $document ) ) {
							if( $document->get_type() != 'invoice' ) {
								$invoice = wcpdf_get_invoice( $order );
								if( ! empty( $invoice ) && is_callable( array( $invoice, 'get_document_notes' ) ) ) {
									$custom = $invoice->get_document_notes();
								}
							} elseif( is_callable( array( $document, 'get_document_notes' ) ) ) {
								$custom = $document->get_document_notes();
							}
						}
						break;
					case 'document_notes':
						if ( ! empty( $document ) && is_callable( array( $document, 'get_document_notes' ) ) ) {
							$custom = $document->get_document_notes();
						}
						break;
					case 'document_number':
						if (!empty($document)) {
							if ( $number = $document->get_number() ) {
								$custom = $number->get_formatted();
							}
						}
						break;
					case 'document_date':
						if (!empty($document)) {
							if ( $date = $document->get_date() ) {
								$date_format = function_exists( 'wcpdf_date_format' ) ? wcpdf_date_format( $document, 'document_date' ) : wc_date_format();
								$custom      = $date->date_i18n( $date_format );
							}
						}
						break;
					case 'site_title':
						$custom = get_bloginfo();
						break;
					case 'shipping_notes':
					case 'customer_notes':
					case 'customer_note':
						$custom = $$data_source->get_customer_note();
						if (!empty($custom)) {
							$custom = wpautop( wptexturize( $custom ) );
						}
						break;
					case 'order_notes':
						$custom = $this->get_order_notes( $$data_source );
						break;
					case 'private_order_notes':
						$custom = $this->get_order_notes( $$data_source, 'private' );
						break;
					case 'order_number':
						if ( method_exists( $$data_source, 'get_order_number' ) ) {
							$custom = ltrim($$data_source->get_order_number(), '#');
						}
						break;
					case 'order_status':
						$custom = wc_get_order_status_name( $$data_source->get_status() );
						break;
					case 'payment_status':
						if ( is_callable( array( $$data_source, 'is_paid' ) ) ) {
							$custom = $$data_source->is_paid() ? __( 'Paid', 'wpo_wcpdf_templates' ) : __( 'Unpaid', 'wpo_wcpdf_templates' );
						}
						break;
					case 'order_date':
						$order_date  = $$data_source->get_date_created();
						$date_format = function_exists( 'wcpdf_date_format' ) ? wcpdf_date_format( $document, 'order_date_created' ) : wc_date_format();
						$custom      = $order_date->date_i18n( $date_format );
						break;
					case 'order_time':
						$order_date = $$data_source->get_date_created();
						$custom = $order_date->date_i18n( wc_time_format() );
						break;
					case 'order_weight':
						$custom = $this->get_order_weight( $$data_source, $document );
						break;
					case 'order_qty':
						$custom = $this->get_order_total_qty( $$data_source, $document );
						break;
					case 'date_paid':
					case 'paid_date':
					case 'time_paid':
					case 'paid_time':
					case 'date_completed':
					case 'completed_date':
						$custom = $this->get_date( $placeholder_clean, $$data_source );
						break;
					case 'current_date':
						$date_format = function_exists( 'wcpdf_date_format' ) ? wcpdf_date_format( $document, 'current_date' ) : wc_date_format();
						$custom      = date_i18n( $date_format );
						break;
					case 'payment_method_title':
						$custom = $document->get_payment_method();
						break;
					case 'payment_method_description':
						if ( $payment_gateway = wc_get_payment_gateway_by_order( $$data_source ) ) {
							$custom = $payment_gateway->get_description();
						}
						break;
					case 'payment_method_instructions':
						if ( $payment_gateway = wc_get_payment_gateway_by_order( $$data_source ) ) {
							if ( isset( $payment_gateway->instructions ) ) {
								$custom = $payment_gateway->instructions;
							}
						}
						break;
					case 'payment_method_thankyou_page_text':
						if ( $payment_gateway = wc_get_payment_gateway_by_order( $$data_source ) ) {
							if ( method_exists( $payment_gateway, 'thankyou_page' ) ) {
								ob_start();
								$payment_gateway->thankyou_page( $$data_source->get_id() );
								$custom = ob_get_clean();
								if (!empty($custom)) {
									$custom = str_replace( PHP_EOL, '', $custom );
								}
							}
						}
						break;
					case 'used_coupons':
						if ( version_compare( WOOCOMMERCE_VERSION, '3.7', '<' ) ) { // backwards compatibility
							$custom = implode(', ', $$data_source->get_used_coupons() );
						} else {
							$custom = implode(', ', $$data_source->get_coupon_codes() );
						}
						$text = str_replace($placeholder, $custom, $text);
						continue 3; // do not fallback to parent order
					case 'current_user_name':
						$user = wp_get_current_user();
						if ( $user instanceof \WP_User ) {
							$custom = $user->display_name;
						}
						break;
					case 'formatted_order_total':
						if (!empty($document)) {
							$grand_total 	= $document->get_order_grand_total('incl');
							$custom			= $grand_total['value'];
						}
						break;
					case 'formatted_subtotal':
						if (!empty($document)) {
							$subtotal 		= $document->get_order_subtotal('incl');
							$custom			= $subtotal['value'];
						}
						break;
					case 'formatted_discount':
						if (!empty($document)) {
							$discount 		= $document->get_order_discount('total', 'incl');
							$custom			= isset($discount['value']) ? $discount['value'] : '';
						}
						break;
					case 'formatted_shipping':
						if (!empty($document)) {
							$shipping 		= $document->get_order_shipping('incl');
							$custom			= $shipping['value'];
						}
						break;
					case 'formatted_order_total_ex':
						if (!empty($document)) {
							$grand_total 	= $document->get_order_grand_total('excl');
							$custom			= $grand_total['value'];
						}
						break;
					case 'formatted_subtotal_ex':
						if (!empty($document)) {
							$subtotal 		= $document->get_order_subtotal('excl');
							$custom			= $subtotal['value'];
						}
						break;
					case 'formatted_discount_ex':
						if (!empty($document)) {
							$discount 		= $document->get_order_discount('total', 'excl');
							$custom			= isset($discount['value']) ? $discount['value'] : '';
						}
						break;
					case 'formatted_shipping_ex':
						if (!empty($document)) {
							$shipping 		= $document->get_order_shipping('excl');
							$custom			= $shipping['value'];
						}
						break;
					case 'document_barcode':
						if ( ! empty( $document ) && function_exists( 'wcub_get_barcode' ) ) {
							$barcode = wcub_get_barcode( $document );
							if( $barcode->exists() ) {
								$custom = '<div class="wcub-document-barcode">'.$barcode->get_output().'</div>';
							}
						}
						break;
					case 'order_barcode':
						if ( ! empty( $$data_source ) && function_exists( 'wcub_get_barcode' ) ) {
							$barcode = wcub_get_barcode( $$data_source );
							if( $barcode->exists() ) {
								$custom = '<div class="wcub-order-barcode">'.$barcode->get_output().'</div>';
							}
						}
						break;
					case 'wc_order_barcode':
						$barcode_text = $$data_source->get_meta( '_barcode_text' );
						if ( function_exists( 'WC_Order_Barcodes' ) && ! empty( $barcode_text ) ) {
							if ( is_callable( array( WC_Order_Barcodes(), 'barcode_url' ) ) ) {
								$src = WC_Order_Barcodes()->barcode_url( $$data_source->get_id() );
							} else {
								$src = trailingslashit( get_site_url() ) . '?wc_barcode=' . $barcode_text;
							}

							if ( apply_filters( 'wpo_wcpdf_templates_wc_order_barcodes_use_http', false ) ) {
								$src  = str_replace( 'https://', 'http://', $src );
							}

							if ( apply_filters( 'wpo_wcpdf_templates_wc_order_barcodes_prefetch_image_data', true ) ) {
								try {
									$barcode_request = wp_remote_get( $src );
									if ( $barcode_request && ! is_wp_error( $barcode_request ) && $barcode_image_data = wp_remote_retrieve_body( $barcode_request ) ) {
										$src = sprintf( 'data:image/png;base64,%s', base64_encode( $barcode_image_data ) );
									}
								} catch (\Throwable $th) {
									wcpdf_log_error( 'Error trying to fetch barcode data: ' . $th->getMessage(), 'critical', $th );					
								}
							}

							if ( WC_Order_Barcodes()->barcode_type == 'qr' ) {
								$css = 'height: 40mm; width: 40mm; position:relative';
							} else {
								$css = 'height: 10mm; width: 40mm; overflow:hidden; position:relative';
							}
							$custom = sprintf('<div style="text-align: center; width: 40mm;" class="wc-order-barcode"><div style="%s"><img src="%s" style="width: 40mm; height:40mm; position: absolute; bottom: 0mm; left: 0;"/></div><span class="wc-order-barcodes-text">%s</span></div>', $css, $src, $barcode_text );
						}
						break;
					case 'local_pickup_plus_pickup_details':
						$custom = $this->get_local_pickup_plus_pickup_details( $$data_source );
						break;
					case 'wpo_wcpdf_shop_name':
						if (!empty($document)) {
							$custom = $document->get_shop_name();
						}
						break;
					case 'wpo_wcpdf_shop_address':
						if ( ! empty( $document ) ) {
							$custom = $document->get_shop_address();
						}
						break;
					case 'wpo_wcpdf_footer':
						if ( ! empty( $document ) ) {
							$custom = $document->get_footer();
						}
						break;
					case 'wpo_wcpdf_extra_1':
						if ( ! empty( $document ) ) {
							$custom = $document->get_extra_1();
						}
						break;
					case 'wpo_wcpdf_extra_2':
						if ( ! empty( $document ) ) {
							$custom = $document->get_extra_2();
						}
						break;
					case 'wpo_wcpdf_extra_3':
						if ( ! empty( $document ) ) {
							$custom = $document->get_extra_3();
						}
						break;
					case 'spelled_out_total':
						if( extension_loaded( 'intl' ) && class_exists( 'NumberFormatter' ) ) {
							$number_formatter = new \NumberFormatter( determine_locale(), \NumberFormatter::SPELLOUT );
							$custom           = $number_formatter->format( $document->order->get_total() );
						}
						break;
					case 'checkout_payment_url':
					case 'payment_url':
						if (is_callable(array($$data_source,'get_checkout_payment_url'))) {
							$custom = $$data_source->get_checkout_payment_url();
						}
						break;
					case 'customer_order_count':
						if ( is_callable( array( $$data_source, 'get_customer_id' ) ) && function_exists( 'wc_get_customer_order_count' ) && ! empty( $customer_id = $$data_source->get_customer_id() ) ) {
							$custom = wc_get_customer_order_count( $customer_id );
						}
						break;
					case 'customer_total_spent':
						if ( is_callable( array( $$data_source, 'get_customer_id' ) ) && function_exists( 'wc_get_customer_total_spent' ) && ! empty( $customer_id = $$data_source->get_customer_id() ) ) {
							$spent   = wc_get_customer_total_spent( $customer_id );
							$custom  = $document->format_price( $spent );
						}
						break;
					case 'customer_registered_date':
						if ( is_callable( array( $$data_source, 'get_user' ) ) && function_exists( 'wc_string_to_datetime' ) && ! empty( $user = $$data_source->get_user() ) ) {
							$registered_date = wc_string_to_datetime( $user->user_registered );
							$date_format     = function_exists( 'wcpdf_date_format' ) ? wcpdf_date_format( $document, 'customer_registered_date' ) : wc_date_format();
							$custom          = $registered_date->date_i18n( $date_format );
						}
						break;
					case 'ABSPATH':
						if ( defined( 'ABSPATH' ) ) {
							$custom = ABSPATH;
						}
						break;
					case 'WP_CONTENT_DIR':
						if ( defined( 'WP_CONTENT_DIR' ) ) {
							$custom = WP_CONTENT_DIR;
						}
						break;
					default:
						break;
				}
				if ( ! empty( $custom ) ) {
					$custom_with_excluded_hidden = $this->exclude_hidden_products(
						array(
							array(
								'type'  => trim( $placeholder, '{}' ),
								'value' => $custom
							)
						),
						$document
					);

					$text = str_replace( $placeholder, $custom_with_excluded_hidden[0]['value'], $text );
					continue 2;
				}

				// Order Properties
				if ( in_array( $placeholder_clean, array( 'shipping_address', 'billing_address' ) ) ) {
					$placeholder_clean = "formatted_{$placeholder_clean}";
				}

				$property_meta_keys = array(
					'_order_currency'     => 'currency',
					'_order_tax'          => 'total_tax',
					'_order_total'        => 'total',
					'_order_version'      => 'version',
					'_order_shipping'     => 'shipping_total',
					'_order_shipping_tax' => 'shipping_tax',
				);
				if ( in_array( $placeholder_clean, array_keys( $property_meta_keys ) ) ) {
					$property_name = $property_meta_keys[$placeholder_clean];
				} else {
					$property_name = str_replace( '-', '_', sanitize_title( ltrim( $placeholder_clean, '_' ) ) );
				}
				if ( is_callable( array( $$data_source, "get_{$property_name}" ) ) ) {
					$prop = call_user_func( array( $$data_source, "get_{$property_name}" ) );
					if ( ! empty( $prop ) ) {
						$text = str_replace( $placeholder, $prop, $text );
						continue 2;
					}
				}

				// Order Meta
				if ( ! $this->is_order_prop( $placeholder_clean ) ) {
					$meta = $$data_source->get_meta( $placeholder_clean );
					if ( ! empty( $meta ) ) {
						// format date fields with WC format automatically
						$meta = $this->maybe_format_date_field( $meta, $placeholder_clean );

						$text = str_replace( $placeholder, $meta, $text );
						continue 2;
					} else {
						// Fallback to hidden meta
						$meta = $$data_source->get_meta( "_{$placeholder_clean}" );
						if ( ! empty( $meta ) ) {
							$text = str_replace( $placeholder, $meta, $text );
							continue 2;
						}
					}
				}
			}

			// remove placeholder if no replacement was made
			$text = str_replace( $placeholder, '', $text );
		}

		return $text;
	}

	public function maybe_format_date_field( $date_value, $meta_key ) {
		$known_date_fields = apply_filters( 'wpo_wcpdf_templates_format_date_fields', array(
			'_local_pickup_time_select', // WooCommerce Local Pickup Time Select - array with timestamp
			'ywcdd_order_delivery_date', // YITH WooCommerce Delivery Date Premium
			'_orddd_timestamp',          // Order Delivery Date Pro (Tyche)
			'_orddd_lite_timestamp',     // Order Delivery Date Lite (Tyche)
			'_delivery_date',            // WooCommerce Order Delivery ... or generic
		) );

		if ( in_array( $meta_key, $known_date_fields ) ) {
			if ( $meta_key == '_local_pickup_time_select' && is_array( $date_value ) ) {
				$date_value = array_shift( $date_value );
			}

			// could be timestamp or formatted date
			if ( is_numeric( $date_value ) && intval( $date_value ) > 30000101 ) { // avoid colision with Ymd date strings until January 1st, 3000
				$timestamp = intval( $date_value );
			} elseif ( is_string( $date_value ) ) {
				$timestamp = strtotime( $date_value );
			} else { // not something we can use
				return $date_value;
			}

			// sanity check (party like it's 1999, huh?)
			if ( $timestamp > strtotime( '1999-12-31' ) ) {
				$wcpdf_date_format = function_exists( 'wcpdf_date_format' ) ? wcpdf_date_format() : wc_date_format();

				// determine whether to include time in formatted date (if the original format had it)
				if ( $meta_key == '_local_pickup_time_select' || ( !is_numeric( $date_value ) && strpos( (string) $date_value, ':' ) !== false ) ) {
					$date_format = $wcpdf_date_format . ' ' . wc_time_format();
				} else {
					$date_format = $wcpdf_date_format;
				}

				$date_value = date_i18n( apply_filters( 'wpo_wcpdf_templates_date_field_format', $date_format ), $timestamp );
			}
		}

		return $date_value;
	}

	public function is_order_prop( $key ) {
		// Taken from WC class
		$order_props = array(
			// Abstract order props
			'parent_id',
			'status',
			'currency',
			'version',
			'prices_include_tax',
			'date_created',
			'date_modified',
			'discount_total',
			'discount_tax',
			'shipping_total',
			'shipping_tax',
			'cart_tax',
			'total',
			'total_tax',
			// Order props
			'customer_id',
			'order_key',
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_postcode',
			'billing_country',
			'billing_email',
			'billing_phone',
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_city',
			'shipping_state',
			'shipping_postcode',
			'shipping_country',
			'payment_method',
			'payment_method_title',
			'transaction_id',
			'customer_ip_address',
			'customer_user_agent',
			'created_via',
			'customer_note',
			'date_completed',
			'date_paid',
			'cart_hash',
		);
		return in_array($key, $order_props);
	}

	public function make_item_replacements( $text, $item, $document ) {
		// make replacements if placeholders present
		if ( strpos( $text, '{{' ) === false ) {
			return $text;
		}

		// make an index of placeholders used in the text
		preg_match_all('/\{\{.*?\}\}/', $text, $placeholders_used);
		$placeholders_used = array_shift($placeholders_used); // we only need the first match set

		// loop through placeholders and make replacements
		foreach ($placeholders_used as $placeholder) {
			$replacement = null;
			$placeholder_clean = trim($placeholder,"{{}}");

			// custom product field placeholders
			if ( strpos($placeholder_clean, 'product_custom_field::') !== false ) {
				$meta_key = trim(str_replace('product_custom_field::', '', $placeholder_clean));
				if (!empty($meta_key)) {
					$replacement = $this->get_product_custom_field( $item['product'], $meta_key );
					if (!empty($replacement)) {
						$text = str_replace($placeholder, $replacement, $text);
						continue;
					}
				}
			}

			// custom product field placeholders
			if ( strpos($placeholder_clean, 'item_meta::') !== false ) {
				$meta_key = trim(str_replace('item_meta::', '', $placeholder_clean));
				if (!empty($meta_key)) {
					$replacement = $this->get_order_item_meta( $item, $meta_key );
					if (!empty($replacement)) {
						$text = str_replace($placeholder, $replacement, $text);
						continue;
					}
				}
			}

			// product attribute placeholders
			if ( strpos($placeholder_clean, 'product_attribute::') !== false && !empty( $item['product'] ) ) {
				$attribute_name = trim(str_replace('product_attribute::', '', $placeholder_clean));
				if (!empty($attribute_name)) {
					$replacement = $document->get_product_attribute( $attribute_name, $item['product'] );
					if (!empty($replacement)) {
						$text = str_replace($placeholder, $replacement, $text);
						continue;
					}
				}
			}

			switch ($placeholder_clean) {
				case 'item_id':
					$replacement = $item['item_id'] != 0 ? $item['item_id'] : '';
					break;
				case 'product_id':
					$replacement = $item['product_id'] != 0 ? $item['product_id'] : '';
					break;
				case 'variation_id':
					$replacement = $item['variation_id'] != 0 ? $item['variation_id'] : '';
					break;
				case 'product_description':
					$replacement = $this->get_product_description( $item['product'] );
					break;
				case 'product_description_short':
					$replacement = $this->get_product_description( $item['product'], 'short' );
					break;
				case 'product_description_long':
					$replacement = $this->get_product_description( $item['product'], 'long' );
					break;
				case 'product_description_variation':
					$replacement = $this->get_product_description( $item['product'], 'variation' );
					break;
				case 'product_categories':
					$replacement = $this->get_product_categories( $item['product'] );
					break;
				case 'product_tags':
					$replacement = $this->get_product_tags( $item['product'] );
					break;
				case 'purchase_note':
					$replacement = $this->get_product_purchase_note( $item['product'] );
					break;
				case 'product_dimensions':
					$replacement = $this->get_product_dimensions( $item['product'] );
					break;
				case 'product_length':
					$replacement = is_callable( array( $item['product'], 'get_length' ) ) ? wc_format_dimensions( array( $item['product']->get_length() ) ) : '';
					break;
				case 'product_width':
					$replacement = is_callable( array( $item['product'], 'get_width' ) ) ? wc_format_dimensions( array( $item['product']->get_width() ) ) : '';
					break;
				case 'product_height':
					$replacement = is_callable( array( $item['product'], 'get_height' ) ) ? wc_format_dimensions( array( $item['product']->get_height() ) ) : '';
					break;
				case 'product_weight':
					$replacement = is_callable( array( $item['product'], 'get_weight' ) ) ? wc_format_weight( $item['product']->get_weight() ) : '';
					break;
				case 'sale_price_discount_excl_tax':
					$replacement = $this->get_sale_price_discount( $item['item'], $item['item_id'], $document->order, 'price_excl_tax' );
					break;
				case 'sale_price_discount_incl_tax':
					$replacement = $this->get_sale_price_discount( $item['item'], $item['item_id'], $document->order, 'price_incl_tax' );
					break;
				case 'sale_price_discount_percent':
					$replacement = $this->get_sale_price_discount( $item['item'], $item['item_id'], $document->order, 'percent' );
					break;
				case 'wc_brands':
					$replacement = $this->get_product_brands( $item['product'] );
					break;
				case 'sku':
					$replacement = $item['sku'];
					break;
				case 'wpo_batch_number':
					if ( function_exists( 'wpo_wcpbn_get_item_batch_numbers' ) ) {
						$replacement = wpo_wcpbn_get_item_batch_numbers( $item['item'] );
					}
					break;
				case 'wpo_batch_expiry_date':
					if ( function_exists( 'wpo_wcpbn_get_item_batch_expiry_dates' ) ) {
						$replacement = wpo_wcpbn_get_item_batch_expiry_dates( $item['item'] );
					}
					break;
				case 'product_barcode':
					if ( function_exists( 'wcub_get_barcode' ) ) {
						$barcode = wcub_get_barcode( $item['product'] );
						if( $barcode->exists() ) {
							$replacement = '<div class="wcub-product-barcode">'.$barcode->get_output().'</div>';
						}
					}
					break;
			}

			$replacement = apply_filters( 'wpo_wcpdf_custom_item_placeholder_' . sanitize_title( $placeholder_clean ), $replacement, $text, $item, $document );

			if (!empty($replacement)) {
				$text = str_replace($placeholder, $replacement, $text);
				continue;
			}

			// remove placeholder if no replacement was made
			$text = str_replace($placeholder, '', $text);
		}

		return $text;
	}

	public function get_product_custom_field( $product, $meta_key ) {
		if ( isset( $product ) && ! empty( $meta_key ) ) {
			// backwards compatible meta keys of properties
			$property_meta_keys = array(
				'_stock' => 'stock_quantity',
			);
			$property = in_array( $meta_key, array_keys( $property_meta_keys ) ) ? $property_meta_keys[$meta_key] : str_replace( '-', '_', sanitize_title( ltrim( $meta_key, '_' ) ) );

			// try actual product first, starting with properties
			if ( is_callable( array( $product, "get_{$property}" ) ) ) {
				$custom = $product->{"get_{$property}"}( 'view' );
			}
			if ( empty( $custom ) ) {
				$custom = $product->get_meta( $meta_key );
			}

			// fallback to parent for variations
			if ( empty( $custom ) && $product->is_type( 'variation' ) ) {
				$_product = wc_get_product( $product->get_parent_id() );
				// try actual product first, starting with properties
				if ( is_callable( array( $_product, "get_{$property}" ) ) ) {
					$custom = $_product->{"get_{$property}"}( 'view' );
				}
				if ( empty( $custom ) ) {
					$custom = $_product->get_meta( $meta_key );
				}
			}

			return $custom;
		} else {
			return '';
		}
	}

	public function get_order_item_meta( $document_item, $meta_key ) {
		return wc_get_order_item_meta( $document_item['item_id'], $meta_key, true );
	}

	public function get_product_description( $product, $type = 'short', $use_variation_description = true ) {
		if (! empty( $product ) ) {
			if ( $type == 'variation' ) {
				$description = $product->is_type( 'variation' ) ? $product->get_description() : '';
			} elseif ( isset( $use_variation_description ) && $product->is_type( 'variation' ) ) {
				$description = $product->get_description();
			} else {
				if ( $product->is_type( 'variation' ) ) {
					$_product = wc_get_product( $product->get_parent_id() );
				} else {
					$_product = $product;
				}
				switch ( $type ) {
					case 'short':
						if ( method_exists( $_product, 'get_short_description' ) ) {
							$description = $_product->get_short_description();
						} else {
							$description = $_product->post->post_excerpt;
						}
						break;
					case 'long':
						if ( method_exists( $_product, 'get_description' ) ) {
							$description = $_product->get_description();
						} else {
							$description = $_product->post->post_content;
						}
						break;
				}
			}
		} else {
			$description = '';
		}

		return $description;
	}

	public function get_product_categories( $product ) {
		if (isset($product)) {
			if (function_exists('wc_get_product_category_list')) {
				// WC3.0+
				if ( $product->is_type( 'variation' ) ) {
					// variations don't have categories so we take the parent
					$category_list = wc_get_product_category_list( $product->get_parent_id() );
				} else {
					$category_list = wc_get_product_category_list( $product->get_id() );
				}
			} else {
				$category_list = $product->get_categories();
			}
			$product_categories = strip_tags( $category_list );
		} else {
			$product_categories = '';
		}
		return $product_categories;
	}

	public function get_product_tags( $product ) {
		if (isset($product)) {
			if (function_exists('wc_get_product_tag_list')) {
				// WC3.0+
				if ( $product->is_type( 'variation' ) ) {
					// variations don't have tags so we take the parent
					$tag_list = wc_get_product_tag_list( $product->get_parent_id() );
				} else {
					$tag_list = wc_get_product_tag_list( $product->get_id() );
				}
			} else {
				$tag_list = $product->get_tags();
			}
			$product_tags = strip_tags( $tag_list );
		} else {
			$product_tags = '';
		}
		return $product_tags;
	}

	public function get_product_purchase_note( $product ) {
		if (!empty($product)) {
			$purchase_note = method_exists($product, 'get_purchase_note') ? $product->get_purchase_note() : $product->purchase_note;
			$purchase_note = do_shortcode( wp_kses_post( $purchase_note ) );
		} else {
			$purchase_note = '';
		}
		return $purchase_note;
	}

	public function get_product_dimensions( $product ) {
		if ( !empty($product) && function_exists('wc_format_dimensions') && is_callable( array( $product, 'get_dimensions' ) ) ) {
			return wc_format_dimensions( $product->get_dimensions( false ) );
		} else {
			return '';
		}
	}

	public function get_sale_price_discount( $item, $item_id, $order, $type = null ) {
		$regular_prices = $this->get_regular_item_price( $item, $item_id, $order );

		if ( round( $item['line_total'], 2 ) == round( $regular_prices['excl'] * $item['qty'], 2 ) ) {
			return '';
		}

		switch ( $type ) {
			default:
			case 'price_excl_tax':
				$item_price    = $item['line_total']; // before coupon discounts
				$regular_price = $regular_prices['excl'] * $item['qty'];
				return wc_price( $regular_price - $item_price, array ( 'currency' => $order->get_currency() ) );
			case 'price_incl_tax':
				$item_price    = $item['line_total'] + $item['line_tax']; // before coupon discounts
				$regular_price = $regular_prices['incl'] * $item['qty'];
				return wc_price( $regular_price - $item_price, array ( 'currency' => $order->get_currency() ) );
			case 'percent':
				$item_price    = $item['line_total'] + $item['line_tax']; // before coupon discounts
				$regular_price = $regular_prices['incl'] * $item['qty'];

				if ( $regular_price > 0 ) {
					$percent   = ( ( $regular_price - $item_price ) / $regular_price ) * 100;
					$precision = apply_filters( 'wpo_wcpdf_discount_percentage_precision', 0 );
					$percent   = number_format( $percent, $precision, wc_get_price_decimal_separator(), '' );
					return "{$percent}%";
				} else {
					return '';
				}
		}
	}

	public function get_product_brands( $product ) {
		if ( function_exists( 'get_brands ') && ! empty( $product ) ) {
			if ( $product->is_type( 'variation' ) && is_callable( array( $product, 'get_parent_id' ) ) ) {
				$product_id = $product->get_parent_id();
			} else {
				$product_id = $product->get_id();
			}

			$terms = get_the_terms( $product_id, 'product_brand' );
			$brand_count = is_array( $terms ) ? sizeof( $terms ) : 0;
			if ( $brand_count == 0 ) {
				return '';
			}

			$taxonomy = get_taxonomy( 'product_brand' );
			$labels   = $taxonomy->labels;

			$brands = get_brands( $product_id, ', ' );
			$label = '<span class="wc-brands-label">' . sprintf( _n( '%1$s: ', '%2$s: ', $brand_count ), $labels->singular_name, $labels->name ). '</span>';
			return sprintf( '<div class="brands">%s %s</div>', $label, $brands );
		} else {
			return '';
		}
	}

	public function get_order_notes( $order, $filter = 'customer' ) {
		if ( 'shop_order_refund' == $order->get_type() && is_callable( array( $order, 'get_parent_id' ) ) ) {
			$post_id = $order->get_parent_id();
		} else {
			$post_id = $order->get_id();
		}

		$args = array(
			'post_id' 	=> $post_id,
			'approve' 	=> 'approve',
			'type' 		=> 'order_note'
		);

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		$notes = get_comments( $args );

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		if ( $notes ) {
			$formatted_notes = array();
			foreach( $notes as $key => $note ) {
				if ( $filter == 'customer' && !get_comment_meta( $note->comment_ID, 'is_customer_note', true ) ) {
					unset($notes[$key]);
					continue;
				}
				if ( $filter == 'private' && get_comment_meta( $note->comment_ID, 'is_customer_note', true ) ) {
					unset($notes[$key]);
					continue;
				}
				$note_classes   = array( 'note_content' );
				$note_classes[] = ( __( 'WooCommerce', 'woocommerce' ) === $note->comment_author ) ? 'system-note' : '';

				$formatted_notes[$key] = sprintf( '<div class="%s">%s</div>', esc_attr( implode( ' ', $note_classes ) ), wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ) );
			}
			return implode("\n", $formatted_notes);
		} else {
			return false;
		}
	}

	public function add_tax_base( $taxes, $order ) {
		$tax_rates_base = $this->get_tax_rates_base( $order );
		foreach ($taxes as $item_id => $tax) {
			if ( isset( $tax_rates_base[$tax['rate_id']] ) ) {
				$taxes[$item_id]['base'] = $tax_rates_base[$tax['rate_id']]->base;
				$taxes[$item_id]['calculated_rate'] = $tax_rates_base[$tax['rate_id']]->calculated_rate;
			}

			$created_via = is_callable( array( $order, 'get_created_via' ) ) ? $order->get_created_via() : false;
			if ( $created_via == 'subscription' ) {
				// subscription renewals didn't properly record the rate_percent property between WC3.7 and WCS3.0.1
				// so we use a fallback if the rate_percent = 0
				// if we the tax is bigger than 0 stored the rate percentage in the past, use that
				$tax_amount = $tax['tax_amount'] + $tax['shipping_tax_amount'];
				if ( $tax_amount > 0 && isset($tax_rates_base[$tax['rate_id']]->rate_percent) && $tax_rates_base[$tax['rate_id']]->rate_percent > 0 ) {
					$taxes[$item_id]['stored_rate'] = $this->format_tax_rate( $tax_rates_base[$tax['rate_id']]->rate_percent );
				} elseif ( is_numeric($item_id) && $tax_amount > 0 && $stored_rate = wc_get_order_item_meta( absint($item_id), '_wcpdf_rate_percentage', true ) ) {
					$taxes[$item_id]['stored_rate'] = $this->format_tax_rate( $stored_rate );
				}
				// not setting 'stored_rate' will let the plugin fall back to the calculated_rate
			} elseif ( method_exists( $order, 'get_version' ) && version_compare( $order->get_version(), '3.7', '>=' ) && version_compare( WC_VERSION, '3.7', '>=' ) && isset( $tax_rates_base[$tax['rate_id']] ) ) {
				$taxes[$item_id]['stored_rate'] = $this->format_tax_rate( $tax_rates_base[$tax['rate_id']]->rate_percent );
			} elseif ( is_numeric($item_id) && $stored_rate = wc_get_order_item_meta( absint($item_id), '_wcpdf_rate_percentage', true ) ) {
				$taxes[$item_id]['stored_rate'] = $this->format_tax_rate( $stored_rate );
			}
		}
		return $taxes;
	}

	public function get_tax_rates_base( $order ) {
		// get tax totals from order and preset base
		$taxes = $this->get_tax_totals( $order );
		foreach ($taxes as $rate_id => $tax) {
			$tax->base = $tax->shipping_tax_amount = 0;
		}

		$hide_zero_tax = apply_filters( 'wpo_wcpdf_tax_rate_base_hide_zero', apply_filters( 'woocommerce_order_hide_zero_taxes', true ) );

		// get subtotals from regular line items and fees
		$items = $order->get_items( array( 'fee', 'line_item', 'shipping' ) );
		foreach ($items as $item_id => $item) {
			// get tax data
			if ( $item['type'] == 'shipping' ) {
				$line_taxes = maybe_unserialize( $item['taxes'] );
				// WC3.0 stores taxes as 'total' (like line items);
				if (isset($line_taxes['total'])) {
					$line_taxes = $line_taxes['total'];
				}
			} else {
				$line_tax_data = maybe_unserialize( $item['line_tax_data'] );
				$line_taxes = $line_tax_data['total'];
			}

			foreach ( $line_taxes as $rate_id => $tax ) {
				if ( isset( $taxes[$rate_id] ) ) {
					// convert tax to float, but only if numeric
					$tax = (is_numeric($tax)) ? (float) $tax : $tax;
					if ( ( is_float( $tax ) && abs( $tax ) > 0.0 ) || ( $tax === 0.0 && $hide_zero_tax === false ) ) {
						$taxes[$rate_id]->base += apply_filters( 'wpo_wcpdf_item_tax_rate_base', ($item['type'] == 'shipping') ? floatval( $item['cost'] ) : floatval( $item['line_total'] ), $item, $taxes[$rate_id], $order );
						if ($item['type'] == 'shipping') {
							$taxes[$rate_id]->shipping_tax_amount += floatval( $tax );
						}
					}
				}
			}
		}

		// add calculated rate
		foreach ($taxes as $rate_id => $tax) {
			$calculated_rate = $this->calculate_tax_rate( $tax->base, $tax->amount );
			if (function_exists('wc_get_price_decimal_separator')) {
				$tax_rate = str_replace('.', wc_get_price_decimal_separator(), strval($calculated_rate) );
			}
			$taxes[$rate_id]->calculated_rate = $calculated_rate;
		}

		return $taxes;
	}

	public function get_tax_totals( $order ) {
		$taxes = array();
		$merge_by_code = apply_filters( 'wpo_wcpdf_tax_rate_base_merge_by_code', false );
		if ( $merge_by_code ) {
			// get taxes from WC
			$tax_totals = $order->get_tax_totals();
			// put taxes in new array with tax_id as key
			foreach ( $tax_totals as $code => $tax ) {
				$tax->code            = $code;
				$taxes[$tax->rate_id] = $tax;
			}
		} else {
			// DON'T MERGE BY CODE
			foreach ( $order->get_items( 'tax' ) as $key => $tax ) {
				$code    = $tax->get_rate_code();
				$rate_id = $tax->get_rate_id();

				if ( ! isset( $taxes[ $rate_id ] ) ) {
					$taxes[ $rate_id ]         = new \stdClass();
					$taxes[ $rate_id ]->amount = 0;
				}

				$taxes[ $rate_id ]->id                = $key;
				$taxes[ $rate_id ]->base              = 0;
				$taxes[ $rate_id ]->code              = $code;
				$taxes[ $rate_id ]->rate_id           = $rate_id;
				$taxes[ $rate_id ]->is_compound       = $tax->is_compound();
				$taxes[ $rate_id ]->label             = $tax->get_label();
				$taxes[ $rate_id ]->amount           += (float) $tax->get_tax_total() + (float) $tax->get_shipping_tax_total();
				$taxes[ $rate_id ]->formatted_amount  = wc_price( wc_round_tax_total( $taxes[ $rate_id ]->amount ), array( 'currency' => $order->get_currency() ) );

				// WC3.7 stores rate percent
				if ( is_callable( array( $tax, 'get_rate_percent' ) ) ) {
					$taxes[ $rate_id ]->rate_percent = $tax->get_rate_percent();
				}
			}

			if ( apply_filters( 'woocommerce_order_hide_zero_taxes', true ) ) {
				$amounts = array_filter( wp_list_pluck( $taxes, 'amount' ) );
				$taxes   = array_intersect_key( $taxes, $amounts );
			}
		}
		return $taxes;
	}

	public function calculate_tax_rate( $price_ex_tax, $tax ) {
		if ( $price_ex_tax != 0) {
			$tax_rate = $this->format_tax_rate( ($tax / $price_ex_tax)*100 );
		} else {
			$tax_rate = '-';
		}
		return $tax_rate;
	}

	public function format_tax_rate( $tax_rate ) {
		$precision = apply_filters( 'wpo_wcpdf_calculate_tax_rate_precision', 1 );
		$formatted_tax_rate = round( (float) $tax_rate , $precision ).' %';
		return apply_filters( 'wpo_wcpdf_formatted_tax_rate', $formatted_tax_rate, $tax_rate );
	}

	public function save_regular_item_price( $order_id, $posted = array() ) {
		if ( $order = wc_get_order( $order_id ) ) {
			$items = $order->get_items();
			if (empty($items)) {
				return;
			}

			foreach ($items as $item_id => $item) {
				// this function will directly store the item price
				$regular_price = $this->get_regular_item_price( $item, $item_id, $order );
			}
		}
	}
	
	public function get_item_vat_column_data( $price_type, $item, $document, $split = array(), $dash_for_zero = false ) {
		$column_data = '';
		
		switch ( $price_type ) {
			case 'single_before':
				if ( ! empty( $item['item'] ) ) {
					$line_subtotal_tax = isset( $split['subtotal'] ) ? $split['subtotal'] : $item['item']['line_subtotal_tax'];
					$price             = ( $line_subtotal_tax ) / max( 1, $item['quantity'] );
					$column_data       = ( 0 == $price && $dash_for_zero ) ? '—' : $document->format_price( $price );
				}
				break;
			case 'single_after':
				if ( ! empty( $item['item'] ) ) {
					if ( isset( $split['subtotal'] ) && isset( $split['discount_tax'] ) && isset( $split['multiple'] ) ) {
						$discount_tax = ( 0 != $split['discount_tax'] && $split['multiple'] ) ? $split['discount_tax'] / max( 1, $item['quantity'] ) : $split['discount_tax'];
						$line_tax     = ( 0 != $split['subtotal'] ) ? $split['subtotal'] - $discount_tax : $split['subtotal'];
					} else {
						$price        = $item['item']['line_tax'];
						$line_tax     = ( false !== strpos( $price, '<bdi>0,00&nbsp;' ) && $dash_for_zero ) ? '—' : $price;
					}
					
					$price       = ( $line_tax ) / max( 1, $item['quantity'] );
					$column_data = ( 0 == $price && $dash_for_zero ) ? '—' : $document->format_price( $price );
				}
				break;
			case 'total_before':
				if ( isset( $split['total'] ) && isset( $split['discount_tax'] ) && isset( $split['multiple'] ) ) {
					$discount_tax = ( 0 != $split['discount_tax'] && $split['multiple'] ) ? $split['discount_tax'] / max( 1, $item['quantity'] ) : $split['discount_tax'];
					$price        = ( 0 != $split['total'] ) ? $split['total'] + $discount_tax : $split['total'];
					$column_data  = ( 0 == $price && $dash_for_zero ) ? '—' : $document->format_price( $price );
				} else {
					$price        = $item['line_subtotal_tax'];
					$column_data  = ( false !== strpos( $price, '<bdi>0,00&nbsp;' ) && $dash_for_zero ) ? '—' : $price;
				}
				break;
			case 'total_after':
				if ( isset( $split['total'] ) ) {
					$price       = $split['total'];
					$column_data = ( 0 == $price && $dash_for_zero ) ? '—' : $document->format_price( $price );
				} else {
					$price       = $item['line_tax'];
					$column_data = ( false !== strpos( $price, '<bdi>0,00&nbsp;' ) && $dash_for_zero ) ? '—' : $price;
				}
				break;
		}
		
		return apply_filters( 'wpo_wcpdf_item_vat_column_data', $column_data, $price_type, $item, $document, $split, $dash_for_zero );
	}

	public function get_item_tax_rate_name( $item, $order ) {
		$tax_names   = '';
		$order_taxes = $this->get_tax_totals( $order );

		if ( is_callable( array( $item, 'get_taxes' ) ) && $taxes = $item->get_taxes() ) {
			if ( ! empty( $taxes['total'] ) ) {
				$formatted_taxes = array();
				
				foreach ( $taxes['total'] as $tax_rate_id => $tax_amount ) {
					if ( apply_filters( 'woocommerce_order_hide_zero_taxes', true ) && $tax_amount == 0 ) {
						continue;
					}
					if ( $tax_amount != '' ) {
						$label             = $order_taxes[$tax_rate_id]->label;
						$rate              = $this->format_tax_rate( $order_taxes[$tax_rate_id]->rate_percent );
						$formatted_taxes[] = "{$label} {$rate}";
					}
				}

				$tax_names = implode( '<br>', $formatted_taxes );
			}
		}

		return $tax_names;
	}

	// get regular price from item - query product when not stored in item yet
	public function get_regular_item_price( $item, $item_id, $order ) {
		// first check if we alreay have stored the regular price of this item
		$regular_price = wc_get_order_item_meta( $item_id, '_wcpdf_regular_price', true );
		if ( !empty( $regular_price ) && is_array( $regular_price ) && array_key_exists( 'incl', $regular_price ) && array_key_exists( 'excl', $regular_price ) ) {
			return $regular_price;
		}

		if ( is_callable( array( $item, 'get_product' ) ) ) { // WC4.4+
			$product = $item->get_product();
		} else {
			$product = $order->get_product_from_item( $item );
		}

		if ($product) {
			$product_regular_price = $product->get_regular_price();
			// get different incarnations
			$regular_price = array(
				'incl'	=> wc_get_price_including_tax( $product, array( 'qty' => 1, 'price' => $product_regular_price ) ),
				'excl'	=> wc_get_price_excluding_tax( $product, array( 'qty' => 1, 'price' => $product_regular_price ) ),
			);
		} else {
			// fallback to item price
			$regular_price = array(
				'incl'	=> $order->get_line_subtotal( $item, true /* $inc_tax */, false ) / max( 1, abs( $item->get_quantity() ) ),
				'excl'	=> $order->get_line_subtotal( $item, false /* $inc_tax */, false ) / max( 1, abs( $item->get_quantity() ) ),
			);
		}

		wc_update_order_item_meta( $item_id, '_wcpdf_regular_price', $regular_price );
		return $regular_price;
	}

	public function get_discount_percentage( $order, $discount = null ) {
		if( is_null( $discount ) ) {
			if ( method_exists( $order, 'get_total_discount' ) ) {
				// WC2.3 introduced an $ex_tax parameter
				$ex_tax   = false;
				$discount = $order->get_total_discount( $ex_tax );
			} elseif ( method_exists( $order, 'get_discount_total' ) ) {
				// was this ever included in a release?
				$discount = $order->get_discount_total();
			} else {
				return false;
			}
		}

		$order_total = $order->get_total();

		// shipping and fees are not discounted
		$shipping_total = $order->get_total_shipping() + $order->get_shipping_tax();
		$fee_total = 0;
		if (method_exists($order, 'get_fees')) { // old versions of WC don't support fees
			foreach ( $order->get_fees() as $fees ) {
				$fee_total += $fees['line_total'] + $fees['line_tax'];
			}
		}

		$percentage = ( $discount / ( $order_total + $discount - $shipping_total - $fee_total) ) * 100;

		if ( round($percentage) > 0 ) {
			return $percentage;
		} else {
			return false;
		}
	}

	public function get_order_weight( $order, $document = null, $add_unit = true ) {
		$items = $order->get_items();
		$weight = 0;
		if( sizeof( $items ) > 0 ) {
			foreach( $items as $item_id => $item ) {
				if ( is_callable( array( $item, 'get_product' ) ) ) { // WC4.4+
					$product = $item->get_product();
				} else {
					$product = $order->get_product_from_item( $item );
				}

				if ( $this->subtract_refunded_qty( $document ) && $refunded_qty = $order->get_qty_refunded_for_item( $item_id ) ) {
					$qty = (int) $item['qty'] + $refunded_qty;
				} else {
					$qty = (int) $item['qty'];
				}

				if ( is_callable( array( $product, 'get_weight' ) ) && is_numeric($product->get_weight()) ) {
					$weight += $product->get_weight() * $qty;
				}
			}
		}
		if ( $add_unit == true ) {
			$weight = $this->format_weight ( $weight );
		}
		return apply_filters( 'wpo_wcpdf_templates_order_weight', $weight, $order, $document );
	}

	public function get_order_total_qty( $order, $document = null ) {
		$items = $order->get_items();
		$total_qty = 0;
		if( sizeof( $items ) > 0 ) {
			foreach( $items as $item_id => $item ) {
				// only count visible items (product bundles compatibility)
				if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
					continue;
				}
				$total_qty += $item['qty'];

				if ( $this->subtract_refunded_qty( $document ) && $refunded_qty = $order->get_qty_refunded_for_item( $item_id ) ) {
					$total_qty += $refunded_qty;
				}
			}
		}

		return apply_filters( 'wpo_wcpdf_templates_order_qty', $total_qty, $order, $document );
	}

	public function get_date( $placeholder, $order ) {
		
		// parent order fallback
		if ( 'shop_order_refund' == $order->get_type() && is_callable( array( $order, 'get_parent_id' ) ) ) {
			$order = wc_get_order( $order->get_parent_id() );
		}

		switch ( $placeholder ) {
			case 'date_paid':
			case 'paid_date':
				$date_paid   = $order->get_date_paid();
				$date_format = function_exists( 'wcpdf_date_format' ) ? wcpdf_date_format( $document, 'order_date_paid' ) : wc_date_format();
				$date        = ! empty( $date_paid ) ? $date_paid->date_i18n( $date_format ) : '-';
				break;
			case 'time_paid':
			case 'paid_time':
				$date_paid = $order->get_date_paid();
				$date      = ! empty( $date_paid ) ? $date_paid->date_i18n( wc_time_format() ) : '-';
				break;
			case 'date_completed':
			case 'completed_date':
				$date_completed = $order->get_date_completed();
				$date_format    = function_exists( 'wcpdf_date_format' ) ? wcpdf_date_format( $document, 'order_date_completed' ) : wc_date_format();
				$date           = ! empty( $date_completed ) ? $date_completed->date_i18n( $date_format ) : '-';
				break;
			default:
				$date = '-';
				break;
		}

		return $date;
	}

	public function subtract_refunded_qty( $document ) {
		$subtract_refunded_qty = false;
		if (!empty($document) && $document->get_type() == 'packing-slip') {
			$packing_slip_settings = get_option( 'wpo_wcpdf_documents_settings_packing-slip' );
			if ( isset($packing_slip_settings['subtract_refunded_qty'] ) ) {
				$subtract_refunded_qty = true;
			}
		}
		return $subtract_refunded_qty;
	}

	// hide regular price item eta
	public function hide_regular_price_itemmeta( $hidden_keys ) {
		$hidden_keys[] = '_wcpdf_regular_price';
		return $hidden_keys;
	}

	public function array_keys_prefix( $array, $prefix, $add_or_remove = 'add' ) {
		if (empty($array) || !is_array($array) ) {
			return $array;
		}

		foreach ($array as $key => $value) {
			if ( $add_or_remove == 'add' ) {
				$array[$prefix.$key] = $value;
				unset($array[$key]);
			} else { // remove
				$new_key = str_replace($prefix, '', $key);
				$array[$new_key] = $value;
				unset($array[$key]);
			}
		}

		return $array;

	}

	public function get_local_pickup_plus_pickup_details( $order ) {
		if ( function_exists('wc_local_pickup_plus') ) {
			$local_pickup   = wc_local_pickup_plus();
			$orders_handler = $local_pickup->get_orders_instance();

			if ( $orders_handler && ( $pickup_data = $orders_handler->get_order_pickup_data( $order ) ) ) {
				ob_start();
				$shipping_method = $local_pickup->get_shipping_method_instance();
				$package_number = 1;
				$packages_count = count( $pickup_data );
				?>
				<div class="wc-local-pickup-plus-details">
				<h3><?php echo esc_html( $shipping_method->get_method_title() ); ?></h3>
				<?php foreach ( $pickup_data as $pickup_meta ) : ?>

					<div>
						<?php if ( $packages_count > 1 ) : ?>
							<h5><?php echo sprintf( '%1$s #%2$s', esc_html( $shipping_method->get_method_title() ), $package_number ); ?></h5>
						<?php endif; ?>
						<ul>
							<?php foreach ( $pickup_meta as $label => $value ) : ?>
								<li class="<?php echo sanitize_html_class( strtolower( str_replace( ' ', '-', $label ) ) ); ?>">
									<strong><?php echo esc_html( $label ); ?>:</strong> <?php echo wp_kses_post( $value ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
						<?php $package_number++; ?>
					</div>

				<?php endforeach; ?>
				</div>
				<?php
				$order_pickup_data = ob_get_clean();
				// remove line breaks, this is pure HTML
				$order_pickup_data = str_replace( array( "\r", "\n" ), '', $order_pickup_data );
				return $order_pickup_data;
			}
		}
	}

	/**
	 * Save tax rate percentage in tax meta every time totals are calculated
	 * @param  bool $and_taxes Calc taxes if true.
	 * @param  WC_Order $order Order object.
	 * @return void
	 */
	public function save_tax_rate_percentage_recalculate( $and_taxes, $order ) {
		// it seems $and taxes is mostly false, meaning taxes are calculated separately,
		// but we still update just in case anything changed
		if ( !empty( $order ) && method_exists( $order, 'get_version' ) && version_compare( $order->get_version(), '3.7', '>=' ) ) {
			return; // WC3.7 already stores the rate in the tax lines
		} else {
			$this->save_tax_rate_percentage( $order );
		}
	}

	public function save_tax_rate_percentage_frontend( $order_id, $posted ) {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '3.7', '<' ) ) {
			$order = wc_get_order( $order_id );
			if ( !empty( $order ) ) {
				$this->save_tax_rate_percentage( $order );
			}
		}
	}

	public function save_tax_rate_percentage( $order ) {
		foreach ( $order->get_taxes() as $item_id => $tax_item ) {
			if ( is_a( $tax_item, '\WC_Order_Item_Tax' ) && is_callable( array( $tax_item, 'get_rate_id' ) ) ) {
				// get tax rate id from item
				$tax_rate_id = $tax_item->get_rate_id();
				// read tax rate data from db
				if ( class_exists( '\WC_TAX' ) && is_callable( array( '\WC_TAX', '_get_tax_rate' ) ) ) {
					$tax_rate = \WC_Tax::_get_tax_rate( $tax_rate_id, OBJECT );
					if ( $tax_rate && ! empty( $tax_rate->tax_rate ) ) {
						// store percentage in tax item meta
						wc_update_order_item_meta( $item_id, '_wcpdf_rate_percentage', $tax_rate->tax_rate );
					}
				}
			}
		}
	}

	/**
	 * PHP Intl extension used by the spell out total placeholder (PHP NumberFormatter class)
	 * @param  array $server_configs
	 * @return array
	 */
	public function php_intl_check( $server_configs ) {
		$server_configs['Intl'] = array(
			'required' => __( 'Required when using the {{spelled_out_total}} placeholder', 'wpo_wcpdf_templates' ),
			'value'    => null,
			'result'   => extension_loaded('intl'),
		);

		return $server_configs;
	}

	/**
	 * Helper function to format the item weight
	 * @param  float $weight
	 * @return string
	 */
	public function format_weight( $weight ) {
		return wc_format_weight( $weight );
	}

} // end class

endif;
