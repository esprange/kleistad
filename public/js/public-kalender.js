/* global kleistadData,FullCalendar */

( function( $ ) {
    'use strict';

    $( document ).ready(
		function() {

			var calendarEl = document.getElementById( 'kleistad_fullcalendar' );
			var calendar = new FullCalendar.Calendar( calendarEl, {
				initialView: 'dayGridMonth',
				locales: [ 'nl' ],
				locale: 'nl',
				headerToolbar: {
					left: 'dayGridMonth, timeGridWeek, timeGridDay',
					center: 'title',
					right: 'today prev,next'
				},
				height: 'auto',
				dayMaxEventRows: true,
				navLinks: true,
				buttonIcons: true,
				weekNumbers: true,
				weekNumberFormat: { week: 'numeric' },
				fixedWeekCount: false,
				allDaySlot: false,
				slotMinTime: '08:00:00',
				scrollTime: '09:00:00',
				eventContent: function( info ) {
					var tekst =
						'<div class="fc-event-main-frame ' + info.className + '">' + 
							'<div class="fc-event-time">' + info.timeText + '</div>' +
							'<div class="fc-event-title-container">' +
								'<div class="fc-event-title fc-sticky">' + info.event.title + '</div>';
					switch ( info.view.type ) {
						case 'timeGridDay':
							if ( 'undefined' !== typeof( info.event.extendedProps.naam ) ) {
								tekst += info.event.extendedProps.naam;
								tekst += '<br/>Docent :' + info.event.extendedProps.docent;
								tekst += '<br/>Aantal :' + info.event.extendedProps.aantal;
								if ( ( '' !== info.event.extendedProps.technieken ) ) {
									tekst +=  '<br/>' + info.event.extendedProps.technieken;
								}
							}
							/* falls through */
						case 'timeGridWeek':
							tekst += '</div></div>';
							return { html: tekst };
						default:
							break;
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
						function( jqXHR ) {
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
		}
    );

} )( jQuery );
