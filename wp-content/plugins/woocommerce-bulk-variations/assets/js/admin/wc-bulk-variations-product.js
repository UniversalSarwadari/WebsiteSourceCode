(function( $, window, document, params, undefined ) {
	"use strict";

	const toggleBulkVariationsTab = () => {
		$( '.show_if_bulk_variations' ).toggle( 'variable' === $( '#product-type' ).val() || $( '.woocommerce_variation' ).length > 0 );
	};

	$( '#product-type' ).on( 'change', () => {
		toggleBulkVariationsTab();
	} ).trigger('change');

	$( "#variable_product_options" ).on( 'reload', () => {
		updateAttributeDropdowns();
	} );

	$( `input#${params.option_variation_data}_enable` ).change( ( e ) => {
		$( `#${params.option_variation_data}_hide_add_to_cart_div` ).toggle( ! e.currentTarget.checked );
	} ).trigger('change');

	$( `#${params.option_variation_data}_override` ).on( 'change', ( e ) => {
		$( `#${params.option_variation_data}_div` ).toggle( e.currentTarget.checked );
	} ).trigger('change');

	$( 'select.wcbvp-attribute-selector' ).on( 'change', ( e ) => {
		const $this = $( e.currentTarget );

		$( 'select.wcbvp-attribute-selector' ).not( $this ).find( 'option' ).each( ( index, option ) => {
			$( option ).prop( 'disabled', $( option ).val() && $this.val() === $( option ).val() );
		});
	} ).trigger('change');

	$( document ).ajaxComplete( ( event, xhr ) => {
		if ( xhr.responseJSON && xhr.responseJSON.data ) {
			const attributeCount = $( '<div>' ).append( $( xhr.responseJSON.data.html ) ).find( '.woocommerce_attribute' ).length;

			if ( attributeCount ) {
				$( '#bulk_variations_product_data .options_group' ).toggleClass( 'single-attribute', attributeCount === 1 );

				updateAttributeDropdowns( xhr.responseJSON.data.html );
			}

			toggleBulkVariationsTab();
		}
	} );

	const updateAttributeDropdowns = ( html ) => {
		const productAttributes = $( '<div>' ).append( $( html ) );

		const attributes = [];

		productAttributes.find( 'input[name*="attribute_names"]' ).each( ( index, input ) => {
			if ( '1' === productAttributes.find( 'input[name*="attribute_variation"]' ).get( index ).value ) {
				const attribute = {
					name: input.value,
					label: input.value
				};

				if ( $( input ).closest( '.taxonomy' ).length ) {
					attribute.label = $( input ).prev().text();
				}

				attributes.push( attribute );
			}
		});

		if ( attributes.length ) {
			$( 'select.wcbvp-attribute-selector' ).each( ( index, select ) => {
				const oldValue = $( select ).val();
				$( select )
					.find('option')
					.remove()
					.end()
					.append(`<option>${params.choose_label}</option>`);

				attributes.forEach( ( { name, label } ) => {
					$( select ).append( $( '<option>', { value: name } ).text( label ) );
				} );

				$( select ).val( oldValue );
			} );

			$( 'select.wcbvp-attribute-selector' ).trigger('change');
		}
	};

})( jQuery, window, document, wcbvp_data );
