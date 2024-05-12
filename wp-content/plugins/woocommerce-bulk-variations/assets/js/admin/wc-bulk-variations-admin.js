( function( $, window, document, undefined ) {
	"use strict";

	const toggleChildSettings = function( $parent ) {
		let show = false;
		const toggleVal = $parent.data( 'toggleVal' ),
			  closestAncestorTag = $parent.data( 'ancestorTag' ) || 'tr',
			  $children = $parent.closest( '.form-table' ).find( '.' + $parent.data( 'child-class' ) ).closest( closestAncestorTag );

		$children.each( function() {
			if ( 'radio' === $parent.attr( 'type' ) ) {
				show = $parent.prop( 'checked' ) && toggleVal == $parent.val();
			} else if ( 'checkbox' === $parent.attr( 'type' ) ) {
				if ( typeof toggleVal === 'undefined' || 1 == toggleVal ) {
					show = $parent.prop( 'checked' );
				} else {
					show = !$parent.prop( 'checked' );
				}
			} else if ( 'select' === $parent.prop( 'tagName' ).toLowerCase() ) {
				var foundOption = `.${$parent.data( 'child-class' )}-${$parent.val()}`;
				show = ! ! $( this ).find( foundOption ).length;
			} else {
				show = ( toggleVal == $parent.val() );
			}
			$( this ).toggle( show );
		});
	};

	$( () => {

		$( '.form-table .wbv-toggle-parent' ).each( ( e ) => {
			const $this = $( e.currentTarget );

			toggleChildSettings( $this );

			$this.on( 'change', () => {
				toggleChildSettings( $this );
			} );

		} );

		$( document ).on( 'change', 'select.wcbvp-attribute-selector', ( e ) => {
			const $this = $( e.currentTarget );

			$( 'select.wcbvp-attribute-selector' ).not( $this ).find( 'option' ).each( ( index, option ) => {
				$( option ).prop( 'disabled', $( option ).val() && $this.val() === $( option ).val() );
			});
		} );

		$( 'select.wcbvp-attribute-selector' ).trigger('change');		

	} );


} )( jQuery, window, document );
