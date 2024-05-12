(function( $, window, document, wp, params, undefined ) {
	"use strict";

    const BulkVariationManager =  ( () => {

        let initialized  = false,
            filteredOnly = false;

        const initialize = () => {
            $( '#variation_filters').hide();

            $( document ).ajaxComplete( onAjaxComplete );
        };

        const prepareInterface = () => {
            if ( initialized ) {
                return false;
            }

            // change the name of the original bulk actions
            // so that they don't send the default AJAX requests
            $('#field_to_edit option').each( ( index, option ) => {
                if ( ! $( option ).data('global') ) {
                    $( option ).attr( 'data-action', $( option ).val() ).val( $( option ).val() + '_selected' )
                }
            } );

            // add a chckbox after the bulk actions select box
            $( 'a.button.bulk_edit.do_variation_action' ).after(
                $( '<label>', { class: 'bulk-actions-filtered-only', disabled: '' } ).append(
                    $( '<input>', {
                        id: 'wcbvp_bulk_actions_filtered_only',
                        name: 'wcbvp_bulk_actions_filtered_only',
                        type: 'checkbox'
                    } ),
                    $( '<span>' ).text( params.bulk_actions_filtered_only )
                )
            );

            $('#field_to_edit').before(
                $( '<div>', {class: 'wcbvp-bulk-actions-group' } )
            )

            $( '.wcbvp-bulk-actions-group' ).append(
                $( '#field_to_edit, a.button.bulk_edit.do_variation_action, label.bulk-actions-filtered-only' ).detach()
            ).parent().after(
                $( '#variation_filters').show().detach()
            );

            // store the 'Delete all variations' label
            // so that we can toggle between 'all' and 'filtered'
            const $deleteOption = $('#field_to_edit option[data-action="delete_all"]');
            $deleteOption.attr('data-label', $deleteOption.text() );

            // move the filter panel right before the list of variations
            ;

            bindEvents();

            initialized = true;
        };

        const bindEvents = () => {  
            unbindDefaultEvents();
            
            $( document )
                .on( 'change', '#variation_filters select.wcbvp-attribute-filters', { newFilters: true }, updateFilters )
                .on( 'click', '#variation_filters a.update_filters', { newFilters: true }, updateFilters )
                .on( 'click', '#variation_filters a.reset_filters', resetFilters )
                .on( 'click', '#variation_filters a.add_filter', onAddFilterItem )
                .on( 'keypress', '#variation_filters :input', onFilterInputEnter )
                // .on( 'change input', '#variation_filters .wcbvp-filter-item :input', onFilterChange )
                .on( 'change', '.variations-pagenav .page-selector', { isRelative: false }, setPage )
                .on( 'click', '.variations-pagenav .first-page', { page: 1 }, setPage )
                .on( 'click', '.variations-pagenav .prev-page', { page: -1, isRelative: true }, setPage )
                .on( 'click', '.variations-pagenav .next-page', { page: 1, isRelative: true }, setPage )
                .on( 'click', '.variations-pagenav .last-page', { page: Infinity }, setPage )
                .on( 'click', '#variable_product_options button.save-variation-changes', saveChanges )
                .on( 'change', 'input#wcbvp_bulk_actions_filtered_only', toggleFilterOnly )
                .on( 'click', 'a.button.additional-filters', () => $( '.wcbvp-additional-filters' ).slideToggle() )
                .on( 'click', 'button.wcbvp-remove-filter-item', onRemoveFilterItem )
                .on( 'change', '#wcbvp_additional_filters select.filter-meta-key', onMetaChange )
                .on( 'change', '#wcbvp_additional_filters select.filter-compare', onCompareChange );

            let variationActions = [
                'delete_all',
                'toggle_enabled',
                'variable_regular_price',
                'variable_regular_price_increase',
                'variable_regular_price_decrease',
                'variable_sale_price',
                'variable_sale_price_increase',
                'variable_sale_price_decrease',
                'variable_sale_schedule',
                'variable_stock',
                'variable_low_stock_amount',
                'variable_download_limit',
                'variable_download_expiry',
                'variable_length',
                'variable_width',
                'variable_height',
                'variable_weight',
                'variable_set_thumbnail',
                'variable_remove_thumbnail',
                'toggle_downloadable',
                'toggle_virtual',
                'toggle_manage_stock',
                'variable_stock_status_instock',
                'variable_stock_status_outofstock',
                'variable_stock_status_discontinued',
                'variable_stock_status_onbackorder'
            ];

            const filteredVariationActions = $( document ).triggerHandler( 'wcbvp.getVariationActions', [ variationActions ] );

            if ( undefined !== filteredVariationActions ) {
                variationActions = filteredVariationActions;
            }

            for( const action of variationActions ) {
                $( 'select.variation_actions' )
                    .off( action + '_selected_ajax_data' )
                    .on( action + '_selected_ajax_data', { action }, doVariationActions );
            }
        };

        const unbindDefaultEvents = () => {
            $( document.body )
                .off( 'change', '.variations-pagenav .page-selector' )
                .off( 'click', '.variations-pagenav .first-page' )
                .off( 'click', '.variations-pagenav .prev-page' )
                .off( 'click', '.variations-pagenav .next-page' )
                .off( 'click', '.variations-pagenav .last-page' );

            $( '#variable_product_options' )
				.off( 'click', 'button.save-variation-changes' );
        };

        const block = () => {
            $( '#woocommerce-product-data' ).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        };

        const unblock = () => {
            $( '#woocommerce-product-data' ).unblock();
        };

        const resetFilters = () => {
            $( '#variable_product_options .variations-pagenav' ).show();
            $( '#variation_filters select.wcbvp-attribute-filters' ).val('');
            $( '#variation_filters .wcbvp-filter-item' ).remove();
            $( 'label.bulk-actions-filtered-only' ).attr( 'disabled', '' );
            $( 'input#wcbvp_bulk_actions_filtered_only' ).prop( 'checked', false ).trigger('change');
            filterVariations( { data: { newFilters: true } } );
        };

        const doVariationActions = ( e ) => {
            const action       = e.data.action.replace( /toggle_|variable_|_selected|/gi, '' );
            let   data         = e.data;

            data.per_page   = -1;
            data.page       = 1;
            data.product_id = woocommerce_admin_meta_boxes_variations.post_id;
            data.filters    = {};

            if ( filteredOnly ) {
                data.filters = getFilters();
            }

            let value;

            switch ( action ) {
                case 'delete_all':
					if ( window.confirm( params.warning_delete_filtered_variations ) ) {
						if ( window.confirm( woocommerce_admin_meta_boxes_variations.i18n_last_warning ) ) {
							data.allowed = true;
						}
					}
                    break;

                case 'regular_price':
                case 'sale_price':
                case 'stock':
                case 'low_stock_amount':
                case 'download_limit':
                case 'download_expiry':
                case 'length':
                case 'width':
                case 'height':
                case 'weight':
                    value = window.prompt( woocommerce_admin_meta_boxes_variations.i18n_enter_a_value );

					if ( value != null ) {
						data.value  = value;
					} else {
                        data = null;
					}
					break;

                case 'regular_price_increase':
                case 'regular_price_decrease':
                case 'sale_price_increase':
                case 'sale_price_decrease':
                    value = window.prompt( woocommerce_admin_meta_boxes_variations.i18n_enter_a_value_fixed_or_percent );

					if ( value != null ) {
						if ( value.indexOf( '%' ) >= 0 ) {
							data.value = accounting.unformat( value.replace( /\%/, '' ), woocommerce_admin.mon_decimal_point ) + '%';
						} else {
							data.value = accounting.unformat( value, woocommerce_admin.mon_decimal_point );
						}
					} else {
                        data = null;
					}

                    break;

                case 'sale_schedule':
					data.date_from = window.prompt( woocommerce_admin_meta_boxes_variations.i18n_scheduled_sale_start );
					data.date_to   = window.prompt( woocommerce_admin_meta_boxes_variations.i18n_scheduled_sale_end );

					if ( null === data.date_from ) {
						data.date_from = false;
					}

					if ( null === data.date_to ) {
						data.date_to = false;
					}

					if ( false === data.date_to && false === data.date_from ) {
                        data = null;
					}

					break;

                case 'remove_thumbnail':
					if ( window.confirm( params.warning_remove_thumbnails ) ) {
						if ( window.confirm( woocommerce_admin_meta_boxes_variations.i18n_last_warning ) ) {
							data.allowed = true;
						} else {
                            data = null;
                        }
					} else {
                        data = null;
                    }
                    
                    break;

                case 'set_thumbnail':
                    data = null;

                    const frame = wp.media({
                        frame: 'select',
                        multiple: false,
                        title: params.wp_media_title,
                        library: { 
                           type: 'image'
                        },
                        button: { 
                           text : params.wp_media_button
                        }                     
                    });

                    frame.on( 'select', () => {
                        block();

                        const attachment = frame.state().get('selection').first().toJSON();
                        const postData = e.data;

                        postData.thumbnail_id = attachment.id;

                        $.ajax({
                            url: woocommerce_admin_meta_boxes_variations.ajax_url,
                            data: {
                                action:       'woocommerce_bulk_edit_variations',
                                security:     woocommerce_admin_meta_boxes_variations.bulk_edit_variations_nonce,
                                product_id:   woocommerce_admin_meta_boxes_variations.post_id,
                                product_type: $( '#product-type' ).val(),
                                bulk_action:  'variable_set_thumbnail_selected',
                                data:         postData
                            },
                            type: 'POST',
                            success: function() {
                                filterVariations();
                            }
                        });
                    });

                    frame.open();

                    break;

                default:
                    const filteredData = $( document ).triggerHandler( 'wcbvp.doVariationActions', [ data ] );

                    if ( undefined !== filteredData ) {
                        data = filteredData;
                    }
            }

            return data;
        };

        const getFilters = () => {
            const filters = {
                attributes: {},
                meta: []
            };

            $( '#variation_filters select.wcbvp-attribute-filters' ).toArray().map( (select) => {
                if ( select.value ) {
                    filters.attributes[ select.dataset.attribute_name ] = select.value
                }
            } )

            $( '#variation_filters .wcbvp-filter-item:not(.editing)' ).each( ( index, item ) => {
                const $item    = $( item ),
                      $key     = $( '.filter-meta-key', $item ),
                      $compare = $( '.filter-compare option:selected', $item ),
                      value1   = $( 'input.wcbvp-filter-value', $item ).val(),
                      value2   = $( 'input.wcbvp-filter-second-value', $item ).val();

                const meta = {
                    key:     $key.val(),
                    compare: $compare.val()
                };

                if ( 0 < $compare.data('values') ) {
                    meta.value = value1;
                }

                if ( 1 < $compare.data('values') ) {
                    meta.value2 = value2;
                }

                if ( '_stock_status' === meta.key ) {
                    meta.value = $( 'select.stock-status-options', $item ).val();
                }

                filters.meta.push( meta );
            });

            return filters;
        };
            
        const filterVariations = ( e ) => {
            const newFilters = e && e.data && e.data.newFilters;

            const page     = newFilters ? 1 : wrapper().data( 'page' ),
                  per_page = woocommerce_admin_meta_boxes_variations.variations_per_page;

            block();

            const filters    = getFilters(),
                  hasFilters = Object.values( filters ).map( v => Object.values( v ).length ).filter( v => v ).length > 0;

            $( 'a.reset_filters' ).toggleClass( 'hidden', ! hasFilters );
            $( 'label.bulk-actions-filtered-only' ).attr( 'disabled', hasFilters ? null : '' );
            $( 'input#wcbvp_bulk_actions_filtered_only' ).prop( 'checked', filteredOnly && hasFilters ).trigger('change');

            $.ajax({
                url: woocommerce_admin_meta_boxes_variations.ajax_url,
                data: {
                    action:     'wcbvp_load_variations',
                    security:   woocommerce_admin_meta_boxes_variations.load_variations_nonce,
                    product_id: woocommerce_admin_meta_boxes_variations.post_id,
                    filters,
                    page,
                    per_page
                },
                type: 'POST',
                success: ( response ) => {
                    wrapper().empty().append( response.data.html );

                    updateVariationsCount( response.data.count, response.data.total );
                    updatePage();

                    $( '#woocommerce-product-data' ).trigger( 'woocommerce_variations_loaded' );
                },
                complete: () => {
                    unblock();
                }
            });
        };

        const onAjaxComplete = ( event, request, settings ) => {
            if ( ! settings || ! settings.data ) {
                return false;
            }

            const params = decodeParams(
                Object.fromEntries(
                    settings.data
                        .replace( /%5B/gi, '[' )
                        .replace( /%5D/gi, ']' )
                        .split( '&' )
                        .map( a => a.split( '=' ) )
                )
            );

            if ( params ) {
                if ( 'woocommerce_bulk_edit_variations' === params.action ) {
                    const data = params.data;

                    if ( data && 'delete_all' === data.action && 'true' === data.allowed ) {
                        resetFilters()
                    }
                }

                if ( ! initialized && 'woocommerce_load_variations' === params.action ) {
                    prepareInterface();
                }
            }

        }

        const decodeParams = ( params ) => {
            const keyPattern = /^(\w+)\[(\w+)\](.*)$/;

            return Object.keys( params ).reduce( ( result, key ) => {
                let match = key.match( keyPattern );

                if ( match && match.length >= 3 ) {
                    let [ newKey, nextKey, rest = '' ] = match.slice( 1 );

                    result[ newKey ] = Object.assign(
                        {},
                        result[ newKey ],
                        decodeParams({ [ nextKey + rest ]: params[ key ] })
                    );
                } else {
                    result[ key ] = params[ key ];
                }

                return result;
            }, {});
        }

        const toggleFilterOnly = ( e ) => {
            const $checkbox = $( e.currentTarget ),
                  $deleteOption = $('#field_to_edit option[data-action="delete_all"]');

            filteredOnly = $( e.currentTarget ).prop( 'checked' );

            $deleteOption.text( $checkbox.prop( 'checked' ) ? params.delete_filtered_variations : $deleteOption.attr('data-label') );
        };

        const updateFilters = ( e ) => {
            filteredOnly = true;

            if ( 0 === $( '.wcbvp-filter-item' ).length ) {
                $( '#variation_filters' ).removeClass( 'changed' );
            }

            filterVariations( { data: { newFilters: true } } );
        }

        const onFilterInputEnter = ( e ) => {
            // if the user presses ENTER on any input...
            if (e.keyCode == 13) {
                // ...update the current filter...
                updateFilters( e );

                // ...and prevent the submission of the outer post form
                return false;
            }        
        };

        const onAddFilterItem = ( e ) => {
            const $toolbar = $( e.currentTarget ).closest( '.wcbvp-filter-items-toolbar' ),
                  template = wp.template( 'wcbvp-filter-item' ),
                  $item    = $( template() ).hide();

            $toolbar.before(
                $item
            );

            $( '.wcbvp-filter-value, .wcbvp-filter-second-value, .stock-status-options', $item ).hide();

            $item.slideToggle(250);

            $( '.filter-meta-key', $item ).trigger('change');
            $( '#variation_filters' ).addClass( 'changed' );
        };

        const onRemoveFilterItem = ( e ) => {
            const $item = $( e.currentTarget ).closest( '.wcbvp-filter-item' );
            $item.slideToggle( 250, () => {
                $item.remove();
                $( '#variation_filters' ).addClass( 'changed' );
            });
        };

        const onMetaChange = ( e ) => {
            const $key       = $( e.currentTarget ),
                  $keyOption = $( 'option:selected', $key ),
                  $item      = $key.closest( '.wcbvp-filter-item' ),
                  $compare   = $( 'select.filter-compare', $item ),
                  $options   = $( 'option', $compare ).filter( ( index, option ) => ! $( option ).val() || $( option ).data('type').split(',').includes( $keyOption.data( 'type' ) ) );

            $( 'option', $compare ).prop( 'hidden', true );
            $options.prop( 'hidden', false );
            $compare.prop( 'disabled', 0 === $options.length );

            $( 'select.stock-status-options', $item ).addClass('hidden').next( '.select2' ).addClass('hidden');

            if ( '_stock_status' === $key.val() && $().selectWoo ) {
				const wcbvp_stock_status = () => {
					$( 'select.stock-status-options', $item ).selectWoo( {
						placeholder: params.select_stock_status_placeholder,
                        multiple: true,
                        width: 'auto',
						allowClear: true,
                        closeOnSelect: false,
					} );
				};
                $( 'select.stock-status-options', $item ).removeClass('hidden').next( '.select2' ).removeClass('hidden');
                wcbvp_stock_status();
            }

            if ( 0 === $options.length ) {
                return false;
            }

            $compare.get(0).selectedIndex = $options.get(0).index;
            $compare.trigger('change');
        };

        const onCompareChange = ( e ) => {
            const $compare    = $( e.currentTarget ),
                  $item       = $compare.closest( '.wcbvp-filter-item' ),
                  $key        = $( '.filter-meta-key', $item ),
                  $keyOption  = $( 'option:selected', $key ),
                  type        = $keyOption.data('type'),
                  placeholder = $keyOption.data('placeholder'),
                  values      = $( 'option:checked', $compare ).data('values');

            $( '.wcbvp-filter-value', $item )
                .attr( { type, placeholder } )
                .toggle( values > 0 );
            $( '.wcbvp-filter-second-value', $item )
                .attr( { type, placeholder } )
                .toggle( values > 1 );
        };

        // const bulkAction = ( e ) => {

        //     $.ajax({
        //         url: woocommerce_admin_meta_boxes_variations.ajax_url,
        //         data: {
        //             action:      'wcbvp_do_action',
        //             bulk_action: $( '#field_to_edit' ).val(),
        //             security:    woocommerce_admin_meta_boxes_variations.bulk_edit_variations_nonce,
        //             product_id:  woocommerce_admin_meta_boxes_variations.post_id,
        //             attributes:  attributes
        //         },
        //         type: 'POST',
        //         success: function( response ) {
        //             wrapper().empty().append( response.data.html );

        //             if ( newFilters ) {
        //                 updateVariationsCount( response.data.count, response.data.total );
        //             }
        //             updatePage();
                    
        //             $( '#woocommerce-product-data' ).trigger( 'woocommerce_variations_loaded' );
        //             unblock();
        //         }
        //     });
        // };

        const setPage = ( e ) => {
            updatePage( e.data );

            filterVariations();

            return false;
        };

        const updatePage = ( data ) => {
            let page = Number( $( '.variations-pagenav .page-selector' ).val() );

            const pages   = wrapper().data( 'total_pages' ),
                  current = wrapper().data( 'page' );

            if ( data ) {
                const pageValue  = data.page || page,
                      isRelative = data.isRelative;

                page = isRelative ? Math.min( pages, Math.max( 1, current + pageValue ) ) : Math.min( pageValue, pages );

                $( '.variations-pagenav .page-selector' ).val( page );
            }

            wrapper().data( 'page', page ).attr( 'data-page', page );
            
            $( '.variations-pagenav .pagination-links' ).toggleClass( 'hidden', pages < 2 );
            $( '.variations-pagenav .page-selector' ).toggleClass( 'disabled', pages < 2 )
            $( '.variations-pagenav a.first-page, .variations-pagenav a.prev-page' ).toggleClass( 'disabled', 1 === page );
            $( '.variations-pagenav a.next-page, .variations-pagenav a.last-page' ).toggleClass( 'disabled', page === pages );
        };

        const wrapper = () => {
            return $( '#variable_product_options' ).find( '.woocommerce_variations' );
        };

        const updateVariationsCount = ( count, total ) => {
            const displaying_num = $( '.variations-pagenav .displaying-num' ),
                  pages          = Math.ceil( count / Number( woocommerce_admin_meta_boxes_variations.variations_per_page ) );

            $( '.variations-pagenav .total-pages' ).text( pages );
            $( '.variations-pagenav .pagination-links' ).toggleClass( 'hidden', pages < 2 );

            // Set the new total of variations
            wrapper().data( 'count', count ).attr( 'data-count', count );
            wrapper().data( 'total', total ).attr( 'data-total', count );
            wrapper().data( 'total_pages', pages ).attr( 'data-total', count );

            const currentPage = $( '.variations-pagenav .page-selector' ).val() || 1;

            $( '.variations-pagenav .page-selector' ).empty().val('');

            if ( pages > 1 ) {
                $( '.variations-pagenav .page-selector' ).each( ( index, select ) => {
                    $( select ).append( Array( pages ).fill( 1 ).map( ( i, j ) => `<option value="${j+1}">${j+1}</option>` ) )
                    select.value = currentPage
                })
            }

            const countLabel = `<strong>${count}</strong>`;

            let totalLabel = woocommerce_admin_meta_boxes_variations.i18n_variation_count_single.replace( '%qty%', `<strong>${total}</strong>` );

            if ( total > 1 ) {
                totalLabel = woocommerce_admin_meta_boxes_variations.i18n_variation_count_plural.replace( '%qty%', `<strong>${total}</strong>` );
            }

            if ( count === total ) {
                displaying_num.html( totalLabel )
            } else {
                const filterResultLabel = count === 1 ? params.filter_singular_result : params.filter_results;
                displaying_num.html( filterResultLabel.replace( '%count%', countLabel ).replace( '%totals%', totalLabel ) )
            }

            return count;
        };

        const getVariationFields = ( fields ) => {
			var data = $( ':input', fields ).serializeJSON();

			$( '.variations-defaults select' ).each( function( index, element ) {
				var select = $( element );
				data[ select.attr( 'name' ) ] = select.val();
			});

			return data;
        };

        const saveChanges = () => {
            const wrapper     = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
                  need_update = $( '.variation-needs-update', wrapper );

            let data = {};

            // Save only with products need update.
            if ( 0 < need_update.length ) {
                block();

                data                 = getVariationFields( need_update );
                data.action          = 'woocommerce_save_variations';
                data.security        = woocommerce_admin_meta_boxes_variations.save_variations_nonce;
                data.product_id      = woocommerce_admin_meta_boxes_variations.post_id;
                data['product-type'] = $( '#product-type' ).val();

                $.ajax({
                    url: woocommerce_admin_meta_boxes_variations.ajax_url,
                    data: data,
                    type: 'POST',
                    success: function( response ) {
                        // Allow change page, delete and add new variations
                        need_update.removeClass( 'variation-needs-update' );
                        $( 'button.cancel-variation-changes, button.save-variation-changes' ).attr( 'disabled', 'disabled' );

                        $( '#woocommerce-product-data' ).trigger( 'woocommerce_variations_saved' );

                        filterVariations();
                    }
                });
            }
        };

        // Public API.
        return {
            initialize,
            block,
            unblock,
            setPage,
            resetFilters
        };

    })();

    window.WCBulkVariationManager = BulkVariationManager;

	$( () => {

        BulkVariationManager.initialize();
	})

})( jQuery, window, document, wp, wcbvp_manager_translations );
