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
					var tekst;
					if ( 'agendaDay' === info.view.type ) {
						tekst = '<table class="kleistad_form">';
						if ( 'undefined' !== typeof( info.event.extendedProps.code ) ) {
							tekst += '<tr><td>Code</td><td>' + info.event.extendedProps.code + '</td></tr>';
						}
						if ( 'undefined' !== typeof( info.event.extendedProps.docent ) ) {
							tekst += '<tr><td>Docent</td><td>' + info.event.extendedProps.docent + '</td></tr>';
						}
						if ( 'undefined' !== typeof( info.event.extendedProps.aantal ) ) {
							tekst += '<tr><td>Aantal</td><td>' + info.event.extendedProps.aantal + '</td></tr>';
						}
						if ( 'undefined' !== typeof( info.event.extendedProps.technieken && '' !== info.event.extendedProps.technieken ) ) {
							tekst += '<tr><td>Technieken</td><td>' + info.event.extendedProps.technieken + '</td></tr>';
						}
						info.el.innerHTML += tekst + '</table>';
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
        }
    );

} )( jQuery );
