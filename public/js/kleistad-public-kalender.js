/* global kleistadData,FullCalendar */

( function( $ ) {
    'use strict';

    $( document ).ready(
		function() {

			var calendarEl = document.getElementById( 'kleistad_fullcalendar' );
			var calendar = new FullCalendar.Calendar( calendarEl, {
				locale: 'nl',
				header: {
					left: 'month,agendaWeek,agendaDay',
					center: 'title',
					right: 'today prev,next'
				  },
				eventLimit: true,
				dateClick: function( info ) {
					this.changeView( 'agendaDay', info.dateStr );
				},
				minTime: '08:00:00',
				eventRender: function( info ) {
					var tekst = '';
					if ( 'agendaDay' === info.view.type ) {
						// if ( ( 'cursus' === info.event.title ) && ( 'undefined' !== typeof( info.event.extendedProps.naam ) ) && ( '' !== info.event.extendedProps.naam ) ) {
						// 	tekst += '<div class="kleistad_row"><div class="kleistad_col_3">' + info.event.extendedProps.naam + '</div></div>';
						// }
						if ( 'undefined' !== typeof( info.event.extendedProps.code ) ) {
							tekst += '<div class="kleistad_row"><div class="kleistad_col_1">Code</div><div class="kleistad_col_2">' + info.event.extendedProps.code + '</div></div>';
						}
						if ( 'undefined' !== typeof( info.event.extendedProps.docent ) ) {
							tekst += '<div class="kleistad_row"><div class="kleistad_col_1">Docent</div><div class="kleistad_col_2">' + info.event.extendedProps.docent + '</div></div>';
						}
						if ( 'undefined' !== typeof( info.event.extendedProps.aantal ) ) {
							tekst += '<div class="kleistad_row"><div class="kleistad_col_1">Aantal</div><div class="kleistad_col_2">' + info.event.extendedProps.aantal + '</div></div>';
						}
						if ( ( 'undefined' !== typeof( info.event.extendedProps.technieken ) ) && ( '' !== info.event.extendedProps.technieken ) ) {
							tekst += '<div class="kleistad_row"><div class="kleistad_col_1">Techniek</div><div class="kleistad_col_2">' + info.event.extendedProps.technieken + '</div></div>';
						}
						info.el.innerHTML += tekst;
					}
				},
				events: function( info, successCallback, failureCallback ) {
					$.ajax(
						{
							url: kleistadData.base_url + '/kalender/',
							method: 'POST',
							beforeSend: function( xhr ) {
								xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
							},
							data: {
								start: info.start.toISOString(),
								eind:  info.end.toISOString()
							}
						}
					).done(
						function( data ) {
							successCallback( data.events );
						}
					).fail(
						/* jshint unused:vars */
						function( jqXHR, textStatus, errorThrown ) {
							if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
								failureCallback( jqXHR.responseJSON.message );
								return;
							}
							failureCallback( kleistadData.error_message );
						}
					);
				}
			});
			calendar.render();

			/**
			 * Verander de opmaak bij hovering.
			 */
			$( 'body' ).on(
				'hover', '.fc-day-number', function() {
					$( this ).css( 'cursor', 'pointer' );
				}
			);

		}

    );

} )( jQuery );
