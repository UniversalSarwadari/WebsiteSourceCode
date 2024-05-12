<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wpo_wcpdf_templates_get_table_headers( $document ) {
	$column_settings = WPO_WCPDF_Templates()->settings->get_settings( $document->get_type(), 'columns', $document );
	$order_discount  = $document->get_order_discount( 'total', 'incl' );
	$taxes           = $document->get_order_taxes();

	// mark first and last column
	if ( ! empty( $column_settings ) ) {
		end( $column_settings );
		$column_settings[key($column_settings)]['position'] = 'last';
		reset( $column_settings );
		$column_settings[key($column_settings)]['position'] = 'first';
	}

	$headers = array();

	foreach ( $column_settings as $column_key => $column_setting) {
		if ( ! $order_discount && isset( $column_setting['only_discounted'] ) ) {
			continue;
		}
		
		// vat split column
		if ( 'vat' === $column_setting['type'] && isset( $column_setting['split'] ) && ! empty( $taxes ) ) {			
			foreach ( $taxes as $tax ) {
				$title      = $tax['label'] . ' (' . $tax['rate'] . ')';				
				$new_column = array(
					'split' => '1',
					'title' => apply_filters( 'wpo_wcpdf_vat_split_column_title', $title, $tax ),
					'class' => 'vat-split',
					'type'  => 'vat',
				);
				$new_column_key           = $column_key . '_' . $tax['rate_id'];
				$headers[$new_column_key] = $column_setting + $new_column + WPO_WCPDF_Templates()->main->get_order_details_header( $new_column, $document );
			}
		
		// default column
		} else {
			$headers[$column_key] = $column_setting + WPO_WCPDF_Templates()->main->get_order_details_header( $column_setting, $document );
		}
	}

	return apply_filters( 'wpo_wcpdf_templates_table_headers', $headers, $document->get_type(), $document );
}

function wpo_wcpdf_templates_get_table_body( $document ) {
	$column_settings = WPO_WCPDF_Templates()->settings->get_settings( $document->get_type(), 'columns', $document );
	$order_discount  = $document->get_order_discount( 'total', 'incl' );
	$taxes           = $document->get_order_taxes();

	// mark first and last column
	if ( ! empty( $column_settings ) ) {
		end( $column_settings );
		$column_settings[key($column_settings)]['position'] = 'last';
		reset( $column_settings );
		$column_settings[key($column_settings)]['position'] = 'first';
	}
	
	$body  = array();
	$items = $document->get_order_items();
	if ( sizeof( $items ) > 0 ) {
		foreach ( $column_settings as $column_key => $column_setting ) {
			$line_number = 1;
			foreach ( $items as $item_id => $item ) {
				if ( ! $order_discount && isset( $column_setting['only_discounted'] ) ) {
					continue;
				}

				$column_setting['line_number'] = $line_number;
				
				// vat split column	
				if ( 'vat' === $column_setting['type'] && isset( $column_setting['split'] ) && ! empty( $taxes ) ) {
					$item_taxes = $item['item']->get_taxes();
					
					$item_subotal_taxes           = isset( $item_taxes['subtotal'] ) ? $item_taxes['subtotal'] : array();
					$filtered_item_subtotal_taxes = array_filter( $item_subotal_taxes );
					$multiple                     = ! empty( $filtered_item_subtotal_taxes ) && count( $filtered_item_subtotal_taxes ) > 1;
					
					// loop order taxes to add item split ones
					foreach ( $taxes as $tax ) {
						// add split tax
						$split = array();
						foreach ( $item_taxes as $item_tax_type => $item_tax_values ) {
							$value                   = ! empty( $item_tax_values[ $tax['rate_id'] ] ) ? $item_tax_values[ $tax['rate_id'] ] : 0;
							$split[ $item_tax_type ] = floatval( $value );
						}
						
						// the item has multiple taxes?
						$split['multiple']     = $multiple;
						$split['tax_rate']     = $tax['rate'];
						
						// add split discount
						$split['discount']     = floatval( $item['item']->get_subtotal() - $item['item']->get_total() );
						$split['discount_tax'] = floatval( $item['item']['line_subtotal_tax'] - $item['item']['line_tax'] );
						
						// add column
						$new_column = array(
							'type'          => $column_setting['type'],
							'split'         => $split,
							'dash_for_zero' => isset( $column_setting['dash_for_zero'] ),
							'label'         => $column_setting['label'],
							'price_type'    => $column_setting['price_type'],
							'discount'      => $column_setting['discount'],
						);
						
						$new_column_key                  = $column_key . '_' . $tax['rate_id'];
						$body[$item_id][$new_column_key] = $new_column + WPO_WCPDF_Templates()->main->get_order_details_data( $new_column, $item, $document );
					}
				
				// default column
				} else {
					$body[$item_id][$column_key] = $column_setting + WPO_WCPDF_Templates()->main->get_order_details_data( $column_setting, $item, $document );
				}
				
				$line_number++;
			}
		}
	}

	return apply_filters( 'wpo_wcpdf_templates_table_body', $body, $document->get_type(), $document );
}

function wpo_wcpdf_templates_get_totals( $document ) {
	$total_settings = WPO_WCPDF_Templates()->settings->get_settings( $document->get_type(), 'totals', $document );
	$totals_data = WPO_WCPDF_Templates()->main->get_totals_table_data( $total_settings, $document );

	return apply_filters( 'wpo_wcpdf_templates_totals', $totals_data, $document->get_type(), $document );
}

function wpo_wcpdf_templates_get_footer_settings( $document, $default_height = '5cm' ) {
	$footer_height = str_replace( ' ', '', WPO_WCPDF_Templates()->settings->get_footer_height() );
	if ( empty( $footer_height ) ) {
		$footer_height = $default_height;
	}

	// calculate bottom page margin
	$page_bottom = floatval($footer_height);

	// convert to cm
	if (strpos($footer_height,'in') !== false) {
		$page_bottom = $page_bottom * 2.54;
	} elseif (strpos($footer_height,'mm') !== false) {
		$page_bottom = $page_bottom / 10;
	}

	// set limit cap in cm
	$limit_cap = apply_filters( 'wpo_wcpdf_templates_footer_height_limit', 10 );
	if( $page_bottom > $limit_cap ) {
		$page_bottom = $limit_cap;
	}

	// footer height
	$footer_height = $page_bottom.'cm';

	// add 1 + cm
	$page_bottom   = ($page_bottom + 1).'cm';

	return compact( 'footer_height', 'page_bottom' );
}
