/**
 * Generieke Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 */

/* global kleistadData */
/* exported strtodate, strtotime, timetostr, detectTap */

var detectTap;

/**
 * Converteer string naar tijd in minuten
 *
 * @param {String} value
 */
function strtotime( value ) {
	var hours, minutes;
	if ( 'string' === typeof value ) {
		/* jshint eqeqeq:false */
		if ( Number( value ) == value ) {
			return Number( value );
		}
		hours = value.substring( 0, 2 );
		minutes = value.substring( 3 );
		return Number( hours ) * 60 + Number( minutes );
	}
	return value;
}

/**
 * Converteer tijd in minuten naar tijd text.
 *
 * @param {int} value
 */
function timetostr( value ) {
	var hours = Math.floor( value / 60 );
	var minutes = value % 60;
	return ( '0' + hours ).slice( -2 ) + ':' + ( '0' + minutes ).slice( -2 );
}

/**
 * Converteer lokale datum in format 'd-m-Y' naar Date.
 *
 * @param (String) datum
 */
function strtodate( value ) {
	var veld = value.split( '-' );
	return new Date( veld[2], veld[1] - 1, veld[0] );
}

( function( $ ) {
    'use strict';

	/**
	 * Polyfill, kijk of de browser ondersteuning geeft voor een datalist element.
	 */
	var nativedatalist = !! ( 'list' in document.createElement( 'input' ) ) && !! ( document.createElement( 'datalist' ) && window.HTMLDataListElement );
	if ( ! nativedatalist ) {
		$( 'input[list]' ).each( function() {
			var availableTags = $( '#' + $( this ).attr( 'list' ) ).find( 'option' ).map( function() {
				return this.value;
				}
			).get();
			$( this ).autocomplete( { source: availableTags } );
			}
		);
	}

	/**
	 * Nederlandse versie van datatable
	 */
	if ( $( '.kleistad_datatable' )[0] ) {
		$.extend( $.fn.dataTable.defaults, {
			language: {
				swachten: 'Bezig...',
				sLengthMenu: '_MENU_ resultaten weergeven',
				sZeroRecords: 'Geen resultaten gevonden',
				sInfo: '_START_ tot _END_ van _TOTAL_ resultaten',
				sInfoEmpty: 'Geen resultaten om weer te geven',
				sInfoFiltered: ' (gefilterd uit _MAX_ resultaten)',
				sInfoPostFix: '',
				sSearch: 'Zoeken:',
				sEmptyTable: 'Geen resultaten aanwezig in de tabel',
				sInfoThousands: '.',
				sLoadingRecords: 'Een moment geduld aub - bezig met laden...',
				oPaginate: {
					sFirst: 'Eerste',
					sLast: 'Laatste',
					sNext: 'Volgende',
					sPrevious: 'Vorige'
				},
				oAria: {
					sSortAscending: ': activeer om kolom oplopend te sorteren',
					sSortDescending: ': activeer om kolom aflopend te sorteren'
				}
			}
		});
	}

	/**
	 * Maak een timespinner van de spinner.
	 */
	if ( $( '.kleistad_tijd' )[0] ) {
		$.widget(
			'ui.timespinner', $.ui.spinner, {
				options: {
					step: 15,
					page: 60,
					max: 60 * 23 + 45,
					min: 0,
					stop: function() {
						$( this ).change();
						}
				},
				_parse: function( value ) {
					return strtotime( value );
				},
				_format: function( value ) {
					return timetostr( value );
				}
			}
		);
	}

	/**
	 * Zoek de postcode op via de server.
	 */
	$.fn.lookupPostcode = function( postcode, huisnr, callback ) {
		if ( '' !== postcode && '' !== huisnr ) {
			$.ajax(
				{
					url: kleistadData.base_url + '/adres/',
					method: 'GET',
					data: {
						postcode: postcode,
						huisnr:   huisnr
					}
				}
			).done(
				function( data ) { // Als er niets gevonden kan worden dan code 204, data is dan undefined.
					if ( 'undefined' !== typeof data ) {
						callback( data );
					}
				}
			).fail(); // Geen verdere actie ingeval van falen.
		}
	};

	$.fn.removeClassWildcard = function( pattern ) {
		$( this ).removeClass( function( index, className ) {
			return ( className.match( new RegExp( '(^|\\s)' + pattern + '\\S+', 'g' ) ) || [] ).join( ' ' );
		});
	};

	function onLoad() {
		/**
		 * Definieer de tabellen.
		 */
		if ( $( '.kleistad_datatable' )[0] ) {
			$( '.kleistad_datatable' ).DataTable();
		}

		/**
		 * Definieer de datum velden.
		 */
		if ( $( '.kleistad_datum' )[0] ) {
			$.datepicker.setDefaults(
				{
					dateFormat: 'dd-mm-yy'
				}
			);
		}

		/**
		 * Definieer de timespinners.
		 */
		if ( $( '.kleistad_tijd' )[0] ) {
			$( '.kleistad_tijd' ).timespinner(
				{
					start: function() {
						return ( ! $( this ).attr( 'readonly' ) );
					}
				}
			);
		}

	}

	function submitForm( $form, data ) {
		var $shortcode = $form.closest( '.kleistad_shortcode' );
		/**
		 *  Bij een submit de spinner tonen.
		 */
		$( '#kleistad_wachten' ).addClass( 'kleistad_wachten' ).show();
		$.ajax(
			{
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				cache: false,
				contentType: false,
				data: data,
				method: 'POST',
				processData: false,
				url: kleistadData.base_url + '/formsubmit/'
			}
		).done(
			function( data ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad_wachten' );
				switch ( data.actie ) {
					case 'refresh':
					case 'home':
						$shortcode.html( data.html );
						break;
					case 'reset':
						$form[0].reset();
						break;
					case 'download':
						window.location.href = data.file_uri;
						break;
					case 'redirect':
						window.location.replace( data.redirect_uri );
						break;
					default: // Is ook case none.
				}
				$( '#kleistad_berichten' ).html( data.status );
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

	$( document ).ajaxComplete(
        function() {
			onLoad();
		}
	);

	$( document ).ready(
        function() {
			onLoad();

			/**
			 * Alle forms krijgen een wachten box.
			 */
			if ( $( '.kleistad_shortcode' )[0] ) {

				/**
				 * Leg voor de submit actie vast welke button de submit geïnitieerd heeft.
				 */
				$( '.kleistad_shortcode' ).on( 'click', 'button[type="submit"]',
					function( event ) {
						$( this ).closest( 'form' ).data( 'clicked', { id: event.target.id, value: event.target.value } );
					}
				);

				/**
				 * Submit het formulier, als er een formulier is.
				 */
				$( '.kleistad_shortcode' ).on( 'submit', 'form',
					function( event ) {
						var $form          = $( this );
						var data           = new FormData( this );
						var clicked        = $form.data( 'clicked' );
						var confirm        = $( '#' + clicked.id ).data( 'confirm' );
						var tekst          = 'undefined' === typeof confirm ? [] : confirm.split( '|' );
						data.append( 'form_actie', clicked.value );
						data.append( 'form_url', window.location.href );
						event.preventDefault();

						/**
						 * Sluit eventuele openstaande dialogs.
						 */
						$( 'ui-dialog' ).dialog( 'close' );

						/**
						 * Als er een tekst is om eerst te confirmeren dan de popup tonen.
						 */
						if ( tekst.length ) {
							$( '#kleistad_bevestigen' ).text( tekst[1] ).dialog(
								{
									modal: true,
									zIndex: 10000,
									autoOpen: true,
									width: 'auto',
									resizable: false,
									title: tekst[0],
									buttons: {
										Ja: function() {
											$( this ).dialog( 'close' );
											submitForm( $form, data );
										},
										Nee: function() {
											$( this ).dialog( 'close' );
											return false;
										}
									}
								}
							);
						} else {
							submitForm( $form, data );
						}
					}
				);
			}

			/**
			 * Voor de ondersteuning van touch events
			 */
			$( document ).on( 'touchstart', function() {
				detectTap = true;
			});

			$( document ).on( 'touchmove', function() {
				detectTap = false;
			});

		}
	);

} )( jQuery );
