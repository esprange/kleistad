/**
 * Registratie overzicht Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

/* global kleistadData */

( function( $ ) {
	'use strict';

	/**
	 * Document ready.
	 */
	$(
		function()
		{
			/**
			 * Definieer de popup dialoog
			 */
			$( '#kleistad_deelnemer_info' ).dialog(
				{
					autoOpen: false,
					height: 'auto',
					width: 750,
					modal: true,
					open: function() {
						$( '.ui-button' ).addClass( 'kleistad-button' ).removeClass( 'ui-button' );
					},
					buttons: [
						{
							text: 'OK',
							click: function() {
								$( this ).dialog( 'close' );
							}
					}
					]
				}
			);

			/**
			 * Filter de abonnees/cursisten.
			 */
			$( '#kleistad_deelnemer_selectie' ).on(
				'click',
				function() {
					let selectie               = $( this ).val(),
						kleistadDeelnemerLijst = $( '#kleistad_deelnemer_lijst' ).DataTable();
					kleistadDeelnemerLijst.search( '' ).columns().search( '' );
					switch ( selectie ) {
						case '*':
							kleistadDeelnemerLijst.columns().search( '', false, false ).draw();
							break;
						case 'A':
							kleistadDeelnemerLijst.columns( 0 ).search( '1', false, false ).draw();
							break;
						case 'K':
							kleistadDeelnemerLijst.columns( 1 ).search( '1', false, false ).draw();
							break;
						default:
							kleistadDeelnemerLijst.columns( 2 ).search( selectie, false, false ).draw();
					}
				}
			);

			/**
			 * Toon de detailinformatie van de deelnemer
			 */
			$( '#kleistad_deelnemer_lijst tbody' ).on(
				'click touchend',
				'tr',
				function() {
					const $wachten = $( '#kleistad_wachten' );
					$wachten.addClass( 'kleistad-wachten' ).show();
					$.ajax(
						{
							url: kleistadData.base_url + '/registratie/',
							method: 'GET',
							beforeSend: function( xhr ) {
								xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
							},
							data: {
								gebruiker_id: $( this ).data( 'id' )
							}
						}
					).done(
						function( data ) {
							$wachten.removeClass( 'kleistad-wachten' );
							$( '#kleistad_deelnemer_info' ).html( data.content ).dialog( 'option', 'title', data.naam ).dialog( 'open' );						}
					).fail(
						function( jqXHR ) {
							$wachten.removeClass( 'kleistad-wachten' );
							if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
								window.alert( jqXHR.responseJSON.message );
								return;
							}
							window.alert( kleistadData.error_message );
						}
					);
				}
			);

		}
	);
} )( jQuery );
