/**
 * Generieke Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 */

 /* global kleistadData */

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
	 * Nederlandse versie van datatable
	 */
	$.extend( $.fn.dataTable.defaults, {
		language: {
			sProcessing: 'Bezig...',
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

	/**
	 * Maak een timespinner van de spinner.
	 */
	$.widget(
		'ui.timespinner', $.ui.spinner, {
			options: {
				step: 15,
				page: 60,
				max: 60 * 23 + 45,
				min: 0,
				spin: function() {
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

	/**
	 * Kopieer value naar klembord.
	 */
	$.fn.kleistad_klembord = function() {
		var range     = document.createRange(),
			lijst     = $( this ).val(),
			selection, $temp;

		// For IE.
		if ( window.clipboardData ) {
			window.clipboardData.setData( 'Text', lijst );
		} else {
			$temp = $( '<div>' );
			$temp.css( {
				position: 'absolute',
				left:     '-1000px',
				top:      '-1000px'
			} );
			$temp.text( lijst );
			$( 'body' ).append( $temp );
			range.selectNodeContents( $temp.get( 0 ) );
			selection = window.getSelection();
			selection.removeAllRanges();
			selection.addRange( range );
			document.execCommand( 'copy', false, null );
			$temp.remove();
		}
		return this;
	};

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
				function( data ) {
					callback( data );
				}
			).fail(); // Geen verdere actie ingeval van falen.
		}
	}


	$( document ).ready(
        function() {
            /**
             * Definieer de tabellen.
             */
			$( '.kleistad_datatable' ).DataTable();

			/**
             * Definieer de datum velden.
             */
            $( '.kleistad_datum' ).datepicker(
				{
					dateFormat: 'dd-mm-yy'
				}
			);

            /**
             * Definieer de timespinners.
             */
            $( '.kleistad_tijd' ).timespinner();
		}
	);

} )( jQuery );
