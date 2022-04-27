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
			$( 'input[name^=extra_cursist]' ).on(
				'change',
				function() {
					const $inputs = $( this ).parents( '[id^=kleistad_medecursist]' ).find( 'input' );
					let	values    = $inputs.map(
						function() {
							return $( this ).val();
						}
					).get().join( '' );
					$inputs.prop( 'required', '' !== values );
				}
			);
		}
	);

} )( jQuery );
