/**
 * Abonnee wijziging Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

/* global strtodate */

( function( $ ) {
	'use strict';

	/**
	 * Document ready.
	 */
	$(
		function()
		{
			let nuDatum         = new Date(),
				dag             = 24 * 60 * 60 * 1000,
				$pauze_datum    = $( '#kleistad_pauze_datum' ),
				$herstart_datum = $( '#kleistad_herstart_datum' ),
				minPauze        = $pauze_datum.data( 'min_pauze' ),
				maxPauze        = $pauze_datum.data( 'max_pauze' );

			if ( $pauze_datum.hasClass( 'kleistad-datum' ) ) {
				$pauze_datum.datepicker(
					'option',
					{
						minDate: new Date( nuDatum.getFullYear(), nuDatum.getMonth() + 1, 1 ),
						onSelect: function( datum ) {
							let pauzeDatum    = strtodate( datum ),
								herstartDatum = strtodate( $herstart_datum.val() );

							if ( herstartDatum.getTime() < ( pauzeDatum.getTime() + minPauze * dag ) ) {
								herstartDatum.setDate( pauzeDatum.getTime() + minPauze * dag );
								$( '#kleistad_herstart_datum' ).datepicker( 'setDate', herstartDatum );
							}
							$herstart_datum.datepicker(
								'option',
								{
									minDate: new Date( pauzeDatum.getTime() + minPauze * dag ),
									maxDate: new Date( pauzeDatum.getTime() + maxPauze * dag )
								}
							);
						}
					}
				);
			}

			if ( $herstart_datum.hasClass( 'kleistad-datum' ) ) {
				$herstart_datum.datepicker(
					'option',
					{
						minDate: new Date( strtodate( $pauze_datum.val() ).getTime() + minPauze * dag ),
						maxDate: new Date( strtodate( $pauze_datum.val() ).getTime() + maxPauze * dag )
					}
				);
			}

			$( '.kleistad-shortcode' ).on(
				'click',
				'#kleistad_abo_pauze',
				function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_pauze' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt pauzeren of de pauze wilt aanpassen ?' );
				}
			)
			.on(
				'click',
				'#kleistad_abo_einde',
				function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_einde' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt beëindigen ?' );
				}
			)
			.on(
				'click',
				'#kleistad_abo_wijziging',
				function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_wijziging' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt wijzigen ?' );
				}
			)
			.on(
				'click',
				'#kleistad_abo_extras',
				function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_extras' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je de extras van jouw abonnement wilt wijzigen ?' );
				}
			)
			.on(
				'click',
				'#kleistad_abo_dag',
				function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_dag' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je de werkdag van jouw beperkt abonnement wilt wijzigen ?' );
				}
			)
			.on(
				'click',
				'#kleistad_abo_betaalwijze',
				function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_betaalwijze' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je de betaalwijze van jouw abonnement wilt wijzigen ?' );
				}
			);
		}
	);

} )( jQuery );
