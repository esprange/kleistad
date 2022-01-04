/**
 * Dagdelenkaart bestelling Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

( function( $ ) {
	'use strict';

	$(
		/**
		 * Document ready.
		 */
		function()
		{
			/**
			 * Definieer het datum veld.
			 */
			$( '#kleistad_start_datum' ).datepicker(
				'option',
				{
					minDate: 0,
					maxDate: '+3M'
				}
			);

			/**
			 * Wijzig de button tekst.
			 */
			$( 'input[name=betaal]:radio' ).on(
				'change',
				function() {
					$( '#kleistad_submit' ).html( ( 'ideal' === $( this ).val() ) ? 'betalen' : 'verzenden' );
				}
			);

			/**
			 * Vul adresvelden in
			 */
			$( '#kleistad_huisnr, #kleistad_pcode' ).on(
				'change',
				function() {
					let pcode = $( '#kleistad_pcode' );
					pcode.val( pcode.val().toUpperCase() );
					$().lookupPostcode(
						pcode.val(),
						$( '#kleistad_huisnr' ).val(),
						/**
						 * Anonieme functie
						 *
						 * @param {object} data
						 * @param {string} data.straat
						 * @param {string} data.plaats
						 */
						function( data ) {
							$( '#kleistad_straat' ).val( data.straat );
							$( '#kleistad_plaats' ).val( data.plaats );
						}
					);
				}
			);
		}
	);

} )( jQuery );
