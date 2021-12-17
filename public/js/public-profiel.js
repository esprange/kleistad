/**
 * Kleistad javascript voor profiel.
 *
 * @author Eric Sprangers.
 * @since  6.21.1
 * @package Kleistad
 */

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
			$page.prepend( '<div class="kleistad kleistad-profiel">&nbsp;</div>' );

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
					$( '.kleistad-profiel' ).html( data.html );
				}
			);

			/**
			 * Toon de betaalinfo.
			 */
			$page.on(
				'click',
				'#kleistad-betaalinfo',
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
