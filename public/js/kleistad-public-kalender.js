/* global kleistadData */

( function( $ ) {
    'use strict';

	function kalender( dag, maand, jaar, modus ) {

		$.ajax(
			{
				url: kleistadData.base_url + '/kalender/',
				method: 'POST',
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				data: {
					dag:    dag,
					maand:  maand,
					jaar:   jaar,
					modus:  modus
				}
			}
		).done(
			function( data ) {
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
				var dag   = table.data( 'dag' ),
					maand = table.data( 'maand' ),
					jaar  = table.data( 'jaar' ),
					modus = table.data( 'modus' );
				kalender( dag, maand, jaar, modus );
			})();

			/**
			 * Volgende maand.
			 */
            $( 'body' ).on(
                'click', '#kleistad_next', function() {
					var table = $( '#kleistad_kalender' );
					var dag   = table.data( 'dag' ),
						maand = table.data( 'maand' ) + 1,
						jaar  = table.data( 'jaar' ),
						modus = table.data( 'modus' );
						if ( 12 < maand ) {
						jaar++;
						maand = 1;
					}
					table.data( 'maand', maand );
					table.data( 'jaar', jaar );
					kalender( dag, maand, jaar, modus );
				}
			);

			/**
			 * Vorige maand.
			 */
            $( 'body' ).on(
                'click', '#kleistad_prev', function() {
					var table = $( '#kleistad_kalender' );
					var dag   = table.data( 'dag' ),
						maand = table.data( 'maand' ) - 1,
						jaar  = table.data( 'jaar' ),
						modus = table.data( 'modus' );
					if ( 1 > maand ) {
						jaar--;
						maand = 12;
					}
					table.data( 'maand', maand );
					table.data( 'jaar', jaar );
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
                    width: 500,
                    modal: true
                }
            );

			/**
             * Verander de opmaak bij hover
             */
            $( 'body' ).on(
                'hover', '.kleistad_event_info, .kleistad_maand', function() {
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
                    $( '#kleistad_event' ).dialog( 'option', 'title', event.naam ).dialog( 'open' );
					$( '#kleistad_event' ).html(
						'<table class="kleistad_form" >' +
						'<tr><th>Code</th><td>' + event.code + '</td></tr>' +
						'<tr><th>Docent</th><td>' + event.docent + '</td></tr>' +
						'<tr><th>Start</th><td>' + event.start + '</td></tr>' +
						'<tr><th>Eind</th><td>' + event.eind + '</td></tr>' +
						'<tr><th>Aantal</th><td>' + event.aantal + '</td></tr>' +
						'<tr><th>Technieken</th><td>' + event.technieken + '</td></tr>' +
						'</table>' );
                }
            );
        }
    );

} )( jQuery );
