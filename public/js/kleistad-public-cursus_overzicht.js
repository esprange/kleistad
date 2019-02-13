/* global: DOMParser */
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
             * Definieer de tabel
             *
             * @type array kleistadCursusLijst array van deelnemers
             */
            $( '#kleistad_cursus_lijst' ).DataTable(
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
                    order: [ 1, 'desc' ],
                    columnDefs: [
                        { visible: false, targets: [ 0, 1 ] },
                        { orderData: [ 0 ], targets: [ 2 ] },
                        { orderData: [ 1 ], targets: [ 5 ] }
                    ]

                }
            );

            /**
             * Definieer de popup dialoog
             */
            $( '#kleistad_cursisten_info' ).dialog(
                {
                    autoOpen: false,
                    height: 'auto',
                    width: 1000,
                    modal: true,
                    buttons: {
                        Ok: function() {
                            $( this ).dialog( 'close' );
						},
						'Kopie naar klembord': function() {
							var range     = document.createRange(),
								lijst     = $( '#kleistad_email_lijst' ).val(),
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
						},
						Download: function() {
                            $( '#kleistad_download_cursisten' ).submit();
						}
                    }
                }
            );

            /**
             * Verander de opmaak bij hovering.
             */
            $( 'body' ).on(
                'hover', '.kleistad_cursus_info', function() {
                    $( this ).css( 'cursor', 'pointer' );
                    $( this ).toggleClass( 'kleistad_hover' );
                }
            );

            /**
             * Toon de detailinformatie van de deelnemer
             */
            $( 'body' ).on(
                'click', '.kleistad_cursus_info', function() {
                    var html   = '<tr><th>Naam</th><th>Telefoon</th><th>Email</th><th>Technieken</th></tr>',
						lijst  = $( this ).data( 'lijst' ),
						id     = $( this ).data( 'id' ),
						naam   = $( this ).data( 'naam' ),
						emails = '';
					$( '#kleistad_cursisten_info' ).dialog( 'option', 'title', decode( naam ) ).dialog( 'open' );
					$( '#kleistad_cursus_id' ).val( id );
					$.each( lijst, function( key, value ) {
						html += '<tr><td>' + decode( value.naam ) + ( 1 < value.aantal ? ' (' + value.aantal + ')' : '' ) +
								'</td><td>' + value.telnr +
								'</td><td>' + value.email +
								'</td><td>' + value.technieken +
								'</td></tr>';
						emails += value.email + ';';
					} );
					$( '#kleistad_cursisten_lijst' ).empty().append( html );
					$( '#kleistad_email_lijst' ).val( emails );
                }
            );
        }
    );
} )( jQuery );
