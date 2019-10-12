( function( $ ) {
	'use strict';

    $( document ).ready(
        function() {

			$( '.kleistad_shortcode' )
			.on( 'click', '#kleistad_abo_pauze',
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_pauze' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt pauzeren ?' );
                }
			)
			.on( 'click', '#kleistad_abo_einde',
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_einde' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt beëindigen ?' );
                }
			)
			.on( 'click', '#kleistad_abo_start',
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_start' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).prop( 'disabled', false ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt hervatten ?' );
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
