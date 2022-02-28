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

			$( '.kleistad-shortcode' )
			.on(
				'click',
				'#kleistad_abo_pauze',
				function() {
					$( '[id^=kleistad_optie]' ).hide();
					$( '#kleistad_optie_pauze' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).val( 'pauze' ).data( { confirm: 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt pauzeren of de pauze wilt aanpassen ?' } );
				}
			)
			.on(
				'click',
				'#kleistad_abo_einde',
				function() {
					$( '[id^=kleistad_optie]' ).hide();
					$( '#kleistad_optie_einde' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).val( 'einde' ).data( { confirm: 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt beëindigen ?' } );
				}
			)
			.on(
				'click',
				'#kleistad_abo_soort',
				function() {
					$( '[id^=kleistad_optie]' ).hide();
					$( '#kleistad_optie_soort' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).val( 'soort' ).data( { confirm: 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt wijzigen ?' } );
				}
			)
			.on(
				'click',
				'#kleistad_abo_extras',
				function() {
					$( '[id^=kleistad_optie]' ).hide();
					$( '#kleistad_optie_extras' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).val( 'extras' ).data( { confirm: 'Abonnement wijzigen|Weet je zeker dat je de extras van jouw abonnement wilt wijzigen ?' } );
				}
			)
			.on(
				'click',
				'#kleistad_abo_betaalwijze',
				function() {
					$( '[id^=kleistad_optie]' ).hide();
					$( '#kleistad_optie_betaalwijze' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).val( 'betaalwijze' ).data( { confirm: 'Abonnement wijzigen|Weet je zeker dat je de betaalwijze van jouw abonnement wilt wijzigen ?' } );
				}
			);
		}
	);

} )( jQuery );
