( function( $, window, document, params, undefined ) {
	"use strict";

	let BulkVariations = ( () => {

		let $currentInstance = null;

		const initialize = () => {
			bindEvents();
			applyStyling();
			reset();
		};

		const bindEvents = () => {
			$( document.body )
				.on( 'click', onGlobalClick )
				.on( 'submit', '.wc-bulk-variations-table-wrapper form :input', e => e.preventDefault() )
				// .on( 'click', '.wc-bulk-variations-table-wrapper *', setCurrentInstance )
				.on( 'click', '.wc-bulk-variations-table-wrapper form button.single_add_to_cart_button', onAddToCart )
				.on( 'focus', '.wcbvp-form-variation :input', onInputFocus )
				// .on( 'blur', 'select.wcbvp-additional-attribute', onSelectBlur )
				.on( 'change', '.wcbvp-cell select.wcbvp-additional-attribute', onAttributeSelect )
				// .on( 'change', '.wcbvp-header select.wcbvp-additional-attribute', onHeaderAttributeSelect )
				.on( 'input', '.wcbvp-quantity-field input', onInputChange )
				// .on( 'click', '.wcbvp-variation-pool-item .action-delete', onPoolItemDelete )
				// .on( 'click', '.wcbvp-variation-pool-item, .wcbvp-variation-pool-item>*:not(action-delete)', onVariationEdit )
				.on( 'click', '.wc-bulk-variations-table .wcbvp-header img, .wc-bulk-variations-table .wcbvp-col-image img', onSelectCurrentImage )
				.on( 'click', '.wc-bulk-variations-table .woocommerce-product-gallery__image a', onOpenPhotoswipe )
				.on( 'quick_view_pro:added_to_cart', clear )
				.on( 'quick_view_pro:open_complete', applyDefaults )
				.on( 'updated_checkout', clear );

			addResizeObservers();
		};

		const applyStyling = () => {
			// const baseGap = 2 * Math.round( $( `.wc-bulk-variations-table` ).width() / 100 );
			// $( '.wc-bulk-variations-table' ).css( { gap: `${baseGap}px ${baseGap + 4}px` } );

			setSelectWidth();
		};

		const setSelectWidth = () => {
			let minWidth = 0;

			$( `.wc-bulk-variations-table` ).each( ( i, t ) => {
				$( t ).find( '.wcbvp-cell' ).first().find('select.wcbvp-additional-attribute').each( ( j, s ) => {
					const width = getRealSelectSize( $( s ) );

					minWidth = Math.max( minWidth, width );
				});
			});
			$( 'select.wcbvp-additional-attribute, .wcbvp-quantity-input' ).css( 'min-width', minWidth );
		};

		const applyDefaults = () => {
			$( 'input.wcbvp-quantity' ).trigger( 'input' );

			$( '.wcbvp-cell' ).each( ( cellIndex, cell ) => {
				updateAvailableOptions( $( cell ) );
			})
		};

		const setCurrentInstance = ( e ) => {
			const $instance = getInstance( e.target );
			e.stopPropagation();

			if ( $instance.length ) {
				if ( isFastPool() && $( '.wc-bulk-variations-table', $instance ).attr( 'id' ) !== $( '.wc-bulk-variations-table', $currentInstance ).attr( 'id' ) ) {
					reset();
				}

				$currentInstance = $instance;
			}
		}

		const getCurrentInstance = () => {
			return $currentInstance || $( '.wc-bulk-variations-table-wrapper' ).first();
		}

		const getInstance = ( $element ) => {
			let $instance = $element.closest( '.wc-bulk-variations-table-wrapper' );
			$instance = $instance || getCurrentInstance();

			return $instance;
		}

		const isFastPool = ( $instance = null ) => {
			$instance = $instance || getCurrentInstance()
			return $( 'form.wcbvp-fast-pool', $instance ).length > 0
		}

		const wcFormatPrice = ( n ) => {
			const curOptions   = params.currency_options,
				  format       = curOptions.price_format,
				  decimals     = curOptions.decimals || 2,
				  d_separator  = curOptions.d_separator || '.',
				  t_separator  = curOptions.t_separator || ',',
				  price_markup = curOptions.price_markup || '%s';

			const parts = n.toFixed(decimals).toString().split( '.' );
			parts[0] = parts[0].replace( /\B(?=(\d{3})+(?!\d))/g, t_separator );

			// replace %1$s with the currency symbol and %2$s with the price
			// according to the WooCommerce price format settings
			const formattedPrice = format.replace( /\%(\d)\$s/g, ( m, g ) => ( { 1: curOptions.currency_symbol, 2: parts.join( d_separator ) }[ g ] ) );

			return price_markup.replace( '%s', formattedPrice );
		};

		const recalculate = ( $instances ) => {
			$instances = $instances || $( '.wc-bulk-variations-table-wrapper' );

			$instances.each( ( index, instance ) => {
				const $instance = $( instance );
				const $pool = $( '.wcbvp-variation-pool', $instance );

				$( '.wcbvp-cell', $instance )

				const result = $pool.find( '.wcbvp-quantity' ).toArray().reduce(
					( r, e ) => {
						return {
							quantity: r.quantity + Number( e.value ),
							total: r.total + Number( e.dataset.price ) * Number( e.value )
						};
					},
					{ quantity: 0, total: 0 }
				);

                $( '.wcbvp_total_items', $instance ).html( result.quantity );
                $( '.wcbvp_total_price', $instance ).html( wcFormatPrice( result.total ) );
				$( 'label[role="status"]', $instance ).html( `${result.quantity} ${1 !== result.quantity  ? params.i18n_item_plural : params.i18n_item_singular }. ${params.i18n_your_total.replace( '%s', wcFormatPrice( result.total ) )}` ).trigger('focus');

                $( '.wcbvp_total_quantity', $instance ).html( result.quantity );
				$( '.single_add_to_cart_button', $instance )
					.toggleClass( 'disabled wc-variation-selection-needed', result.quantity === 0 )
					.prop( 'disabled', result.quantity === 0 );

			});

			$( document ).trigger( 'wc_bulk_variations_table_recalculate', [ $instances ] );
	    };

		const addResizeObservers = () => {
			const resizeObserver = new ResizeObserver( entries => {
				for ( let entry of entries ) {
					if ( entry.contentBoxSize[0] ) {
						if ( entry.contentBoxSize[0].inlineSize === entry.contentRect.width ) {
							// the width of the grid changed => adjust the styling!
							applyStyling();
						}
					}
				}
			} );

			$( `.wc-bulk-variations-table` ).each( ( index, table ) => {
				resizeObserver.observe( table );
			} );
		};

		const onAddToCart = ( e ) => {
			const $this = $( e.currentTarget );

			if ( $this.closest( '.wc-quick-view-modal' ).length || window.wc_fast_cart_params && -1 !== wc_fast_cart_params.selectors.cartBtn.indexOf( '.wc-bulk-variations-table-wrapper form button.single_add_to_cart_button' ) ) {
				// submit the form only if it is not inside a Quick View Pro modal dialog and if Fast Cart is not active
				return true;
			}

			$this.submit();
		};

		const addItem = ( $input ) => {
			const $instance   = $input.closest( '.wc-bulk-variations-table-wrapper' ),
			      $pool       = $( '.wcbvp-variation-pool', $instance ),
				  $cell       = $input.closest( '.wcbvp-cell' );

			const selection = getSelection( $cell );

			if ( ! selection || ! selection.variation ) {
				return true;
			}

			const variationID = selection.variation.variation_id;
			let price         = selection.variation.display_price;

			const data = $( document ).triggerHandler( 'wbvCell.update', [ {}, selection.variation, $instance ] );

			if ( undefined !== data && undefined !== data.price ) {
				price = data.price
			}

			let qty   = Number( $cell.find( '.wcbvp-quantity-field input' ).val() ),
				qItem = $pool.find( `div[data-product_id="${variationID}"]` ),
				qInput;

			if ( 0 === qty ) {
				qItem.remove();
			} else {
				if ( ! qItem.length ) {
					if ( isFastPool() ) {
						$( '.wcbvp-variation-pool-item' ).removeClass( 'selected' );
						$( '.wcbvp-form-variation' ).removeClass( 'selected' );
					}

					qItem = $( '<div>', {
							id: `pool_item_${variationID}`,
							class: 'wcbvp-variation-pool-item'
					} ).attr( 'data-product_id', variationID ).addClass( 'selected' );

					for ( const [ attribute, value ] of Object.entries( selection.attributes.object ) ) {
						qItem.attr( `data-${attribute}`, value.name );
					}

					qInput = $( '<input>', { type: 'hidden', name: `quantity[${variationID}]`, class: 'wcbvp-quantity' } );

					$pool.append( qItem );
				} else {
					qInput = qItem.find( 'input' );
				}

				qInput.val( qty ).attr( 'data-price', price );
				qItem.empty().append(
					qInput,
					isFastPool() ? $( '<span>', { class: 'label', title: `${qty}x ${selection.attributes.label}`} ).html( `${qty}&times; ${selection.attributes.label}` ) : null,
					isFastPool() ? $( '<span>', { class: 'action-delete'} ) : null
				);

				// $( '.wcbvp-form-variation', $cell ).addClass( 'selected' );
			}

			// if ( $cell.closest( 'form.wcbvp-cart' ).hasClass( 'wcbvp-compact-grid-mode' ) ) {
			// 	qItem.addClass( 'layer-selected' )
			// }

			recalculate();

		};

		const reset = ( $instances = null, forceReset = false ) => {
			$instances = $instances || getCurrentInstance();

			$instances.each( ( instanceIndex, instance ) => {

				const $instance = $( instance );

				if ( ! isFastPool() && ! forceReset ) {
					return true;
				}

				$( '.wcbvp-cell .wcbvp-quantity-field input[type="number"]', $instance ).each( ( index, el ) => {
					// it is not redundant to perform cell-wide operations here
					// since there is only one quantity input per cell
					// so only one iteration per cell will occur
					const $this            = $( el ),
						  $form            = $( 'form.wcbvp-cart', $instance ),
						  $cell            = $this.closest( '.wcbvp-cell' ),
						  isMultiVariation = $cell.data('variation_ids') && $cell.data('variation_ids').toString().split(',').length > 1,
						  isMulticells     = $form.hasClass( 'wcbvp-multivariation-cells' ),
						  isCompactMode    = $form.hasClass( 'wcbvp-compact-grid-mode' );

					if ( forceReset || isMultiVariation || isMulticells ) {
						updateAvailableOptions( $cell );
					}

					$( '.price', $cell ).html( $( '.price', $cell ).attr( 'data-default' ) );

					if ( 0 === $form.length || '0' === $( el ).prop( 'max' ) ) {
						return true;
					}

					$this.prop( 'disabled', isMultiVariation || isMulticells || isCompactMode );

					if ( forceReset || isMulticells || isCompactMode ) {
						const selectedVariation = getSelection( $cell );

						if ( selectedVariation && ! isMultiVariation ) {
							// if Default Quantity or Quantity Manager are installed, get the default value
							const def = Number( selectedVariation.variation.input_value ) || 0;
							$this.prop( 'max', selectedVariation.variation.max_qty );
							$this.val( def );
						} else {
							$this.attr( 'min', '' ).prop( 'max', '' );
							$this.val( 0 );
						}
					}
				});

				$( '.wcbvp-cell select.wcbvp-additional-attribute', $instance ).val('');
				$( '.wcbvp-header select.wcbvp-additional-attribute', $instance ).val('');

				if ( isFastPool() ) {
					$( '.wcbvp-variation-pool-item', $instance ).removeClass( 'selected layer-selected' );
					$( '.wcbvp-form-variation', $instance ).removeClass( 'selected' );
				}

				if ( forceReset ) {
					$( '.wcbvp-variation-pool-item', $instance ).remove()
					applyDefaults();
				}
			});

			recalculate( $instances )
		};

		const clear = ( e ) => {
			let $instances = $( '.wc-bulk-variations-table-wrapper' );

			if ( e ) {
				$instances = $( e.target ).find( '.wc-bulk-variations-table-wrapper' );
			}
			reset( $instances, true );
		};

		const onInputFocus = ( e ) => {
	        const $this     = $( e.currentTarget ),
				  $instance = getInstance( $this ),
				  $cell     = $this.closest( '.wcbvp-cell' ),
				  $form     = $( 'form.wcbvp-cart', $instance );

			$( '.wcbvp-cell', $instance ).not( $cell ).find( ':input' ).each( ( index, element ) => {
				if ( $form.hasClass( 'wcbvp-compact-grid-mode' ) ) {

				} else {
					if ( 'SELECT' === element.tagName ) {
						if ( $form.hasClass( 'wcbvp-multivariation-cells' ) ) {
							$( element ).val('');
						}
					} else {
						if ( $form.hasClass( 'wcbvp-multivariation-cells' ) ) {
							$( element ).val(0);
						}
					}
				}
			});


			if ( isFastPool() ) {
				$( '.wcbvp-variation-pool-item' ).removeClass( 'selected' );
				$( '.wcbvp-form-variation' ).removeClass( 'selected' );
				$( '.wcbvp-form-variation', $cell ).addClass( 'selected' );
			}

			if ( $cell.attr( 'data-variation_ids' ) && 1 === $cell.attr( 'data-variation_ids' ).split(',').length ) {
				$instance.find( `#pool_item_${$cell.attr( 'data-variation_ids' )}` ).addClass('selected');
			}

			if ( $this.hasClass( 'wcbvp-quantity' ) ) {
				$this.trigger( 'select' );
			}
		};

	    const onInputChange = ( e ) => {

	        const $this     = $( e.currentTarget ),
				  $cell     = $this.closest( '.wcbvp-cell' );

			const selection = getSelection( $cell );

			if ( selection ) {
				const min = Number( selection.variation.min_qty ) || 1,
					  max = Number( selection.variation.max_qty ) || -1,
					  val = Number( $this.val() );

				if ( max > -1 && val > max ) {
					e.preventDefault();
					$this.val( max );
				}

				if ( val > 0 && val < min ) {
					e.preventDefault();
					$this.val( min );
				}

				const individual    = $this.data( 'individual' ),
					current_value = $this.val();

				if ( individual && current_value > 0 ) {
					$( '.wcbvp-quantity', getInstance( $cell ) ).not( $this.get(0) ).val( 0 );

					if ( current_value > 1 ) {
						$this.val( 1 );
					}
				}

				addItem( $this, current_value > 0 );
			}

	        recalculate( $this.closest( '.wc-bulk-variations-table-wrapper' ) );
	    };

		const getCellAvailableVariations = ( $cell ) => {
			const $form               = $( 'form.wcbvp-cart', getInstance( $cell ) ),
				  availableVariations = $form.data( 'product_variations' ),
				  variation_ids       = $cell.attr( 'data-variation_ids' );

			if ( ! availableVariations || ! variation_ids ) {
				return [];
			}

			return availableVariations.filter( v => {
				return variation_ids.split(',').map( id => Number( id ) ).includes( v.variation_id );
			} );
		};

		const updateAvailableOptions = ( $element ) => {
			let $cell = $element;

			if ( $element.hasClass( 'wcbvp-additional-attribute' ) ) {
				$cell = $element.closest( '.wcbvp-cell' );
			}

			const $form            = $( 'form.wcbvp-cart', getInstance( $cell ) ),
				  hideOutOfStock   = params.hide_out_of_stock_items,
				  hideDiscontinued = params.hide_discontinued;

			if ( $form ) {
				const cellVariations = getCellAvailableVariations( $cell );

				$( 'select', $cell ).not( $element ).each( ( selectIndex, select ) => {
					const $select = $( select );

					let availableVariations = cellVariations;

					// if `$element` is a selectbox and its value is not null
					// filter the `availableVariations` to those matching the current selection
					if ( $element.hasClass( 'wcbvp-additional-attribute' ) && $element.val() ) {
						availableVariations = availableVariations.filter( v => {
							const selectAttribute = $element.data( 'attribute_name' );
							return v.attributes[ selectAttribute ] && $element.val() === v.attributes[ selectAttribute ];
						} );
					}

					$( 'option', $select ).each( ( optionIndex, option ) => {
						const $option = $( option );

						if ( ! $option.val() ) {
							// the option with an empty `val()` is just the 'Choose label'
							return true;
						}

						// filter the `availableVariations` to the ones matching the current option
						const matchingVariations = availableVariations.filter( v => {
							return v.attributes[ $select.data( 'attribute_name' ) ] && $option.val() === v.attributes[ $select.data( 'attribute_name' ) ];
						})

						const doNotExist = matchingVariations.length === 0;

						const isNotPurchasable = matchingVariations.filter( v => {
							return v.is_purchasable;
						}).length === 0;

						const isNotInStock = matchingVariations.filter( v => {
							return v.is_in_stock;
						}).length === 0 && hideOutOfStock;

						const isNotDiscontinued = matchingVariations.filter( v => {
							return v.is_in_stock && ! v.is_purchasable && v.availability_html.includes( 'discontinued' );
						}).length === 0 || hideDiscontinued;

						// the current option is hidden because either:
						//     - the variation does not exist
						//     – the variation is not purchasable (private, i.e Enabled is unckecked) and is not discontinued
						//     - the variation is out of stock and WC is set to hide the out-of-stock variations
						const hidden = doNotExist || isNotPurchasable && isNotDiscontinued || isNotInStock;

						// hide the option
						$option.prop( 'hidden', hidden );

						// if the selected option was hidden, reset the select box
						if ( $select.val() === $option.val() && hidden ) {
							$select.val('');
						}
					} );
					$select.prop( 'disabled', $( 'option', $select).length === $( 'option:hidden', $select) )
				});
			}
		};

		const onAttributeSelect = ( e ) => {
			const $this     = $( e.currentTarget ),
				  $cell     = $this.closest( '.wcbvp-cell' ),
				  $instance = getInstance( $cell );

			if ( ! resetCellInfo( $cell ) ) {
				updateAvailableOptions( $this );
			};

			recalculate( $instance );
		};

		const resetCellInfo = ( $cell, focus = true ) => {
			const $instance = getInstance( $cell );

			$('.stock', $cell).hide();

			if ( isFastPool() ) {
				$( '.wcbvp-cell', $instance ).not( $cell ).find('select').val('');
				$( '.wcbvp-cell', $instance ).not( $cell ).find( 'input.wcbvp-quantity-input' ).val(0);
				$( '.wcbvp-form-variation' ).removeClass( 'selected' );
				$cell.find( '.wcbvp-form-variation' ).addClass( 'selected' );
			}

			const selectedVariation = getSelection( $cell ),
				  $pool             = $( '.wcbvp-variation-pool', $instance ),
				  $qty              = $( 'input.wcbvp-quantity', $cell );

			let price = 0,
				stock = '';

			$qty.prop( 'disabled', true );

			if ( ! isFastPool() ) {
				const $items = $( `.wcbvp-variation-pool-item`, $pool ).filter( ( index, item ) => {
					return $cell.attr('data-variation_ids').split(',').includes( $(item).data('product_id').toString() )
				} );

				$items.remove()
			}

			if ( selectedVariation ) {
				let data = {
					isInStock: selectedVariation.variation.is_in_stock,
					isPurchasable: selectedVariation.variation.is_purchasable,
					price: selectedVariation.variation.display_price,
					stock: selectedVariation.variation.availability_html
				};

				const filteredData = $( document ).triggerHandler( 'wbvCell.update', [ data, selectedVariation.variation, $instance ] );

				if ( undefined !== filteredData ) {
					data = filteredData;
				}

				const isInStock      = data.isInStock,
					  isPurchasable  = data.isPurchasable;

				$( '>*', $cell ).toggle( isPurchasable );

				if ( ! isPurchasable ) {
					return true;
				}

				$qty.prop( 'disabled', ! isInStock || ! isPurchasable );

				if ( isInStock ) {
					const def = $qty.val() || Number( selectedVariation.variation.input_value ) || 0;

					$qty.attr( 'min', selectedVariation.variation.min_qty )
						.attr( 'max', selectedVariation.variation.max_qty )
						.val( def )
						.trigger( 'select' );

						if ( focus ) {
							$qty.trigger( 'focus' );
						}

					if ( isFastPool() ) {
						$qty.val( 0 );

						const $item = $pool.find( `.wcbvp-variation-pool-item[data-product_id="${selectedVariation.variation.variation_id}"]` );

						$pool.find( '.wcbvp-variation-pool-item' ).removeClass( 'selected' );

						if ( $item.length ) {
							$qty.val( $item.find('.wcbvp-quantity').val() ).trigger( 'select' );
							$item.addClass( 'selected' );
						}
					} else {
						if ( $qty.val() > 0 ) {
							addItem( $qty );
						}
					}

					if ( params.show_stock || params.show_discontinued ) {
						stock = data.stock;
					}
				} else {
					// always display the availability of the selected variation if it is out of stock
					stock = data.stock;
				}

				price = wcFormatPrice( data.price );
				stock = data.stock;
			} else {
				$qty.val( 0 ).trigger( 'input' );
				price = getPriceRange( $cell );
			}

			$( '.price', $cell ).html( price );
			$( '.stock', $cell ).html( stock ).show();

			return false !== selectedVariation;
		}

		const resetTableInfo = ( $instance ) => {
			$( '.wcbvp-cell', $instance ).each( ( index, cell ) => {
				resetCellInfo( $( cell ), false );
			})

			recalculate( $instance );
		}

		// const onHeaderAttributeSelect = ( e ) => {
		// 	const $this       = $( e.currentTarget ),
		//        $instance   = getInstance( $this ),
		// 		  $cells      = $( '.wcbvp-cell', $instance ),
		// 		  $items      = $( '.wcbvp-variation-pool-item', $instance ),
		// 		  chosenCount = $( '.wcbvp-header select', $instance ).toArray().map( s => s.value ).filter( v => v.length > 0 ).length,
		// 		  allChosen   = chosenCount === $( '.wcbvp-header select', $instance ).length;

		// 	$( '.stock', $instance ).hide();

		// 	if ( isFastPool() ) {
		// 		$( '.wcbvp-form-variation', $instance ).removeClass( 'selected' );
		// 	}

		// 	$cells.each( ( index, cell ) => {
		// 		const $cell = $( cell ),
		// 			  cellAvailableVariations = getCellAvailableVariations( $( cell ) ),
		// 			  variationIds = cellAvailableVariations.map( av => av.variation_id ),
		// 			  $qty = $( 'input[type="number"]', $cell );

		// 		$qty.prop( 'disabled', true ).val( 0 );

		// 		if ( allChosen && variationIds.length > 0 ) {
		// 			$qty.prop( 'disabled', false );
		// 		}

		// 		$( '.price', $cell ).html( getPriceRange( $( cell ) ) );
		// 		$( '.stock', $cell ).html( '' ).show();
		// 		$cell.attr( 'data-variation_ids', variationIds.join( ',' ) );
		// 	} );

		// 	if ( $( 'form', $instance ).hasClass( 'wcbvp-compact-grid-mode' ) ) {
		// 		const layerVariationIds = $cells.toArray().reduce(
		// 			( r, c ) => {
		// 				if ( c.dataset['variation_ids'] ) {
		// 					return r.concat( c.dataset['variation_ids'].split(',') );
		// 				}
		// 				return r;
		// 			},
		// 			[]
		// 		);

		// 		$items.each( ( index, item ) => {
		// 			const productId = $( item ).attr( 'data-product_id' ),
		// 				  qty       = $( 'input.wcbvp-quantity', $( item ) ).val();

		// 			if ( productId ) {
		// 				$( item ).toggleClass( 'layer-selected', layerVariationIds.includes( $( item ).attr( 'data-product_id' ) ) );
		// 				$( `.wcbvp-cell[data-variation_ids="${productId}"] input[type="number"]` ).val( qty );
		// 			}
		// 		} )
		// 	}
		// };

		const getPriceRange = ( $cell ) => {
			const variations = getCellAvailableVariations( $cell ),
				  $instance  = $cell.closest( '.wc-bulk-variations-table-wrapper' );

			let priceField = 'display_price';

			const filteredPriceField = $( document ).triggerHandler( 'wbvCell.pricerange', [ priceField, $instance ] );

			if ( undefined !== filteredPriceField ) {
				priceField = filteredPriceField
			}

			let range = variations.reduce( ( r, v ) => {
				return {
					min: Math.min( r.min, v[ priceField ] ),
					max: Math.max( r.max, v[ priceField ] )
				};
			}, { min: Infinity, max: 0 } );

			if ( range.min === range.max ) {
				return wcFormatPrice( range.min );
			} else if ( 0 === range.max ) {
				return '';
			} else {
				return `${wcFormatPrice( range.min )} – ${wcFormatPrice( range.max )}`;
			}
		}

		const getSelection = ( $cell ) => {

			const labelSeparator = ', ',
				  labels         = [ $cell.data( 'h_label' ) ],
				  $instance      = getInstance( $cell );

			let variation,
				attributes;

			if ( $cell.attr( 'data-v_label' ) ) {
				labels.push( $cell.attr( 'data-v_label' ) );
			}

			// get the select boxes in a given cell
			let $selects = $( 'select', $cell );

			if ( $( 'form.wcbvp-cart', $instance ).hasClass( 'wcbvp-compact-grid-mode' ) ) {
				$selects = $( '.wcbvp-header select', $instance );
			}

			const availableVariations = getCellAvailableVariations( $cell );

			if ( ! availableVariations.length ) {
				return false;
			}

			if ( $selects.length > 0 ) {
				// there are no select boxes or at least one select box has no value
				if ( $selects.toArray().filter( s => ! s.value ).length ) {
					return false;
				}

				// get an object with the attributes currently selected
				const selectedAttributes = $selects.toArray().map( s => {
					return {
						[s.name]: {
							name: s.value,
							label: $( s ).find( `option[value="${s.value}"]`).text()
						}
					};
				} ).reduce( (r, s) => ( { ...r, ...s } ), {} );

				variation = availableVariations.find( v => {

					// filter the variations for which the number of matching attributes
					// is identical to the number of selectable attributes
					// (only one variation satisfies this criteria!)
					return Object.entries( selectedAttributes ).filter( s => {

						// return true if an attribute exists among the variation attributes
						// that has the same name and value of the evaluated selected attribute
						return ( s[0] in v.attributes && v.attributes[ s[0] ] === s[1].name );

					}).length === $selects.length;
				});

				if ( ! variation ) {
					return false;
				}

				let label = labels.concat( Object.values( selectedAttributes ).map( sa => sa.label ) ).join( labelSeparator );

				// if ( $cell.closest('form.wcbvp-cart').hasClass( 'wcbvp-compact-grid-mode' ) ) {
				// 	label = Object.values( selectedAttributes ).map( sa => sa.label ).concat( labels ).join( labelSeparator );
				// }

				attributes = {
					object: selectedAttributes,
					label
				};
			} else {
				variation  = availableVariations[0];
				attributes = {
					object: {},
					label:  labels.join( labelSeparator )
				};
			}

			return {
				attributes,
				variation
			};

		};

		const getPool = ( $instances ) => {
			$instances = $instances || $( '.wc-bulk-variations-table-wrapper' );

			const pool = {};

			$instances.each( ( index, instance ) => {
				const $instance = $( instance ),
					  items = [];

				let id    = $( '.wcbvp-total-wrapper', $instance ).attr( 'id' ) || '',
					count = 0;

				if ( id ) {
					id = id.replace( 'wcbvp_wrapper_wbv_', '' );
				} else {
					id = count++;
				}

				$( '.wcbvp-variation-pool-item', $instance ).toArray().forEach( item => {
					items.push( {
						product_id: Number( item.dataset.product_id ),
						quantity:   Number( item.children[0].value ),
					} );
				} );
				pool[ id ] = items;
			} );

			return pool;
		};

		// const onPoolItemDelete = ( e ) => {
		// 	const $this     = $( e.currentTarget ),
		// 		  $item     = $this.closest( '.wcbvp-variation-pool-item' );

		// 	setCellByPoolItem( $item, 'delete' );
		// 	$item.remove();
		// 	recalculate( getInstance( $this ) );
		// };

		const onGlobalClick = ( e ) => {
			if ( 0 === $( e .target ).closest( '.wc-bulk-variations-table-wrapper' ).length ) {
				return true;
			}

			setCurrentInstance( e );

			const noReset = $( e.target ).closest( '.wcbvp-form-variation, .wcbvp-variation-pool-item, .wcbvp-compact-grid-mode .wcbvp-row-header.wcbvp-col-header' ).length > 0;
			if ( noReset ) {
				return true;
			}

			reset();
		};

		// const onVariationEdit = ( e ) => {
		// 	e.stopPropagation();

		// 	const $this = $( e.target );

		// 	const $item     = $this.closest( '.wcbvp-variation-pool-item' ),
		// 		  $instance = getInstance( $item );

		// 	if ( $item.length && ! $this.hasClass( 'action-delete' ) ) {
		// 		$( 'wcbvp-variation-pool-item', $instance ).remove( 'selected' );
		// 		setCellByPoolItem( $item );
		// 	}

		// };

		// const setCellByPoolItem = ( $item, action = 'select' ) => {
		// 	// reset();

		// 	const start = performance.now();

		// 	const $instance    = $item.closest( '.wc-bulk-variations-table-wrapper' ),
		// 		  $form        = $instance.find( '.wcbvp-cart' ),
		// 		  variation_id = $item.data( 'product_id' ),
		// 		  qty          = $item.find( 'input' ).val();

		// 	if ( $form.hasClass( 'wcbvp-compact-grid-mode' ) ) {
		// 		$( '.wcbvp-header select', $form ).each( (index, element) => {
		// 			$( element ).val( $item.attr( `data-${$( element ).attr( 'name' )}` ) );
		// 		})
		// 		$( '.wcbvp-header select', $form ).first().trigger('change');
		// 	}

		// 	let $cell = $( '.wcbvp-cell', $instance ).filter( ( index, element ) => {
		// 		const variation_ids = $( element ).attr( 'data-variation_ids' );

		// 		if ( variation_ids ) {
		// 			return variation_ids.split( ',' ).includes( variation_id.toString() );
		// 		}

		// 		return false;
		// 	} );

		// 	if ( $cell.length ) {
		// 		const $qty     = $cell.find( '.wcbvp-quantity-field input[type="number"]' );

		// 		if ( 'select' === action ) {
		// 			// the pool item was selected
		// 			// update all the controls inside the appropriate cell
		// 			// if ( $form.hasClass( 'wcbvp-expanded-grid-mode' ) ) {
		// 			// 	let $selects = $( 'select', $cell );

		// 			// 	$selects.each( (index, element) => {
		// 			// 		$( element ).val( $item.attr( `data-${$( element ).attr( 'name' )}` ) );
		// 			// 	});

		// 			// 	// simulate the selection of the first select box
		// 			// 	// just to trigger the update of the cell content
		// 			// 	$selects.first().trigger('change');
		// 			// }

		// 			$qty.prop( 'disabled', false ).val( qty ).trigger( 'select' ).trigger( 'focus' );

		// 			if ( isFastPool() ) {
		// 				$( '.wcbvp-form-variation', $cell ).addClass( 'selected' );
		// 				$item.addClass('selected');
		// 			}
		// 		} else {
		// 			// the pool item was deleted
		// 			// reset the quantity input (especially for persistent inputs)
		// 			$qty.val( 0 ).prop( 'disabled', true );
		// 		}

		// 	}
		// };

		const getRealSelectSize = ( $select ) => {
			const computedStyle = getComputedStyle( $select.get(0) ),
				  parent        = $select.parent().get(0),
			      $dummySelect  = $select.clone();

			$dummySelect.find('option:not(:first-child)').remove();

			const { font, border, padding, margin, marginRight, marginLeft, paddingLeft, paddingRight } = computedStyle;

			$dummySelect.css( { font, border, padding, margin } );
			$( document.body ).append( $dummySelect );
			const width = $dummySelect.get(0).offsetWidth;
			$dummySelect.remove();

			const marginWidth  = Number( marginLeft.replace('px','') ) + Number( marginRight.replace('px','') ),
				  paddingWidth = Number( paddingLeft.replace('px','') ) + Number( paddingRight.replace('px','') );

			return Math.min( width, parent.offsetWidth - marginWidth - paddingWidth );
		};

		const onSelectCurrentImage = ( e ) => {
			const $element = $( e.target )
			const $form = $( 'form.wcbvp-cart', getInstance( $element ) )

			if ( $form ) {
				const variationId = $element.data('variation')
				const variations = $form.data( 'product_variations' ).filter( ( variation ) => variation.variation_id === variationId && variation.image )

				if ( variations.length > 0 ) {
					$form.wc_variations_image_update(variations[0])
				}
			}
		}
	
	    const onOpenPhotoswipe = ( event ) => {
	        if ( typeof PhotoSwipe === 'undefined' || typeof PhotoSwipeUI_Default === 'undefined' ) {
	            return true;
	        }

	        event.preventDefault();

	        var pswpElement = $( '.pswp' )[0],
	            $target     = $( event.target ),
	            $galleryImage = $target.closest( '.woocommerce-product-gallery__image' ),
	            items = [];

	        pswpElement.classList.add( 'wbv-gallery' );

	        if ( $galleryImage.length > 0 ) {
	            $galleryImage.each( function( i, el ) {
	                var img = $( el ).find( 'img' ),
	                    large_image_src = img.attr( 'data-large_image' ),
	                    large_image_w = img.attr( 'data-large_image_width' ),
	                    large_image_h = img.attr( 'data-large_image_height' ),
	                    item = {
	                        src: large_image_src,
	                        w: large_image_w,
	                        h: large_image_h,
	                        title: ( img.attr( 'data-caption' ) && img.attr( 'data-caption' ).length ) ? img.attr( 'data-caption' ) : img.attr( 'title' )
	                    };
	                items.push( item );
	            } );
	        }

	        var options = {
	            index: 0,
	            shareEl: false,
	            closeOnScroll: false,
	            history: false,
	            hideAnimationDuration: 0,
	            showAnimationDuration: 0
	        };

	        // Initializes and opens PhotoSwipe
	        var photoswipe = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options );
	        photoswipe.init();
	        photoswipe.listen( 'close', function() {
	            window.dontCloseQVP = true;
	        });
	    };

		const debug = ( ...data ) => {
            if ( params.debug ) {
				console.trace( 'WBV debug:', ...data );
            }
        };

        // Public API.
        return {
            initialize,
			recalculate,
			getPool,
			reset,
			clear,
			getSelection,
			wcFormatPrice,
			resetTableInfo
        };

	} )();

	window.WCBulkVariations = BulkVariations;

	$( () => {
		BulkVariations.initialize();
	});

} )( jQuery, window, document, wc_bulk_variations_params );
