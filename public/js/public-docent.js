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

	const $planning = $( '#kleistad_planning' ),
		$overzicht  = $( '#kleistad_overzicht' ),
		$wachten    = $( '#kleistad_wachten' );

	/**
	 * Haal de inhoud van de tabel met reserveringen bij de server op.
	 *
	 * @param {string} datum
	 */
	function toonTabel( datum ) {
		let actie = ( $planning[0] ) ? 'planning' : 'overzicht';
		$wachten.addClass( 'kleistad-wachten' ).show();
		$.ajax(
			{
				url: kleistadData.base_url + '/docent_' + actie + '/',
				method: 'GET',
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				data: {
					datum: datum,
					actie: actie
				}
			}
		).done(
			function( data ) {
				$wachten.removeClass( 'kleistad-wachten' );
				if ( 'planning' === actie ) {
					$planning.html( data.content );
				}
				if ( 'overzicht' === actie ) {
					$overzicht.html( data.content );
				}
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
		const planning = [];
		$( 'input[name=planning]:checkbox:checked' ).each(
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
		$( 'input[name=planning]:checkbox:not(:checked)' ).each(
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
		function() {
			const $datum = $( '#kleistad_plandatum' );

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
			toonTabel( $datum.val() );

			$( '.kleistad-shortcode' )
			.on(
				'change',
				'#kleistad_plandatum',
				function() {
					$( this ).datepicker( 'hide' );
					toonTabel( $datum.val(), );
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
					const datum = $datum.datepicker( 'getDate' );
					$datum.datepicker( 'setDate', new Date( datum.getFullYear(), datum.getMonth(), datum.getDate() - 7 ) );
					toonTabel( $datum.val() );
				}
			)
			.on(
				'click',
				'#kleistad_later',
				function() {
					const datum = $datum.datepicker( 'getDate' );
					$datum.datepicker( 'setDate', new Date( datum.getFullYear(), datum.getMonth(), datum.getDate() + 7 ) );
					toonTabel( $datum.val() );
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
					let confirm = $( this ).data( 'confirm' );
					$().askConfirm(
						confirm.split( '|' ),
						function() {
							muteerPlanning( $datum.val(), true )
						}
					);
				}
			);
		}
	);

} )( jQuery );
