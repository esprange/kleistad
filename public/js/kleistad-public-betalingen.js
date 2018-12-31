( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {

			var kleistad_rapport =
				{
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
				};

			/**
             * Definieer datatable.
             */
 /*           $( '.kleistad_rapport' ).DataTable(
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
                    }
                }
			);
*/
			$( '#kleistad_inschrijvingen' ).DataTable(
				{
					language: kleistad_rapport,
                    columnDefs: [
                        { className: 'dt-body-right', targets: [ 0 ] },
                        { className: 'dt-body-center', targets: [ 4, 6, 8 ] },
                        { orderData: [ 1 ], targets: [ 0 ] },
                        { orderData: [ 5 ], targets: [ 4 ] },
                        { orderData: [ 7 ], targets: [ 6 ] },
                        { orderData: [ 9 ], targets: [ 8 ] },
                        { targets: [ 1, 5, 7, 9 ], visible: false, searchable: false }
                    ]
				}
			);

			$( '#kleistad_Workshops' ).DataTable(
				{
					language: kleistad_rapport,
					columnDefs: [
                        { className: 'dt-body-right', targets: [ 0, 5 ] },
                        { className: 'dt-body-center', targets: [ 6 ] },
                        { orderData: [ 1 ], targets: [ 0 ] },
                        { targets: [ 1 ], visible: false, searchable: false }
					]
				}
			);
        }
    );

} )( jQuery );
