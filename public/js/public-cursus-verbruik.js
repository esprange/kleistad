/**
 * Cursus verbruik Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  7.4.0
 * @package Kleistad
 */

( function( $ ) {
	'use strict';

	/**
	 * Document ready.
	 */
	$(
		function()
		{
			$( '.kleistad-shortcode' )
			/**
			 * Als er een andere foto gekozen wordt.
			 */
			.on(
				'change paste keyup',
				'input[name^=verbruik]',
				function() {
					const materiaalprijs = $( '#materiaalprijs' ).val();
					$( this ).parent().next().html( '€ ' + ( $( this ).val() * materiaalprijs / 1000 ).toFixed( 2 ) );
				}
			);
		}
	);

} )( jQuery );
