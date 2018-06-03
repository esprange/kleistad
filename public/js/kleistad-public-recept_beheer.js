( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {

			/**
			 * Polyfill, kijk of de browser ondersteuning geeft voor een datalist element.
			 */
			var nativedatalist = !! ( 'list' in document.createElement( 'input' ) ) &&
				!! ( document.createElement( 'datalist' ) && window.HTMLDataListElement );
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
                    order: [ 2, 'desc' ],
                    columnDefs: [
                        { width: 100, targets: [ 0 ] },
                        { orderable: false, targets: [ 0, 5 ] },
                        { orderData: [ 2 ], targets: [ 3 ] },
                        { visible: false, searchable: false, targets: [ 2 ] }
                    ]

                }
            );

            $( '.kleistad_gewicht' ).on( 'keydown', function( e ) {
                if (

                    // Backspace, delete, tab, escape, enter, comma and .
                    $.inArray( e.keyCode, [46, 8, 9, 27, 13, 110, 188, 190] ) !== -1 ||

                    // Ctrl/cmd+A, Ctrl/cmd+C, Ctrl/cmd+X
                    ( $.inArray( e.keyCode, [65, 67, 88] ) !== -1 && ( true === e.ctrlKey || true === e.metaKey ) ) ||

                    // Home, end, left, right
                    ( e.keyCode >= 35 && e.keyCode <= 39 ) ) {

                    return;
                }

                // Block any non-number
                if ( ( e.shiftKey || ( e.keyCode < 48 || e.keyCode > 57 ) ) && ( e.keyCode < 96 || e.keyCode > 105 ) ) {
                    e.preventDefault();
                }
            });

            $( '#kleistad_recept_toevoegen' ).click( function() {
                $( '#kleistad_recept_action' ).val( 'toevoegen' );
                $( '#kleistad_recept_id' ).val( 0 );
            });

            $( '#kleistad_foto_input' ).change( function() {
                var reader = new FileReader();
                if ( this.files && this.files[0] ) {
                    if ( this.files[0].size > 2000000 ) {
                        window.alert( 'deze foto is te groot (' + this.files[0].size + ' bytes)' );
                        $( this ).val( '' );
                        return false;
                    }
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
