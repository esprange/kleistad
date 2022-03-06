/**
 * Kleistad javascript voor profiel.
 *
 * @author Eric Sprangers.
 * @since  6.21.3
 * @package Kleistad
 */

/* global kleistadData */

( function( $ ) {
	'use strict';

	/**
	 * Wordt aangeroepen nadat de webpage geladen is.
	 */
	$(
		function() {

			/**
			 * Site inner is de pagina inhoud, exclusief eventuele banner voor gebruikers met adminrechten.
			 */
			let $page = $( '.site-inner' );

			/**
			 * Plak de profiel div als eerste regel in de normale pagina.
			 */
			$page.prepend( '<div id="kleistad_profiel" class="kleistad kleistad-profiel">&nbsp;</div>' );

			/**
			 * Haal de profiel gegevens op
			 */
			$.ajax(
				{
					beforeSend: function( xhr ) {
						xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
					},
					contentType: false,
					method:      'GET',
					processData: false,
					url:         kleistadData.base_url + '/profiel/'
				}
			).done(
				/**
				 * De profiel gegevens zijn ontvangen
				 *
				 * @param data
				 */
				function( data ) {
					$( '#kleistad_profiel' ).html( data.html );
				}
			);

			/**
			 * Toon de betaalinfo.
			 */
			$page.on(
				'click',
				'#kleistad_betaalinfo',
				function ( element ) {
					$( '.kleistad-openstaand' ).toggle( 'drop' );
					element.stopPropagation();
				}
			);

			/**
			 * Als er buiten de popup wordt geklikt.
			 */
			$( 'body' ).on(
				'click',
				function() {
					$( '.kleistad-openstaand' ).hide();
				}
			)
		}
	);

} )( jQuery );
