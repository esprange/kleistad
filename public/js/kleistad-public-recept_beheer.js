( function( $ ) {
    'use strict';

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
                    pageLength: 5,
                    order: [ 1, 'desc' ],
                    columnDefs: [
                        {
                            width: 100, targets: [ 0, 2]
                        }
                    ]

                }
            );

            $( '#kleistad_recept_toevoegen' ).click( function() {
                $( '#kleistad_recept_action' ).val( 'toevoegen' );
                $( '#kleistad_recept_id' ).val( 0 );
            });

            $( '#kleistad_foto_input' ).change( function() {
                var reader = new FileReader();
                if ( this.files && this.files[0] ) {
                    reader.onload = function( e ) {
                        $( '#kleistad_foto' ).attr( 'src', e.target.result );
                    };
                    reader.readAsDataURL( this.files[0] );
                }
            });

            $( '[name="wijzigen"]' ).click( function() {
                var id = $( this ).data( 'recept_id' );
                $( '#kleistad_recept_action' ).val( 'wijzigen' );
                $( '#kleistad_recept_id' ).val( id );
            });

            $( '#kleistad_verwijder_recept' ).dialog( {
                autoOpen: false,
                resizable: false,
                height: 'auto',
                width: 400,
                modal: true
            });

            $( '[name="verwijderen"]' ).click( function( event ) {
                var targetUrl = $( this ).attr( 'href' );
                var id = $( this ).data( 'recept_id' );

                event.preventDefault();
                $( '#kleistad_recept_action' ).val( 'verwijderen' );
                $( '#kleistad_recept_id' ).val( id );
                $( '#kleistad_verwijder_recept' ).dialog( {
                    buttons: {
                        Ok: function() {
                            window.location.href = targetUrl;
                        },
                        Annuleren: function() {
                            $( this ).dialog( 'close' );
                        }
                    }
                });
                $( '#kleistad_verwijder_recept' ).dialog( 'open' );
            });

            $( '.extra_regel' ).click( function() {
                var oldRow, newRow;
                oldRow = $( this ).closest( 'tr' ).prev();
                newRow = oldRow.clone().find( 'input' ).val( '' ).end();
                oldRow.after( newRow );
                return false;
            });
        }
    );

} )( jQuery );
