( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {
            /**
             * Definieer de tabel
             *
             * @type array kleistadCursusLijst array van deelnemers
             */
            $( '#kleistad_abonnement_lijst' ).DataTable(
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
                    order: [ 0, 'asc' ]
                }
            );

			$( '#kleistad_klembord' ).click(
				function() {
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
				}
			);

        }
    );
} )( jQuery );
