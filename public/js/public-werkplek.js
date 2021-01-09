/* global kleistadData, detectTap, navigator */

( function( $ ) {
    'use strict';

	var datums     = $( '#kleistad_werkplek' ).data( 'datums' );
	var datumIndex = 0;

	/**
     * Haal de inhoud van de tabel met reserveringen bij de server op.
     */
    function toonWerkplek( datum ) {
		$( '#kleistad_wachten' ).addClass( 'kleistad_wachten' ).show();
         $.ajax(
            {
                url: kleistadData.base_url + '/werkplek/',
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
				$( '#kleistad_wachten' ).removeClass( 'kleistad_wachten' );
				$( '#kleistad_datum_titel' ).text( data.datum)
				$( '#kleistad_werkplek' ).html( data.content );
            }
        ).fail(
			function( jqXHR ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad_wachten' );
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
     * @param {string} method post, put of delete.
	 * @param (int)    id, het gebruiker id.
	 * @param (string) dagdeel, het dagdeel.
	 * @param (string) activiteit, de activiteit. 
     * @returns {undefined}
     */
    function muteerWerkplek( method, datum, id, dagdeel, activiteit ) {
		$( '#kleistad_wachten' ).addClass( 'kleistad_wachten' ).show();
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
				$( '#kleistad_wachten' ).removeClass( 'kleistad_wachten' );
				$( '#kleistad_werkplek' ).html( data.content );
            }
        ).fail(
			function( jqXHR ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad_wachten' );
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
		$( '.kleistad_werkplek' ).button(
			{
				icon:false
			}
		);
		$( '#kleistad_datum' ).datepicker( 'option',
			{
				beforeShowDay: function( datumTekst ) {
					var fDate   = $.datepicker.formatDate( 'dd-mm-yy', datumTekst );
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

    $( function() {

			if ( navigator.appName === 'Microsoft Internet Explorer' || !!( navigator.userAgent.match(/Trident/) || navigator.userAgent.match(/rv:11/)) || (typeof $.browser !== 'undefined' && $.browser.msie === 1 ) ) {
				$( '#kleistad_werkplek' ).hide();
				$( '#kleistad_geen_ie').show();
			}
			onLoad();

			/**
             * Toon de tabel. 
             */
			if ( 'undefined' !== typeof datums ) {
				buttonsActive();
				$( '#kleistad_datum' ).datepicker( 'setDate', datums[datumIndex] );
				toonWerkplek( datums[datumIndex] );
			}

			$( '.kleistad_shortcode' )
			.on( 'change', '#kleistad_datum',
				function() {
					var datum   = $.datepicker.formatDate( 'dd-mm-yy', $( '#kleistad_datum').datepicker( 'getDate' ) );
					datumIndex  = $.inArray( datum, datums );
					$( '#kleistad_datum' ).datepicker( 'setDate', datums[datumIndex] );
					toonWerkplek( datum );
				}
			)
			.on( 'click', '#kleistad_eerder',
				function() {
					datumIndex--;
					buttonsActive();
					$( '#kleistad_datum' ).datepicker( 'setDate', datums[datumIndex] );
					toonWerkplek( datums[datumIndex] );
				}
			)
			.on( 'click', '#kleistad_later', 
				function() {
					datumIndex++;
					buttonsActive();
					$( '#kleistad_datum' ).datepicker( 'setDate', datums[datumIndex] );
					toonWerkplek( datums[datumIndex] );
				}
			)
			.on( 'change', '[id^=werkplek]',
				function() {
					var method = this.checked ? 'POST' : 'DELETE';
					var datum  = $.datepicker.formatDate( 'dd-mm-yy',  $( '#kleistad_datum').datepicker( 'getDate' ) );
					muteerWerkplek( method, datum, $( this ).val(), $( this ).data( 'dagdeel' ), $( this ).data( 'activiteit' ) );
				}
			);
        }
    );

} )( jQuery );
