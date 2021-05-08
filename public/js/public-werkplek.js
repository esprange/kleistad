/* global kleistadData, navigator */

( function( $ ) {
    'use strict';

	var datums       = $( '#kleistad_werkplek' ).data( 'datums' );
	var gebruiker_id = $( '#kleistad_werkplek' ).data( 'id' );
	var datumIndex   = 0;

	/**
     * Haal de inhoud van de tabel met reserveringen bij de server op.
     */
    function toonWerkplek( datum, id ) {
		$( '#kleistad_wachten' ).addClass( 'kleistad-wachten' ).show();
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
				$( '#kleistad_wachten' ).removeClass( 'kleistad-wachten' );
				$( '#kleistad_datum_titel' ).text( data.datum);
				$( '#kleistad_werkplek' ).html( data.content );
            }
        ).fail(
			function( jqXHR ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad-wachten' );
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
		$( '#kleistad_wachten' ).addClass( 'kleistad-wachten' ).show();
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
				$( '#kleistad_wachten' ).removeClass( 'kleistad-wachten' );
				$( '#kleistad_werkplek' ).html( data.content );
            }
        ).fail(
			function( jqXHR ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad-wachten' );
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
		$( '#kleistad_wachten' ).addClass( 'kleistad-wachten' ).show();
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
				$( '#kleistad_wachten' ).removeClass( 'kleistad-wachten' );
				$( '.kleistad-meester[data-dagdeel=' + data.dagdeel + ']' ).val( data.id ).text( data.naam );
			}
        ).fail(
			function( jqXHR ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad-wachten' );
				if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
					window.alert( jqXHR.responseJSON.message );
					return;
				}
				window.alert( kleistadData.error_message );
			}
		);
	}

	function buttonsActive() {
		$( '#kleistad_eerder' ).prop( 'disabled', 0 === datumIndex );
		$( '#kleistad_later' ).prop('disabled', datums.length === datumIndex + 1 );
	}

	function onLoad() {
		$( '#kleistad_datum' ).datepicker( 'option',
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

	$( document ).ajaxComplete(
        function() {
			onLoad();
		}
	);

	$( function() 
		{
			if ( navigator.appName === 'Microsoft Internet Explorer' || !!( navigator.userAgent.match(/Trident/) || navigator.userAgent.match(/rv:11/)) || (typeof $.browser !== 'undefined' && $.browser.msie === 1 ) ) {
				$( '#kleistad_werkplek' ).hide();
				$( '#kleistad_geen_ie').show();
			}
			onLoad();

			$( '#kleistad_meester' ).dialog(
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
								var datum   = $.datepicker.formatDate( 'dd-mm-yy',  $( '#kleistad_datum').datepicker( 'getDate' ) );
								var id      = $( '#kleistad_meester_selectie' ).val();
								var dagdeel = $( '#kleistad_meester_selectie' ).data( 'dagdeel' );
								muteerMeester( datum, id, dagdeel );
								$( this ).dialog( 'close' );
							},
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
								var datum    = $.datepicker.formatDate( 'dd-mm-yy',  $( '#kleistad_datum' ).datepicker( 'getDate' ) );
								gebruiker_id = $( '#kleistad_gebruiker_selectie' ).val();
								$( '#kleistad_wijzig_gebruiker' ).text( $( '#kleistad_gebruiker_selectie option:selected' ).text() );
								toonWerkplek( datum, gebruiker_id );
								$( this ).dialog( 'close' );
							},
						}
					]
				}
			);

			/**
             * Toon de tabel. 
             */
			if ( 'undefined' !== typeof datums ) {
				buttonsActive();
				$( '#kleistad_datum' ).datepicker( 'setDate', datums[datumIndex] );
				toonWerkplek( datums[datumIndex], gebruiker_id );
			}

			$( '.kleistad-shortcode' )
			.on( 'change', '#kleistad_datum',
				function() {
					var datum  = $.datepicker.formatDate( 'dd-mm-yy', $( this ).datepicker( 'getDate' ) );
					datumIndex = $.inArray( datum, datums );
					buttonsActive();
					$( this ).datepicker( 'setDate', datums[datumIndex] );
					$( this ).datepicker( 'hide' );
					toonWerkplek( datum, gebruiker_id );
				}
			)
			.on( 'click', '#kleistad_eerder',
				function() {
					datumIndex--;
					buttonsActive();
					$( '#kleistad_datum' ).datepicker( 'setDate', datums[datumIndex] );
					toonWerkplek( datums[datumIndex], gebruiker_id );
				}
			)
			.on( 'click', '#kleistad_later', 
				function() {
					datumIndex++;
					buttonsActive();
					$( '#kleistad_datum' ).datepicker( 'setDate', datums[datumIndex] );
					toonWerkplek( datums[datumIndex], gebruiker_id );
				}
			)
			.on( 'click', '.kleistad-werkplek',
				function() {
					var method = ( 'reserveren' === $( this ).text() ) ? 'POST' : 'DELETE';
					var datum  = $.datepicker.formatDate( 'dd-mm-yy',  $( '#kleistad_datum').datepicker( 'getDate' ) );
					muteerWerkplek( method, datum, $( this ).val(), $( this ).data( 'dagdeel' ), $( this ).data( 'activiteit' ) );
				}
			)
			.on( 'click', '.kleistad-meester',
				function() {
					$( '#kleistad_meester_selectie' ).val( $( this ).val() );
					$( '#kleistad_meester_selectie' ).data( 'dagdeel', $( this ).data( 'dagdeel' ) );
					$( '#kleistad_meester' ).dialog( 'option', 'title', 'Beheerder voor ' + $( this ).data( 'dagdeel' ).toLowerCase() ).dialog( 'open' );
				}
			)
			.on( 'click', '#kleistad_kalender',
				function() {
					$( '#kleistad_datum' ).datepicker( 'show' );
				}
			)
			.on( 'click', '#kleistad_wijzig_gebruiker',
				function() {
					$( '#kleistad_gebruiker' ).dialog( 'open' );
				}
			 );

        }
    );

} )( jQuery );
