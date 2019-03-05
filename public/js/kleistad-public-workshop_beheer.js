/* global detectTap */

( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {
            /**
             * Definieer de popup dialoog
             */
            $( '#kleistad_workshop' ).dialog(
                {
                    autoOpen: false,
                    height: 'auto',
                    width: 750,
                    modal: true
                }
            );

            /**
             * Verander de opmaak bij hover
             */
            $( 'body' ).on(
                'hover', '.kleistad_workshop_info', function() {
                    $( this ).css( 'cursor', 'pointer' );
                    $( this ).toggleClass( 'kleistad_hover' );
                }
            );

            /**
             * Toon de details van het geselecteerde workshop.
             */
            $( 'body' ).on(
                'click touchend', '.kleistad_workshop_info', function( event ) {
					var workshop;
					if ( 'click' === event.type || detectTap ) {
						workshop = $( this ).data( 'workshop' );
						$( '#kleistad_workshop' ).dialog( 'option', 'title', 'W' + workshop.id + ' ' + workshop.naam ).dialog( 'open' );
						$( '#kleistad_id' ).val( workshop.id );
						$( '#kleistad_naam' ).val( workshop.naam );
						$( '#kleistad_docent' ).val( workshop.docent );
						$( '#kleistad_datum' ).val( workshop.datum );
						$( '#kleistad_start_tijd' ).val( workshop.start_tijd );
						$( '#kleistad_eind_tijd' ).val( workshop.eind_tijd );
						$( '#kleistad_aantal' ).val( workshop.aantal );
						$( '#kleistad_kosten' ).val( workshop.kosten );
						$( '#kleistad_email' ).val( workshop.email );
						$( '#kleistad_contact' ).val( workshop.contact );
						$( '#kleistad_telefoon' ).val( workshop.telefoon );
						$( '#kleistad_organisatie' ).val( workshop.organisatie );
						$( '#kleistad_programma' ).val( workshop.programma );
						$( '#kleistad_definitief' ).attr( 'class', ( workshop.definitief > 0 ) ? 'genericon genericon-checkmark' : '' );
						$( '#kleistad_betaald' ).attr( 'class', ( workshop.betaald > 0 ) ? 'genericon genericon-checkmark' : '' );
						$( '#kleistad_draaien' ).prop( 'checked', String( workshop.technieken ).indexOf( 'Draaien' ) >= 0 );
						$( '#kleistad_handvormen' ).prop( 'checked', String( workshop.technieken ).indexOf( 'Handvormen' ) >= 0 );
						$( '#kleistad_boetseren' ).prop( 'checked', String( workshop.technieken ).indexOf( 'Boetseren' ) >= 0 );
						$( '#kleistad_kosten,#kleistad_datum' ).attr( 'readonly', workshop.definitief );
						$( '#kleistad_workshop_bevestigen,#kleistad_workshop_opslaan' ).prop( 'disabled', workshop.betaald || workshop.definitief || workshop.vervallen );
						$( '#kleistad_workshop_afzeggen' ).prop( 'disabled', workshop.vervallen );
					}
				}
            );

            /**
             * Toon een lege popup dialoog voor een nieuwe workshop
             */
            $( 'body' ).on(
                'click', '#kleistad_workshop_toevoegen', function() {
					$( '#kleistad_workshop' ).dialog( 'option', 'title', '*** nieuw ***' ).dialog( 'open' );
					$( '#kleistad_workshop_form' )[0].reset();
					$( '#kleistad_id' ).val( 0 );
					$( '#kleistad_workshop_bevestigen,#kleistad_workshop_opslaan' ).prop( 'disabled', false );
					$( '#kleistad_workshop_afzeggen' ).prop( 'disabled', true );
					$( '#kleistad_definitief,#kleistad_betaald' ).removeAttr( 'class' );
					$( '#kleistad_kosten,#kleistad_datum' ).attr( 'readonly', false );
                }
			);

			$( '#kleistad_sluit' ).click( function() {
				$( '#kleistad_workshop' ).dialog( 'close' );
			});
        }
    );

} )( jQuery );
