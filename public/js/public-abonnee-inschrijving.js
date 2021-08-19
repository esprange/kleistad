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
		function()
		{
			wijzigTeksten();

			/**
			 * Initieer het start datum veld.
			 */
			$( '#kleistad_start_datum' ).datepicker(
				'option',
				{
					minDate: 0,
					maxDate: '+3M'
				}
			);

			/**
			 * Afhankelijk van keuze abonnement al dan niet tonen dag waarvoor beperkt abo geldig is.
			 */
			$( 'input[name=abonnement_keuze]:radio' ).on(
				'change',
				function() {
					wijzigTeksten();
					if (  'beperkt' === this.value ) {
						$( '#kleistad_dag' ).css( 'visibility', 'visible' );
					} else {
						$( '#kleistad_dag' ).css( 'visibility', 'hidden' );
					}
				}
			);

			/**
			 * Wijzig de teksten als een extra optie wordt aangevinkt.
			 */
			$( 'input[name^=extras]:checkbox' ).on(
				'change',
				function() {
					wijzigTeksten();
				}
			);

			/**
			 * Wijzig de button tekst bij betaling dan wel aanmelding.
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
					pcode.val( pcode.val().toUpperCase().replace( /\s/g, '' ) );
					$().lookupPostcode(
						pcode.val(),
						$( '#kleistad_huisnr' ).val(),
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
