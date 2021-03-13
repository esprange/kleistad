/* global strtodate */

( function( $ ) {
	'use strict';

	$( function()
		{
			var nuDatum  = new Date(),
				dag      = 24 * 60 * 60 * 1000,
				minPauze = $( '#kleistad_pauze_datum' ).data( 'min_pauze' ),
				maxPauze = $( '#kleistad_pauze_datum' ).data( 'max_pauze' );

			if ( $( '#kleistad_pauze_datum' ).hasClass( 'kleistad-datum' ) ) {
				$( '#kleistad_pauze_datum' ).datepicker( 'option',
					{
						minDate: new Date( nuDatum.getFullYear(), nuDatum.getMonth() + 1, 1 ),
						onSelect: function( datum ) {
							var pauzeDatum    = strtodate( datum ),
								herstartDatum = strtodate( $( '#kleistad_herstart_datum' ).val() );

							if ( herstartDatum.getTime() < ( pauzeDatum.getTime() + minPauze * dag ) ) {
								herstartDatum.setDate( pauzeDatum.getTime() + minPauze * dag );
								$( '#kleistad_herstart_datum' ).datepicker( 'setDate', herstartDatum );
							}
							$( '#kleistad_herstart_datum' ).datepicker( 'option',
								{
									minDate: new Date( pauzeDatum.getTime() + minPauze * dag ),
									maxDate: new Date( pauzeDatum.getTime() + maxPauze * dag )
								}
							);
						}
					}
				);
			}

			if ( $( '#kleistad_herstart_datum' ).hasClass( 'kleistad-datum' ) ) {
				$( '#kleistad_herstart_datum' ).datepicker( 'option',
					{
						minDate: new Date( strtodate( $( '#kleistad_pauze_datum' ).val() ).getTime() + minPauze * dag ),
						maxDate: new Date( strtodate( $( '#kleistad_pauze_datum' ).val() ).getTime() + maxPauze * dag )
					}
				);
			}

			$( '.kleistad-shortcode' )
			.on( 'click', '#kleistad_abo_pauze',
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_pauze' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt pauzeren of de pauze wilt aanpassen ?' );
                }
			)
			.on( 'click', '#kleistad_abo_einde',
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_einde' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt beëindigen ?' );
                }
			)
			.on( 'click', '#kleistad_abo_wijziging',
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_wijziging' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt wijzigen ?' );
                }
			)
			.on( 'click', '#kleistad_abo_extras',
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_extras' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je de extras van jouw abonnement wilt wijzigen ?' );
                }
			)
			.on( 'click', '#kleistad_abo_dag',
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_dag' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je de werkdag van jouw beperkt abonnement wilt wijzigen ?' );
                }
			)
			.on( 'click', '#kleistad_abo_betaalwijze',
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_betaalwijze' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je de betaalwijze van jouw abonnement wilt wijzigen ?' );
                }
            );
        }
    );

} )( jQuery );
