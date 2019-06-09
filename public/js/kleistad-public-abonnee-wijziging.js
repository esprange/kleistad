( function( $ ) {
	'use strict';

    $( document ).ready(
        function() {

            $( '#kleistad_abo_pauze' ).click(
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_pauze' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt pauzeren ?' );
                }
            );

            $( '#kleistad_abo_einde' ).click(
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_einde' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt beëindigen ?' );
                }
            );

            $( '#kleistad_abo_start' ).click(
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_start' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt hervatten ?' );
                }
            );

            $( '#kleistad_abo_wijziging' ).click(
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_wijziging' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je jouw abonnement wilt wijzigen ?' );
                }
            );

            $( '#kleistad_abo_extras' ).click(
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_extras' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je de extras van jouw abonnement wilt wijzigen ?' );
                }
            );

            $( '#kleistad_abo_dag' ).click(
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_dag' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je de werkdag van jouw beperkt abonnement wilt wijzigen ?' );
                }
            );

			$( '#kleistad_abo_betaalwijze' ).click(
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_betaalwijze' ).toggle( this.checked );
					$( '#kleistad_submit_abonnee_wijziging' ).data( 'confirm', 'Abonnement wijzigen|Weet je zeker dat je de betaalwijze van jouw abonnement wilt wijzigen ?' );
                }
            );
        }
    );

} )( jQuery );
