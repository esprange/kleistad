/**
 * Docent Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  7.0.0
 * @package Kleistad
 */

/* global kleistadData */

( function( $ ) {
	'use strict';

	let $planning = $( '#kleistad_planning' ),
		$wachten  = $( '#kleistad_wachten' );

	/**
	 * Haal de inhoud van de tabel met reserveringen bij de server op.
	 *
	 * @param {string} datum
	 */
	function toonPlanning( datum ) {
		$wachten.addClass( 'kleistad-wachten' ).show();
		$.ajax(
			{
				url: kleistadData.base_url + '/docent_planning/',
				method: 'GET',
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				data: {
					datum: datum
				}
			}
		).done(
			function( data ) {
				$wachten.removeClass( 'kleistad-wachten' );
				$planning.html( data.planning );
			}
		).fail(
			function( jqXHR ) {
				$wachten.removeClass( 'kleistad-wachten' );
				if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
					window.alert( jqXHR.responseJSON.message );
					return;
				}
				window.alert( kleistadData.error_message );
			}
		);
	}

	/**
	 * Wijzig of verwijder de reservering in de server.
	 *
	 * @param {String}  datum          de datum.
	 * @param {Boolean} defaultOpslaan de defaults opslaan of de huidige planning.
	 * @returns {undefined}
	 */
	function muteerPlanning( datum, defaultOpslaan ) {
		let planning = [];
		$( '.planning:checkbox:checked' ).each(
			function() {
				planning.push(
					{
						datum:   $( this ).data( 'datum' ),
						dagdeel: $( this ).data( 'dagdeel' ),
						status:  1
					}
				);
			}
		)
		$( '.planning:checkbox:not(:checked)' ).each(
			function() {
				planning.push(
					{
						datum:   $( this ).data( 'datum' ),
						dagdeel: $( this ).data( 'dagdeel' ),
						status:  0
					}
				);
			}
		)
		$wachten.addClass( 'kleistad-wachten' ).show();
		$.ajax(
			{
				url: kleistadData.base_url + '/docent_planning/',
				method: defaultOpslaan ? 'POST' : 'PUT',
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				data: {
					datum:    datum,
					planning: planning,
				}
			}
		).done(
			function( data ) {
				$wachten.removeClass( 'kleistad-wachten' );
				$planning.html( data.planning );
			}
		).fail(
			function( jqXHR ) {
				$wachten.removeClass( 'kleistad-wachten' );
				if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
					window.alert( jqXHR.responseJSON.message );
					return;
				}
				window.alert( kleistadData.error_message );
			}
		);
	}

	/**
	 * Document ready.
	 */
	$(
		function()
		{
			let $datum = $( '#kleistad_plandatum' );

			if ( window.navigator.userAgent === 'msie' ) {
				$planning.hide();
				$( '#kleistad_geen_ie' ).show();
			}

			$datum.datepicker(
				'option',
				{
					minDate: 0,
					defaultDate: 0
				}
			);
			toonPlanning( $datum.val() );

			$( '.kleistad-shortcode' )
			.on(
				'change',
				'#kleistad_plandatum',
				function() {
					$( this ).datepicker( 'hide' );
					toonPlanning( $datum.val(), );
				}
			)
			.on(
				'click',
				'#kleistad_kalender',
				function() {
					$datum.datepicker( 'show' );
				}
			)
			.on(
				'click',
				'#kleistad_eerder',
				function() {
					let datum = $datum.datepicker( 'getDate' );
					$datum.datepicker( 'setDate', new Date( datum.getFullYear(), datum.getMonth(), datum.getDate() - 7 ) );
					toonPlanning( $datum.val() );
				}
			)
			.on(
				'click',
				'#kleistad_later',
				function() {
					let datum = $datum.datepicker( 'getDate' );
					$datum.datepicker( 'setDate', new Date( datum.getFullYear(), datum.getMonth(), datum.getDate() + 7 ) );
					toonPlanning( $datum.val() );
				}
			)
			.on(
				'click',
				'#kleistad_bewaren',
				function() {
					muteerPlanning( $datum.val(), false );
				}
			)
			.on(
				'click',
				'#kleistad_default',
				function() {
					muteerPlanning( $datum.val(), true );
				}
			);
		}
	);

} )( jQuery );
