/**
 * Kleistad javascript functies voor formulieren.
 *
 * @author Eric Sprangers.
 * @since  6.0.0
 * @package Kleistad
 * noinspection EqualityComparisonWithCoercionJS
 */

/* global kleistadData */
/* exported strtodate, strtotime, timetostr */

/**
 * Converteer string naar tijd in minuten
 *
 * @param {String} value
 */
function strtotime( value ) {
	let hours, minutes;
	if ( 'string' === typeof value ) {
		if ( Number( value ) == value ) {
			return Number( value );
		}
		hours   = value.substring( 0, 2 );
		minutes = value.substring( 3 );
		return Number( hours ) * 60 + Number( minutes );
	}
	return value;
}

/**
 * Converteer tijd in minuten naar tijd text.
 *
 * @param {int} value
 */
function timetostr( value ) {
	let hours   = Math.floor( value / 60 );
	let minutes = value % 60;
	return ( '0' + hours ).slice( -2 ) + ':' + ( '0' + minutes ).slice( -2 );
}

/**
 * Converteer lokale datum in format 'd-m-Y' naar Date.
 *
 * @param {String} value De datum string.
 */
function strtodate( value ) {
	let veld = value.split( '-' );
	return new Date( veld[2], veld[1] - 1, veld[0] );
}

/**
 * Vergelijking van twee email inputvelden.
 *
 * @param input   Het inputveld.
 * @param compare Het vergelijkingsveld.
 */
function validate_email( input, compare ) {
	input.setCustomValidity( ( input.value === compare.value ) ? '' : 'E-mailadressen zijn niet gelijk' );
}

/**
 * Document ready.
 */
( function( $ ) {
	'use strict';

	/**
	 * Zoek de postcode op via de server.
	 */
	$.fn.lookupPostcode = function( postcode, huisnr, callback ) {
		if ( '' !== postcode && '' !== huisnr ) {
			$.ajax(
				{
					url: kleistadData.base_url + '/adres/',
					method: 'GET',
					data: {
						postcode: postcode,
						huisnr:   huisnr
					}
				}
			).done(
				function( data ) { // Als er niets gevonden kan worden dan code 204, data is dan undefined.
					if ( 'undefined' !== typeof data ) {
						callback( data );
					}
				}
			); // Geen verdere actie ingeval van falen.
		}
	};

	/**
	 * Submit een Kleistad formulier via Ajax call
	 *
	 * @param { jQuery } $shortcode
	 * @param { array} data
	 */
	function submitForm( $shortcode, data ) {
		/**
		 *  Bij een submit de spinner tonen.
		 */
		$( '#kleistad_wachten' ).addClass( 'kleistad-wachten' ).show();
		$.ajax(
			{
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				contentType: false,
				data:        data,
				method:      'POST',
				processData: false,
				url:         kleistadData.base_url + '/formsubmit/'
			}
		).done(
			/**
			 * Toggle de wacht zandloper
			 *
			 * @param data
			 * @property {function} $.fn.vervolg
			 */
			function( data ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad-wachten' );
				$.fn.vervolg( $shortcode, data );
			}
		).fail(
			function( jqXHR ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad-wachten' );
				if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
					window.console.log( jqXHR.responseJSON.message );
				}
				$( '#kleistad_berichten' ).html( kleistadData.error_message );
			}
		);
	}

	/**
	 * Toon een confirmatie popup dialoog.
	 *
	 * @param {String} tekst Te tonen tekst. Als leeg dan wordt er geen popup getoond.
	 * @param callback Uit te voeren actie indien ok.
	 */
	function askConfirm( tekst, callback ) {
		if ( tekst.length ) {
			$( '#kleistad_bevestigen' ).text( tekst[1] ).dialog(
				{
					modal: true,
					zIndex: 10000,
					autoOpen: true,
					width: 'auto',
					resizable: false,
					title: tekst[0],
					open: function() {
						$( '.ui-dialog' ).find( '.ui-button' ).addClass( 'kleistad-button' ).removeClass( 'ui-button' );
					},
					buttons: [
					{
						text: 'Ja',
						click: function() {
							$( this ).dialog( 'close' );
							callback();
							return true;
						}
					},
					{
						text: 'Nee',
						click: function() {
							$( this ).dialog( 'close' );
							return false;
						}
					}
					]
				}
			);
			return false;
		}
		callback();
		return true;
	}

	/**
	 * Wordt aangeroepen nadat de webpage geladen is.
	 */
	$(
		function()
		{
			/**
			 * Definieer de bank selectie.
			 */
			let $bank = $( '#kleistad_bank' );
			if ( $bank[0] ) {
				$bank.iconselectmenu().iconselectmenu( 'menuWidget' ).addClass( 'ui-menu-icons kleistad-bank' );
			}

			/**
			 * Definieer de bevestig dialoog.
			 */
			$( '#kleistad_bevestigen' ).dialog(
				{
					autoOpen: false,
					modal: true
				}
			);

			$( '.kleistad-shortcode' )
			/**
			 * Leg voor de submit actie vast welke button de submit geïnitieerd heeft.
			 */
			.on(
				'click',
				'button[type="submit"]',
				function( event ) {
					$( this ).closest( 'form' ).data( 'clicked', { id: event.target.id, value: event.target.value } );
					return true;
				}
			)
			/**
			 * Submit het formulier, als er een formulier is.
			 *
			 * @property {function} $.fn.shortcode
			 */
			.on(
				'submit',
				'form',
				function( event ) {
					let $form         = $( this );
					let shortcodeData = $.fn.shortcode( $form );
					let formData      = new FormData( this );
					let clicked       = $form.data( 'clicked' );
					let confirm       = $( '#' + clicked.id ).data( 'confirm' );
					let tekst         = 'undefined' === typeof confirm ? [] : confirm.split( '|' );
					formData.append( 'form_actie', clicked.value );
					Object.keys( shortcodeData ).forEach(
						function( item ) {
							formData.append( item, shortcodeData[item] );
						}
					);
					event.preventDefault();
					/**
					 * Als er een tekst is om eerst te confirmeren dan de popup tonen.
					 */
					return askConfirm(
						tekst,
						function() {
							submitForm( $form.closest( '.kleistad-shortcode' ), formData );
						}
					);
				}
			);
		}
	);

} )( jQuery );
