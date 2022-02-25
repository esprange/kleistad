/**
 * Dagdelenkaart bestelling Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

( function( $ ) {
	'use strict';

	/**
	 * Wijzig de teksten in het betaal formulier.
	 */
	function wijzigTeksten() {
		const bedrag = parseFloat( $( '#kleistad_kosten_kaart' ).val() );
		$( 'label[for=kleistad_betaal_ideal]' ).text( 'Ik betaal ' + bedrag.toLocaleString( undefined, { style: 'currency', currency: 'EUR' } ) );
		$( 'label[for=kleistad_betaal_stort]' ).text( 'Ik betaal door storting van ' + bedrag.toLocaleString( undefined, { style: 'currency', currency: 'EUR' } ) + ' volgens de betaalinstructie, zoals aangegeven in de te ontvangen bevestigingsemail.' );
	}

	$(
		/**
		 * Document ready.
		 */
		function()
		{
			wijzigTeksten();

			$( '#kleistad_submit' ).html( 'betalen' );

			/**
			 * Initieer het start datum veld.
			 */
			$( '#kleistad_start_datum' ).datepicker(
				'option',
				{
					minDate: 0,
					maxDate: '+3M'
				}
			).trigger( 'change' );

			/**
			 * Wijzig de button tekst bij betaling dan wel aanmelding.
			 */
			$( '.kleistad-shortcode' )
			.on(
				'change',
				'input[name=betaal]:radio',
				function () {
					$( '#kleistad_submit' ).html( ( 'ideal' === $( this ).val() ) ? 'betalen' : 'verzenden' );
				}
			)
			/**
			 * Vul adresvelden in
			 */
			.on(
				'change',
				'#kleistad_huisnr, #kleistad_pcode',
				function () {
					let pcode = $( '#kleistad_pcode' );
					pcode.val( pcode.val().toUpperCase().replace( /\s/g, '' ) );
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
						function (data) {
							$( '#kleistad_straat' ).val( data.straat ).trigger( 'change' );
							$( '#kleistad_plaats' ).val( data.plaats ).trigger( 'change' );
						}
					);
				}
			);
		}
	);

} )( jQuery );
