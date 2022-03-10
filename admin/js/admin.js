/**
 * Admin dashboard Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

/* global: URL */

( function( $ ) {
	'use strict';

	/**
	 * Converteer lokale datum in format 'd-m-Y' naar Date.
	 *
	 * @param {String} value De tijdstring.
	 */
	function strtodate( value ) {
		let veld = value.split( '-' );
		return new Date( veld[2], veld[1] - 1, veld[0] );
	}

	/**
	 * Definieer de datum velden.
	 */
	function defineDatumpickers() {
		let $datum = $( '.kleistad-datum' );
		if ( $datum[0] && ! $datum.is( ':data("ui-datepicker")' ) ) {
			$datum.datepicker(
				{
					dateFormat: 'dd-mm-yy',
					beforeShowDay: function( date ) {
						const day = date.getDate();
						if ( $( this ).hasClass( 'maand' ) ) {
							return [ ( 1 === day ) ];
						}
						return [ true ];
					},
					beforeShow: function( input ) {
						return ( ! $( input ).attr( 'readonly' ) );
					}
				}
			);
		}
	}

	/**
	 * Initialiseer de eventuele color pickers.
	 */
	function defineColorpickers() {
		let $color = $( '.kleistad-color' );
		if ( $color[0] ) {
			$color.wpColorPicker();
		}
	}

	/**
	 * Document ready.
	 */
	$(
		function()
		{
			let $saldo                 = $( '#saldo' ),
				$werkplek_start_config = $( '#kleistad_start_config' ),
				$werkplek_eind_config  = $( '#kleistad_eind_config' );

			defineColorpickers();

			defineDatumpickers();

			/**
			 * Voeg 15 euro toe.
			 */
			$( '#add15' ).on(
				'click',
				function() {
					$saldo.val( Math.round( ( Number( $saldo.val() ) + 15 ) * 100 ) / 100 );
					return false;
				}
			);

			/**
			 * Voeg 30 euro toe.
			 */
			$( '#add30' ).on(
				'click',
				function() {
					$saldo.val( Math.round( ( Number( $saldo.val() ) + 30 ) * 100 ) / 100 );
					return false;
				}
			);

			$( '#hoofdterm_id' ).on(
				'change',
				function() {
					let href = new URL( document.location );
					href.searchParams.set( 'hoofdterm_id', $( this ).val() );
					document.location = href.toString();
				}
			);

			/**
			 * Hulp functies voor lijst items bij instellingen.
			 */
			$( 'form' ).on(
				'click',
				'button[id^=kleistad_voegtoe_]',
				function() {
					const parameters = $( this ).data( 'parameters' );
					const key        = $( this ).data( 'key' );
					const index      = $( '#kleistad_lijst_' + key + ' tbody tr' ).length;
					let template     =
						'<td><input type="text" class="regular-text" name="kleistad-opties[' + key + '][' + index + '][naam]" required /></td>';
					parameters.forEach(
						/**
						 * Vul de template aan met de input velden.
						 *
						 * @param parameter De input parameter.
						 * @param {string} parameter.veld Het type input.
						 * @param {string} parameter.naam De naam.
						 */
						function( parameter ) {
							const veldClass = ( 'class' in parameter ) ? parameter.class + ' small-text' : 'small-text';
							template       += '<td><input ' + parameter.veld + ' class="' + veldClass + '" name="kleistad-opties[' + key + '][' + index + '][' + parameter.naam + ']" required /></td>';
						}
					);
					template += '<td><span id="kleistad_verwijder_' + key + '_' + index + '" class="dashicons dashicons-trash" style="cursor: pointer;"></span></td>';
					$( '#kleistad_lijst_' + key + ' tbody' ).append( '<tr>' + template + '</tr>' );
					defineDatumpickers();
					defineColorpickers();
				}
			).on(
				'click',
				'span[id^=kleistad_verwijder_]',
				function() {
					$( this ).closest( 'tr' ).remove();
				}
			);

			/**
			 * Bij wijzigen beperkt abonnement, vereisen dat de dag ingevuld wordt.
			 */
			$( '#kleistad-soort' ).on(
				'change',
				function() {
					$( '#kleistad-dag' ).prop( 'required', ( 'beperkt' === $( this ).val() ) );
				}
			);

			/**
			 * Werkplek configyratie
			 */
			$werkplek_start_config.datepicker(
				'option',
				{
					minDate: ( $( this ).prop( 'disabled' ) ) ? null : 0,
					maxDate: $werkplek_eind_config.datepicker( 'getDate' ),
					onSelect: function( datum ) {
						$( '#kleistad_eind_config' ).datepicker(
							'option',
							{
								minDate: strtodate( datum )
							}
						);
					},
					beforeShowDay: function( datum ) {
						return [ 1 === datum.getDay(), '', '' ]; // Maandagen zijn selecteerbaar.
					}
				}
			);

			/**
			 * Werkplek configuratie
			 */
			$werkplek_eind_config.datepicker(
				'option',
				{
					minDate: $werkplek_start_config.datepicker( 'getDate' ),
					onSelect: function( datum ) {
						$( '#kleistad_start_config' ).datepicker( 'option', { maxDate: strtodate( datum ) } );
					},
					beforeShowDay: function( datum ) {
						return [ 0 === datum.getDay(), '', '' ]; // Zondagen zijn selecteerbaar.
					}
				}
			);
		}
	);
} )( jQuery );
