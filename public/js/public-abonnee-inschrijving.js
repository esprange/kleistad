/**
 * Abonnee inschrijving Kleistad javascript functies.
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
		const $abonnement_keuze = $( '[name=abonnement_keuze]:radio:checked' );
		let bedrag              = $abonnement_keuze.data( 'bedrag' );
		let bedragtekst         = $abonnement_keuze.data( 'bedragtekst' );

		if ( 'undefined' !== typeof bedrag ) {
			$( 'input[name^=extras]:checkbox:checked' ).each(
				function() {
					bedrag += $( this ).data( 'bedrag' );
				}
			);
			$( 'label[for=kleistad_betaal_ideal]' ).text( 'Ik betaal ' + bedrag.toLocaleString( undefined, { style: 'currency', currency: 'EUR' } ) + ' ' + bedragtekst );
			$( 'label[for=kleistad_betaal_stort]' ).text( 'Ik betaal door storting van ' + bedrag.toLocaleString( undefined, { style: 'currency', currency: 'EUR' } ) + ' ' + bedragtekst + ' volgens de betaalinstructie, zoals aangegeven in de te ontvangen bevestigingsemail.' );
		}
	}

	/**
	 * Document ready.
	 */
	$(
		function() {
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
			 * Afhankelijk van keuze abonnement al dan niet tonen dag waarvoor beperkt abo geldig is.
			 */
			$( 'input[name=abonnement_keuze]' ).on(
				'change',
				function () {
					wijzigTeksten();
				}
			).trigger( 'change' );

			$( '.kleistad-shortcode' )
			/**
			 * Wijzig de teksten als een extra optie wordt aangevinkt.
			 */
			.on(
				'change',
				'input[name^=extras]:checkbox',
				function () {
					wijzigTeksten();
				}
			)
		}
	);

} )( jQuery );
