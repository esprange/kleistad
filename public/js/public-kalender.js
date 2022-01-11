/**
 * Kalender Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

/* global kleistadData,FullCalendar */

( function( $ ) {
	'use strict';

	/**
	 * Document ready.
	 */
	$(
		function()
		{

			let calendarEl = document.getElementById( 'kleistad_fullcalendar' );
			// noinspection JSUnusedGlobalSymbols .
			let calendar = new FullCalendar.Calendar(
				calendarEl,
				{
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
					eventContent:
						/**
						 * Anonieme functie
						 *
						 * @param info
						 * @param {String} info.event.extendedProps.docent
						 * @param {integer} info.event.extendedProps.aantal
						 * @param {String} info.event.extendedProps.technieken
						 * @returns {{html: string}|undefined}
						 */
						function( info ) {
							let tekst =
								'<div class="fc-event-main-frame">' +
								'<div class="fc-event-time" >' + info.timeText + '</div>' +
								'<div class="fc-event-title-container">' +
								'<div class="fc-event-title fc-sticky">' + info.event.title + '</div>';
							switch ( info.view.type ) {
								case 'timeGridDay':
									if ( 'undefined' !== typeof( info.event.extendedProps.naam ) ) {
										tekst += 'Docent :' + info.event.extendedProps.docent;
										tekst += '<br/>Aantal :' + info.event.extendedProps.aantal;
										if ( ( '' !== info.event.extendedProps.technieken ) ) {
											tekst += '<br/>' + info.event.extendedProps.technieken;
										}
									}
									return { html: tekst + '</div></div>' };
								case 'timeGridWeek':
									return { html: tekst + '</div></div>' };
								default:
									return undefined;
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
				}
			);
			calendar.render();
		}
	);

} )( jQuery );
