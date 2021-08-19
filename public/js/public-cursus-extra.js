/**
 * Inschrijving extra cursisten Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

( function( $ ) {
	'use strict';

	/**
	 * Documemt ready.
	 */
	$(
		function() {
			$( '.kleistad-input' ).on(
				'change',
				function() {
					let waarde       = '',
						$medecursist = $( this ).parents( '.medecursist' );
					$medecursist.find( '.kleistad-input' ).each(
						function() {
							waarde += $( this ).val();
						}
					);
					$medecursist.find( '.kleistad-input' ).prop( 'required', '' !== waarde );
				}
			);
		}
	);

} )( jQuery );
