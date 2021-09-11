/**
 * Recept Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

/* global kleistadData */

( function( $ ) {
	'use strict';

	let receptFilter;

	/**
	 * Lees de geselecteerde filters.
	 *
	 * @param initieel
	 */
	function leesFilters( initieel ) {
		if ( window.sessionStorage.getItem( 'recept_filter' ) && initieel ) {
			receptFilter = JSON.parse( window.sessionStorage.getItem( 'recept_filter' ) );
			receptFilter.terms.forEach(
				function( item ) {
					$( '#kleistad_filters input[name="term"][value="' + item + '"]' ).prop( 'checked' );
				}
			);
			receptFilter.auteurs.forEach(
				function( item ) {
					$( '#kleistad_filters input[name="auteur"][value="' + item + '"]' ).prop( 'checked' );
				}
			);
			$( '#kleistad_zoek' ).val( receptFilter.zoeker );
			$( '#kleistad_sorteer' ).val( receptFilter.sorteer );
		} else {
			receptFilter = {
				zoeker:  $( '#kleistad_zoek' ).val(),
				sorteer: $( '#kleistad_sorteer' ).val(),
				terms:   [],
				auteurs: []
			};
			$( '#kleistad_filters input[name="term"]:checked' ).each(
				function() {
					receptFilter.terms.push( $( this ).val() );
				}
			);
			$( '#kleistad_filters input[name="auteur"]:checked' ).each(
				function() {
					receptFilter.auteurs.push( $( this ).val() );
				}
			);
			window.sessionStorage.setItem( 'recept_filter', JSON.stringify( receptFilter ) );
		}
	}

	/**
	 * Toon de geselecteerde filters.
	 *
	 * @param status
	 */
	function displayFilters( status ) {
		if ( 'show' === status ) {
			$( '#kleistad_filters' ).css(
				{
					width: '30%',
					display: 'block'
				}
			);
			$( '#kleistad_recept_overzicht' ).css(
				{
					marginLeft: '30%'
				}
			);
			$( '#kleistad_filter_btn' ).html( '- verberg filters' );
		} else {
			$( '#kleistad_filters' ).css(
				{
					display: 'none'
				}
			);
			$( '#kleistad_recept_overzicht' ).css(
				{
					marginLeft: '0%'
				}
			);
			$( '#kleistad_filter_btn' ).html( '+ filter resultaten' );
		}
	}

	/**
	 * Zoek de recepten obv de geselecteerde filters.
	 */
	function zoekRecepten() {
		$.ajax(
			{
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				data: receptFilter,
				method: 'GET',
				url: kleistadData.base_url + '/recept/'
			}
		).done(
			function( data ) {
				$( '#kleistad_recepten' ).html( data.content );
				$( '#kleistad_filters input[name="term"]' ).each(
					function() {
						if ( -1 !== $.inArray( $( this ).val(), data.terms ) ) {
							$( this ).prop( 'checked', true );
							$( this ).next().css( { visibility: 'visible' } );
							$( this ).parent().css( { fontWeight: 'bold' } );
						}
					}
				);
				$( '#kleistad_filters input[name="auteur"]' ).each(
					function() {
						if ( -1 !== $.inArray( $( this ).val(), data.auteurs ) ) {
							$( this ).prop( 'checked', true );
							$( this ).next().css( { visibility: 'visible' } );
							$( this ).parent().css( { fontWeight: 'bold' } );
						}
					}
				);
				displayFilters( window.sessionStorage.getItem( 'recept_filter_status' ) );
			}
		).fail(
			function( jqXHR ) {
				if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
					window.console.log( jqXHR.responseJSON.message );
				}
				$( '#kleistad_berichten' ).html( kleistadData.error_message );
			}
		);
	}

	/**
	 * Document ready.
	 */
	$(
		function()
		{
			leesFilters( true );
			zoekRecepten();

			$( '#kleistad_filter_btn' ).on(
				'click',
				function() {
					let tonen = 'hide' === window.sessionStorage.getItem( 'recept_filter_status' ) ? 'show' : 'hide';
					window.sessionStorage.setItem( 'recept_filter_status', tonen );
					displayFilters( tonen );
				}
			);

			$( '#kleistad_zoek' ).on(
				'keyup',
				function( e ) {
					if ( 'Enter' === e.key ) {
						$( '#kleistad_zoek_icon' ).trigger( 'click' );
					}
				}
			);

			$( '#kleistad_zoek_icon' ).on(
				'click',
				function() {
					leesFilters();
					zoekRecepten();
				}
			);

			$( '#kleistad_sorteer' ).on(
				'change',
				function() {
					$( '#kleistad_zoek_icon' ).trigger( 'click' );
				}
			);

			$( '#kleistad_recepten' )
			.on(
				'click',
				'.kleistad-filter',
				function() {
					$( '#kleistad_zoek_icon' ).trigger( 'click' );
				}
			)
			.on(
				'click',
				'.kleistad-meer',
				function() {
					let filter;
					const name = $( this ).attr( 'name' );

					if ( 'meer' === $( this ).val() ) {
						filter = $( this ).parent().parent(); // Checkbox -> Label -> List element.
					} else {
						filter = $( 'input[name=' + name + '][value=meer]' ).parent().parent();
					}
					filter.toggle();
					filter.nextAll().toggle();
				}
			);
		}
	);

} )( jQuery );
