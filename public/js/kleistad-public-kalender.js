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
					// var start = new Date( info.event.start);
					if ( 'agendaDay' === info.view.type ) {
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
					if ( 'month' === info.view.type ) {
					//	info.el.text = start.getHours() + ':' + ( 10 > start.getMinutes() ? '0' : '' ) + start.getMinutes() + ' ' + info.event.title;
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
