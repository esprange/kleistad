/* global kleistadData,FullCalendar */

( function( $ ) {
    'use strict';

    $( document ).ready(
		function() {

			var calendarEl = document.getElementById( 'kleistad_fullcalendar' );
			var calendar = new FullCalendar.Calendar( calendarEl, {
				plugins: [ 'dayGrid', 'timeGrid' ],
				defaultView: 'dayGridMonth',
				locales: [ 'nl' ],
				locale: 'nl',
				header: {
					left: 'dayGridMonth, timeGridWeek, timeGridDay',
					center: 'title',
					right: 'today prev,next'
				},
				eventLimit: true,
				navLinks: true,
				buttonIcons: true,
				weekNumbers: true,
				fixedWeekCount: false,
				allDaySlot: false,
				minTime: '08:00:00',
				scrollTime: '09:00:00',
				eventRender: function( info ) {
					var tekst = '';
					if ( 'timeGridDay' === info.view.type ) {
						tekst += '<div class="kleistad_row"><div class="kleistad_col_3">' + info.event.extendedProps.naam + '</div></div>';
						if ( 'undefined' !== typeof( info.event.extendedProps.docent ) ) {
							tekst += '<div class="kleistad_row"><div class="kleistad_col_1">Docent</div><div class="kleistad_col_2">' + info.event.extendedProps.docent + '</div></div>';
						}
						tekst += '<div class="kleistad_row"><div class="kleistad_col_1">Aantal</div><div class="kleistad_col_2">' + info.event.extendedProps.aantal + '</div></div>';
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
