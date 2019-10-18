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

	/**
	 * Verwijder een class mbv een wildcard pattern.
	 */
	$.fn.removeClassWildcard = function( pattern ) {
		$( this ).removeClass( function( index, className ) {
			return ( className.match( new RegExp( '(^|\\s)' + pattern + '\\S+', 'g' ) ) || [] ).join( ' ' );
		});
	};

	/**
	 * Definieer de tabellen.
	 */
	function defineDatatables() {
		var $datatable = $( '.kleistad_datatable' );
		if ( ! $datatable[0] ) {
			return;
		}
		if ( ! $.fn.DataTable.isDataTable( '.kleistad_datatable' ) ) {
			$datatable.dataTable( {
				language: {
					url: '//cdn.datatables.net/plug-ins/1.10.19/i18n/Dutch.json'
					}
				}
			);
		}
	}

	/**
	 * Definieer de datum velden.
	 */
	function defineDatums() {
		var $datum = $( '.kleistad_datum' );
		if ( $datum[0] && ! $datum.is( ':data("ui-datepicker")' ) ) {
			$datum.datepicker( {
				closeText: 'Sluiten',
				prevText: '←',
				nextText: '→',
				currentText: 'Vandaag',
				monthNames: [ 'januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december' ],
				monthNamesShort: [ 'jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec' ],
				dayNames: [ 'zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag' ],
				dayNamesShort: [ 'zon', 'maa', 'din', 'woe', 'don', 'vri', 'zat' ],
				dayNamesMin: [ 'zo', 'ma', 'di', 'wo', 'do', 'vr', 'za' ],
				weekHeader: 'Wk',
				dateFormat: 'dd-mm-yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: ''
				}
			);
		}
	}

	/**
	 * Definieer de timespinners.
	 */
	function defineTimespinners() {
		var $tijd = $( '.kleistad_tijd' );
		if ( $tijd[0] ) {
			$.widget(
				'ui.timespinner', $.ui.spinner, {
					options: {
						step: 15,
						page: 60,
						max: 60 * 23 + 45,
						min: 0,
						stop: function() {
							$( this ).change();
						},
						start: function() {
							return ( ! $( this ).attr( 'readonly' ) );
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
			$tijd.timespinner();
		}
	}

	/**
	 * Initieer de dynamische velden.
	 */
	function onLoad() {
		defineDatatables();
		defineDatums();
		defineTimespinners();
	}

	/**
	 * Doe een vervolg actie na een ajax response (data).
	 *
	 * @param { jQuery } $shortcode
	 * @param { array } data
	 */
	function vervolg( $shortcode, data ) {
		if ( 'status' in data ) {
			$( '#kleistad_berichten' ).html( data.status );
		} else {
			$( '#kleistad_berichten' ).html( '' );
		}
		if ( 'content' in data ) {
			$shortcode.html( data.content );
			window.scrollTo( 0, 0 );
		}
		if ( 'file_uri' in data ) {
			window.location.href = data.file_uri;
		}
		if ( 'redirect_uri' in data ) {
			window.location.replace( data.redirect_uri );
		}
	}

	/**
	 * Get the selected item using Ajax.
	 *
	 * @param { jQuery } $shortcode
	 * @param { array} data
	 * @param { String } path naar het endpoint
	 */
	function getContent( $shortcode, data, path ) {
		$( '#kleistad_wachten' ).addClass( 'kleistad_wachten' ).show();
		$.ajax(
			{
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				data: data,
				method: 'GET',
				url: kleistadData.base_url + '/' + path + '/'
			}
		).done(
			function( data ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad_wachten' );
				vervolg( $shortcode, data );
			}
		).fail(
			function( jqXHR ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad_wachten' );
				if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
					window.console.log( jqXHR.responseJSON.message );
				}
				$( '#kleistad_berichten' ).html( kleistadData.error_message );
			}
		);
	}

	/**
	 * Submit een Kleistad formulier via Ajax call
	 *
	 * @param { jQuery } $shortcode
	 * @param { array} data
	 */
	function submitForm( $shortcode, data ) {
		/**
		 *  Bij een submit de spinner tonen.
		 */
		$( '#kleistad_wachten' ).addClass( 'kleistad_wachten' ).show();
		$.ajax(
			{
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				contentType: false,
				data:        data,
				method:      'POST',
				processData: false,
				url:         kleistadData.base_url + '/formsubmit/'
			}
		).done(
			function( data ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad_wachten' );
				vervolg( $shortcode, data );
			}
		).fail(
			function( jqXHR ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad_wachten' );
				if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
					window.console.log( jqXHR.responseJSON.message );
				}
				$( '#kleistad_berichten' ).html( kleistadData.error_message );
			}
		);
	}

	/**
	 * Verzamel de relevante gegevens van de shortcode.
	 *
	 * @param { jQuery} $element
	 */
	function shortcode( $element ) {
		var $shortcode    = $element.closest( '.kleistad_shortcode' );
		var shortcodeData = { tag:   $shortcode.data( 'tag' ) };
		if ( 'undefined' !== typeof $shortcode.data( 'atts') ) {
			shortcodeData.atts = JSON.stringify( $shortcode.data( 'atts' ) );
		}
		if ( 'undefined' !== typeof $element.data( 'id' ) ) {
			shortcodeData.id = $element.data( 'id' );
		}
		if ( 'undefined' !== typeof $element.data( 'actie' ) ) {
			shortcodeData.actie = $element.data( 'actie' );
		} else {
			shortcodeData.actie = '-';
		}
		return shortcodeData;
	}

	/**
	 * Toon een confirmatie popup dialoog.
	 *
	 * @param {String} tekst Te tonen tekst. Als leeg dan wordt er geen popup getoond.
	 * @param (function) Uit te voeren actie indien ok.
	 */
	function askConfirm( tekst, callback ) {
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
							callback();
							return true;
						},
						Nee: function() {
							$( this ).dialog( 'close' );
							return false;
						}
					}
				}
			);
			return false;
		}
		callback();
		return true;
	}

	/**
	 * Wordt aangeroepen na elke ajax call.
	 */
	$( document ).ajaxComplete(
        function() {
			onLoad();
		}
	);

	/**
	 * Wordt aangeroepen nadat de webpage geladen is.
	 */
	$( document ).ready(
        function() {
			onLoad();

			/**
			 * Definieer de bevestig dialoog.
			 */
			$( '#kleistad_bevestigen' ).dialog(
				{
					autoOpen: false,
					modal: true
				}
			);

			$( '.kleistad_input_cbr' )
			/**
			 * Als er geen radio button is ingevoerd dat wordt deze invalid. Toon dan de rode omtrek.
			 */
			.on( 'invalid',
				function() {
					$( this ).next( '.kleistad_label_cbr' ).css( 'border', '3px solid red' );
				}
			)
			/**
			 * En verwijder voor alle radiobuttons van die groep zo gauw er één button ingedrukt is.
			 */
			.on( 'change',
				function() {
					$( '[name=' + $( this ).attr( 'name' ) + ']' ).next( '.kleistad_label_cbr' ).css( 'border', 'none' );
				}
			);

			$( '.kleistad_shortcode' )
			/**
			 * Leg voor de submit actie vast welke button de submit geïnitieerd heeft.
			 */
			.on( 'click', 'button[type="submit"]',
				function( event ) {
					$( this ).closest( 'form' ).data( 'clicked', { id: event.target.id, value: event.target.value } );
					return true;
				}
			)
			/**
			 * Als er op een edit anchor is geklikt, doe dan een edit actie.
			 */
			.on( 'click', '.kleistad_edit_link',
				function() {
					var $anchor       = $( this );
					var shortcodeData = shortcode( $anchor );
					getContent( $anchor.closest( '.kleistad_shortcode' ), shortcodeData, 'getitem' );
					return true;
				}
			)
			/**
			 * Als er op een terug anchor is geklikt
			 */
			.on( 'click', '.kleistad_terug_link',
				function() {
					var $button       = $( this );
					var shortcodeData = shortcode( $button );
					getContent( $button.closest( '.kleistad_shortcode' ), shortcodeData, 'getitems' );
					return true;
				}
			)
			/**
			 * Als er op een download button link is geklikt
			 */
			.on( 'click', '.kleistad_download_link',
				function() {
					var $button       = $( this );
					var shortcodeData = shortcode( $button );
					$( 'input' ).each(
						function() {
							shortcodeData[ $( this ).attr( 'name') ] = $( this ).val();
						}
					);
					getContent( $button.closest( '.kleistad_shortcode' ), shortcodeData, 'download' );
					return true;
				}
			)
			/**
			 * Submit het formulier, als er een formulier is.
			 */
			.on( 'submit', 'form',
				function( event ) {
					var $form         = $( this );
					var shortcodeData = shortcode( $form );
					var formData      = new FormData( this );
					var clicked       = $form.data( 'clicked' );
					var confirm       = $( '#' + clicked.id ).data( 'confirm' );
					var tekst         = 'undefined' === typeof confirm ? [] : confirm.split( '|' );
					formData.append( 'form_actie', clicked.value );
					Object.keys( shortcodeData ).forEach( function( item ) {
						formData.append( item, shortcodeData[item] );
					} );
					event.preventDefault();
					/**
					 * Als er een tekst is om eerst te confirmeren dan de popup tonen.
					 */
					return askConfirm( tekst,
						function() {
							submitForm( $form.closest( '.kleistad_shortcode' ), formData );
						}
					);
				}
			)
			/**
			 * Voor de ondersteuning van touch events
			 */
			.on( 'touchstart',
				function() {
					detectTap = true;
				}
			)
			.on( 'touchmove',
				function() {
					detectTap = false;
				}
			);
		}
	);

} )( jQuery );
