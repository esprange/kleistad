/**
 * Werkplek Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

/* global kleistadData */

( function( $ ) {
	'use strict';

	var $werkplek         = $( '#kleistad_werkplek' ),
		$wachten          = $( '#kleistad_wachten' ),
		$meester_selectie = $( '#kleistad_meester_selectie' ),
		$meester          = $( '#kleistad_meester' ),
		$datum            = $( '#kleistad_datum' ),
		datums            = $werkplek.data( 'datums' ),
		gebruiker_id      = $werkplek.data( 'id' ),
		datumIndex        = 0;

	/**
	 * Haal de inhoud van de tabel met reserveringen bij de server op.
	 */
	function toonWerkplek( datum, id ) {
		$wachten.addClass( 'kleistad-wachten' ).show();
		$.ajax(
			{
				url: kleistadData.base_url + '/werkplek/',
				method: 'GET',
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				data: {
					id:    id,
					datum: datum
				}
			}
		).done(
			function( data ) {
				$wachten.removeClass( 'kleistad-wachten' );
				$( '#kleistad_datum_titel' ).text( data.datum );
				$werkplek.html( data.content );
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
	 * @param {String} method post of delete.
	 * @param {String} datum.
	 * @param {int}    id, het gebruiker id.
	 * @param {String} dagdeel, het dagdeel.
	 * @param {String} activiteit, de activiteit.
	 * @returns {undefined}
	 */
	function muteerWerkplek( method, datum, id, dagdeel, activiteit ) {
		$wachten.addClass( 'kleistad-wachten' ).show();
		$.ajax(
			{
				url: kleistadData.base_url + '/werkplek/',
				method: method,
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				data: {
					id:         id,
					datum:      datum,
					dagdeel:    dagdeel,
					activiteit: activiteit
				}
			}
		).done(
			function( data ) {
				$wachten.removeClass( 'kleistad-wachten' );
				$werkplek.html( data.content );
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
	 * @param {String} datum.
	 * @param {int}    id, het meester id.
	 * @param {String} dagdeel, het dagdeel.
	 * @returns {undefined}
	 */
	function muteerMeester( datum, id, dagdeel ) {
		$wachten.addClass( 'kleistad-wachten' ).show();
		$.ajax(
			{
				url: kleistadData.base_url + '/meester/',
				method: 'POST',
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				data: {
					id:         id,
					datum:      datum,
					dagdeel:    dagdeel
				}
			}
		).done(
			function( data ) {
				$wachten.removeClass( 'kleistad-wachten' );
				$( '.kleistad-meester[data-dagdeel=' + data.dagdeel + ']' ).val( data.id ).text( data.naam );
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
	 * Activeer/deactiveer forward en backward buttons.
	 */
	function buttonsActive() {
		$( '#kleistad_eerder' ).prop( 'disabled', 0 === datumIndex );
		$( '#kleistad_later' ).prop( 'disabled', datums.length === datumIndex + 1 );
	}

	/**
	 * Refresh form.
	 */
	function onLoad() {
		$datum.datepicker(
			'option',
			{
				beforeShowDay: function( datum ) {
					var fDate   = $.datepicker.formatDate( 'dd-mm-yy', datum );
					var gotDate = $.inArray( fDate, datums );
					if ( gotDate >= 0 ) {
						return [ true, 'kleistad-state-highlight' ];
					}
					return [ false, '' ];
				}
			}
		);
	}

	/**
	 * Na ajax return
	 */
	$( document ).ajaxComplete(
		function() {
			onLoad();
		}
	);

	/**
	 * Document ready.
	 */
	$(
		function()
		{
			if ( window.navigator.userAgent === 'msie' ) {
				$werkplek.hide();
				$( '#kleistad_geen_ie' ).show();
			}
			onLoad();

			$meester.dialog(
				{
					autoOpen: false,
					height: 'auto',
					width: 360,
					modal: true,
					open: function() {
						$( '.ui-button' ).addClass( 'kleistad-button' ).removeClass( 'ui-button' );
					},
					buttons: [
					{
						text: 'OK',
						click: function() {
							var datum   = $.datepicker.formatDate( 'dd-mm-yy', $datum.datepicker( 'getDate' ) );
							var id      = $meester_selectie.val();
							var dagdeel = $meester_selectie.data( 'dagdeel' );
							muteerMeester( datum, id, dagdeel );
							$( this ).dialog( 'close' );
						}
					}
					]
				}
			);

			$( '#kleistad_gebruiker' ).dialog(
				{
					autoOpen: false,
					height:	  'auto',
					width:    360,
					modal:    true,
					open: function() {
						$( '.ui-button' ).addClass( 'kleistad-button' ).removeClass( 'ui-button' );
					},
					buttons: [
					{
						text: 'OK',
						click: function () {
							var datum    = $.datepicker.formatDate( 'dd-mm-yy',  $datum.datepicker( 'getDate' ) );
							gebruiker_id = $( '#kleistad_gebruiker_selectie' ).val();
							$( '#kleistad_wijzig_gebruiker' ).text( $( '#kleistad_gebruiker_selectie option:selected' ).text() );
							toonWerkplek( datum, gebruiker_id );
							$( this ).dialog( 'close' );
						}
					}
					]
				}
			);

			/**
			 * Toon de tabel.
			 */
			if ( 'undefined' !== typeof datums ) {
				buttonsActive();
				$datum.datepicker( 'setDate', datums[datumIndex] );
				toonWerkplek( datums[datumIndex], gebruiker_id );
			}

			$( '.kleistad-shortcode' )
			.on(
				'change',
				'#kleistad_datum',
				function() {
					var datum  = $.datepicker.formatDate( 'dd-mm-yy', $( this ).datepicker( 'getDate' ) );
					datumIndex = $.inArray( datum, datums );
					buttonsActive();
					$( this ).datepicker( 'setDate', datums[datumIndex] );
					$( this ).datepicker( 'hide' );
					toonWerkplek( datum, gebruiker_id );
				}
			)
			.on(
				'click',
				'#kleistad_eerder',
				function() {
					datumIndex--;
					buttonsActive();
					$datum.datepicker( 'setDate', datums[datumIndex] );
					toonWerkplek( datums[datumIndex], gebruiker_id );
				}
			)
			.on(
				'click',
				'#kleistad_later',
				function() {
					datumIndex++;
					buttonsActive();
					$datum.datepicker( 'setDate', datums[datumIndex] );
					toonWerkplek( datums[datumIndex], gebruiker_id );
				}
			)
			.on(
				'click',
				'.kleistad-werkplek',
				function() {
					var method = ( 'reserveren' === $( this ).text() ) ? 'POST' : 'DELETE';
					var datum  = $.datepicker.formatDate( 'dd-mm-yy',  $datum.datepicker( 'getDate' ) );
					muteerWerkplek( method, datum, $( this ).val(), $( this ).data( 'dagdeel' ), $( this ).data( 'activiteit' ) );
				}
			)
			.on(
				'click',
				'.kleistad-meester',
				function() {
					$meester_selectie.val( $( this ).val() );
					$meester_selectie.data( 'dagdeel', $( this ).data( 'dagdeel' ) );
					$meester.dialog( 'option', 'title', 'Beheerder voor ' + $( this ).data( 'dagdeel' ).toLowerCase() ).dialog( 'open' );
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
				'#kleistad_wijzig_gebruiker',
				function() {
					$( '#kleistad_gebruiker' ).dialog( 'open' );
				}
			);
		}
	);

} )( jQuery );
