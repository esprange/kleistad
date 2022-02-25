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

		}
	);

} )( jQuery );
