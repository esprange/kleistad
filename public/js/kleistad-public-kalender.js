/* global kleistadData */

( function( $ ) {
    'use strict';

	function kalender( datum, modus ) {

		$.ajax(
			{
				url: kleistadData.base_url + '/kalender/',
				method: 'POST',
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				data: {
					dag:   datum.dag,
					maand: datum.maand,
					jaar:  datum.jaar,
					modus: modus,
				}
			}
		).done(
			function( data ) {
				var table = $( '#kleistad_kalender' );
				table.data( 'datum', {
					dag :  data.dag,
					maand: data.maand,
					jaar:  data.jaar
				});
				$( '#kleistad_kalender' ).html( data.html );
			}
		).fail(
			/* jshint unused:vars */
			function( jqXHR, textStatus, errorThrown ) {
				if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
					window.alert( jqXHR.responseJSON.message );
					return;
				}
				window.alert( kleistadData.error_message );
			}
		);
	}

    $( document ).ready(
        function() {

			/**
			 * Self invoke de kalender functie.
			 */
			( function() {
				var table = $( '#kleistad_kalender' );
				var datum = table.data( 'datum' ),
					modus = table.data( 'modus' );
				kalender( datum, modus );
			})();

			/**
			 * Volgende maand of dag.
			 */
            $( 'body' ).on(
                'click', '#kleistad_next', function() {
					var table = $( '#kleistad_kalender' );
					var datum = table.data( 'datum' ),
						modus = table.data( 'modus' );
					if ( 'maand' === modus ) {
						datum.maand++;
					} else if ( 'dag' === modus ) {
						datum.dag++;
					}
					table.data( 'datum', datum );
					kalender( datum, modus );
				}
			);

			/**
			 * Vorige maand of dag.
			 */
            $( 'body' ).on(
                'click', '#kleistad_prev', function() {
					var table = $( '#kleistad_kalender' );
					var datum = table.data( 'datum' ),
						modus = table.data( 'modus' );
					if ( 'maand' === modus ) {
						datum.maand--;
					} else if ( 'dag' === modus ) {
						datum.dag--;
					}
					table.data( 'datum', datum );
					kalender( datum, modus );
				}
			);

			/**
			 * Keuze dag.
			 */
			$( 'body' ).on(
				'click', '.kleistad_dag', function() {
					var table = $( '#kleistad_kalender' );
					var dag   = $( this ).html(),
					    maand = table.data( 'maand' ),
					    jaar  = table.data( 'jaar' ),
					    modus = 'dag';
					table.data( 'dag', dag );
					table.data( 'modus', modus );
					kalender( dag, maand, jaar, modus );
				}
			);

			/**
			 * Keuze maand.
			 */
			$( 'body' ).on(
				'click', '.kleistad_maand', function() {
					var table = $( '#kleistad_kalender' );
					var dag   = 1,
					    maand = table.data( 'maand' ),
					    jaar  = table.data( 'jaar' ),
					    modus = 'maand';
					table.data( 'dag', dag );
					table.data( 'modus', modus );
					kalender( dag, maand, jaar, modus );
				}
			);

			/**
             * Definieer de popup dialoog
             */
            $( '#kleistad_event' ).dialog(
                {
                    autoOpen: false,
                    height: 'auto',
                    width: '300',
                    modal: true
                }
            );

			/**
             * Verander de opmaak bij hover
             */
            $( 'body' ).on(
                'hover', '.kleistad_event_info, .kleistad_maand, .kleistad_dag, #kleistad_prev, #kleistad_next', function() {
                    $( this ).css( 'cursor', 'pointer' );
                    $( this ).toggleClass( 'kleistad_hover' );
                }
            );

			/**
             * Toon de details van het geselecteerde event.
             */
            $( 'body' ).on(
                'click', '.kleistad_event_info', function() {
					var event = $( this ).data( 'event' );
					var tekst = '';
                    $( '#kleistad_event' ).dialog( 'option', 'title', event.naam ).dialog( 'open' );
					if ( 'undefined' !== typeof( event.code ) ) {
						tekst += '<tr><th>Code</th><td>' + event.code + '</td></tr>';
					}
					if ( 'undefined' !== typeof( event.docent ) ) {
						tekst += '<tr><th>Docent</th><td>' + event.docent + '</td></tr>';
					}
					tekst += '<tr><th>Start</th><td>' + event.start + '</td></tr>';
					tekst += '<tr><th>Eind</th><td>' + event.eind + '</td></tr>';
					if ( 'undefined' !== typeof( event.aantal ) ) {
						tekst += '<tr><th>Aantal</th><td>' + event.aantal + '</td></tr>';
					}
					if ( 'undefined' !== typeof( event.technieken && '' !== event.technieken ) ) {
						tekst += '<tr><th>Technieken</th><td>' + event.technieken + '</td></tr>';
					}
					$( '#kleistad_event' ).html( '<table class="kleistad_form" >' + tekst + '</table>' );
                }
            );
        }
    );

} )( jQuery );
