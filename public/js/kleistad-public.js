/* global DOMParser */

/**
 * Converteer eventuele html special karakters
 *
 * @param {String} value
 */
function jdecode( value ) {
	var parser = new DOMParser();
	var dom = parser.parseFromString(
		'<!doctype html><body>' + value,
		'text/html' );
	return dom.body.textContent;
}

( function( $ ) {
    'use strict';

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
	 * Default settings voor datapicker.
	 */
	$.datepicker.setDefaults(
		{
			dateFormat: 'dd-mm-yy'
		}
	);

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

	$( document ).ready(
        function() {
            /**
             * Definieer de tabel.
             */
			$( '.kleistad_datatable' ).DataTable();

			/**
             * Definieer de datum velden.
             */
            $( '.kleistad_datum' ).datepicker();

            /**
             * Definieer de timespinners.
             */
            $( '.kleistad_tijd' ).timespinner();
		}
	);

} )( jQuery );
