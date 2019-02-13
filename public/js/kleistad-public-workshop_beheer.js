/* global DOMParser */

( function( $ ) {
    'use strict';

	/**
	 * Converteer eventuele html special karakters
	 *
	 * @param {String} value
	 */
	function decode( value ) {
		var parser = new DOMParser();
		var dom = parser.parseFromString(
			'<!doctype html><body>' + value,
			'text/html' );
		return dom.body.textContent;
	}

    $( document ).ready(
        function() {

			/**
             * Definieer de tabel.
             */
            $( '.kleistad_rapport' ).DataTable(
                {
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
                    },
					pageLength: 10,
					columnDefs: [
						{ visible: false, targets: [ 2 ] },
                        { orderData: [ 2 ], targets: [ 1 ] }
					],
                    order: [ 2, 'desc' ]
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
                        min: 0
                    },
                    _parse: function( value ) {
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
                    },
                    _format: function( value ) {
                        var hours = Math.floor( value / 60 );
                        var minutes = value % 60;
                        return ( '0' + hours ).slice( -2 ) + ':' + ( '0' + minutes ).slice( -2 );
                    }
                }
            );

            /**
             * Definieer de timespinners.
             */
            $( '.kleistad_tijd' ).each(
                function() {
                    $( this ).timespinner();
                }
            );

            /**
             * Definieer de datumpickers.
             */
            $( '.kleistad_datum' ).each(
                function() {
                    $( this ).datepicker(
                        {
                            dateFormat: 'dd-mm-yy'
                        }
                    );
                }
            );

            /**
             * Definieer de popup dialoog
             */
            $( '#kleistad_workshop' ).dialog(
                {
                    autoOpen: false,
                    height: 'auto',
                    width: 750,
                    modal: true
                }
            );

            /**
             * Verander de opmaak bij hover
             */
            $( 'body' ).on(
                'hover', '.kleistad_workshop_info', function() {
                    $( this ).css( 'cursor', 'pointer' );
                    $( this ).toggleClass( 'kleistad_hover' );
                }
            );

            /**
             * Toon de details van het geselecteerde workshop.
             */
            $( 'body' ).on(
                'click', '.kleistad_workshop_info', function() {
					var workshop = $( this ).data( 'workshop' );
                    $( '#kleistad_workshop' ).dialog( 'option', 'title', decode( workshop.naam ) ).dialog( 'open' );
                    $( '#kleistad_id' ).val( workshop.id );
                    $( '#kleistad_naam' ).val( decode( workshop.naam ) );
					$( '#kleistad_docent' ).val( decode( workshop.docent ) );
                    $( '#kleistad_datum' ).val( workshop.datum );
                    $( '#kleistad_start_tijd' ).val( workshop.start_tijd );
                    $( '#kleistad_eind_tijd' ).val( workshop.eind_tijd );
                    $( '#kleistad_aantal' ).val( workshop.aantal );
                    $( '#kleistad_kosten' ).val( workshop.kosten );
                    $( '#kleistad_email' ).val( workshop.email );
                    $( '#kleistad_contact' ).val( decode( workshop.contact ) );
                    $( '#kleistad_telefoon' ).val( decode( workshop.telefoon ) );
                    $( '#kleistad_organisatie' ).val( decode( workshop.organisatie ) );
					$( '#kleistad_programma' ).val( decode( workshop.programma ) );
					$( '#kleistad_definitief' ).attr( 'class', ( workshop.definitief > 0 ) ? 'genericon genericon-checkmark' : '' );
					$( '#kleistad_betaald' ).attr( 'class', ( workshop.betaald > 0 ) ? 'genericon genericon-checkmark' : '' );
                    $( '#kleistad_draaien' ).prop( 'checked', String( workshop.technieken ).indexOf( 'Draaien' ) >= 0 );
                    $( '#kleistad_handvormen' ).prop( 'checked', String( workshop.technieken ).indexOf( 'Handvormen' ) >= 0 );
					$( '#kleistad_boetseren' ).prop( 'checked', String( workshop.technieken ).indexOf( 'Boetseren' ) >= 0 );
					$( '#kleistad_kosten,#kleistad_datum' ).attr( 'readonly', workshop.definitief );
					$( '#kleistad_workshop_bevestigen,#kleistad_workshop_opslaan' ).prop( 'disabled', workshop.betaald || workshop.definitief || workshop.vervallen );
					$( '#kleistad_workshop_afzeggen' ).prop( 'disabled', workshop.vervallen );
                }
            );

            /**
             * Toon een lege popup dialoog voor een nieuwe workshop
             */
            $( 'body' ).on(
                'click', '#kleistad_workshop_toevoegen', function() {
					$( '#kleistad_workshop' ).dialog( 'option', 'title', '*** nieuw ***' ).dialog( 'open' );
					$( '#kleistad_workshop_form' )[0].reset();
					$( '#kleistad_id' ).val( 0 );
					$( '#kleistad_workshop_bevestigen,#kleistad_workshop_opslaan' ).prop( 'disabled', false );
					$( '#kleistad_workshop_afzeggen' ).prop( 'disabled', true );
					$( '#kleistad_definitief,#kleistad_betaald' ).removeAttr( 'class' );
					$( '#kleistad_kosten,#kleistad_datum' ).attr( 'readonly', false );
                }
            );
        }
    );

} )( jQuery );
