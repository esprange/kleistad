( function ( $ ) {
    'use strict';

    $( document ).ready(
        function () {
            /**
             * Definieer de popup dialoog
             */
            $( '#kleistad_deelnemer_info' ).dialog(
                {
                            autoOpen: false,
                            height: 400,
                            width: 750,
                    modal: true,
                    buttons: {
                        Ok: function () {
                            $( this ).dialog( 'close' );
                        }
                    }
                }
            );

            /**
             * Definieer de tabel
             *
             * @type array kleistadDeelnemerLijst array van deelnemers
             */
            var kleistadDeelnemerLijst = $( '#kleistad_deelnemer_lijst' ).DataTable(
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
                            sSortAscending:  ': activeer om kolom oplopend te sorteren',
                            sSortDescending: ': activeer om kolom aflopend te sorteren'
                        }
                    },
                    columnDefs: [
                        { visible: false, targets: [ 0, 1 ] }
                    ]

                }
            );

            /**
             * Verander de opmaak bij hovering.
             */
            $( 'body' ).on(
                'hover', '.kleistad_deelnemer_info', function () {
                    $( this ).css( 'cursor', 'pointer' );
                    $( this ).toggleClass( 'kleistad_hover' );
                }
            );

            /**
             * Filter de abonnees/cursisten.
             */
            $( 'body' ).on(
                'click', '#kleistad_deelnemer_selectie', function () {
                    var selectie = $( this ).val();
                    switch ( selectie ) {
                        case '*':
                            kleistadDeelnemerLijst.search( '' ).columns().search( '' );
                            kleistadDeelnemerLijst.columns().search( '', false, false ).draw();
                            break;

                        case '0':
                            kleistadDeelnemerLijst.search( '' ).columns().search( '' );
                            kleistadDeelnemerLijst.columns( 0 ).search( '1', false, false ).draw();
                            break;

                        default:
                            kleistadDeelnemerLijst.search( '' ).columns().search( '' );
                            kleistadDeelnemerLijst.columns( 1 ).search( selectie, false, false ).draw();
                    }
                }
            );

            /**
             * Toon de detailinformatie van de deelnemer
             */
            $( 'body' ).on(
                'click', '.kleistad_deelnemer_info', function () {
                    var header = '<tr><th>Cursus</th><th>Code</th><th>Ingedeeld</th><th>Inschrijfgeld</th><th>Cursusgeld</th><th>Geannuleerd</th><th>Technieken</th></tr>',
                        inschrijvingen = $( this ).data( 'inschrijvingen' ),
                        deelnemer = $( this ).data( 'deelnemer' ),
                        abonnee = $( this ).data( 'abonnee' );
                    $( '#kleistad_deelnemer_info' ).dialog( 'open' );
                    $( '#kleistad_deelnemer_tabel' ).empty();
                    $( '#kleistad_deelnemer_tabel' )
                        .append(
                            '<tr><th colspan="7">' + deelnemer.naam + '<br/>' +
                            deelnemer.straat + ' ' + deelnemer.huisnr + ' ' +
                            deelnemer.pcode + ' ' + deelnemer.plaats + '</td></tr>'
                            );

                    if ( typeof inschrijvingen !== 'undefined' ) {
                        $.each(
                            inschrijvingen, function ( key, value ) {
                                var status = ( value.ingedeeld ) ? '<span class="dashicons dashicons-yes"></span>' : '',
                                    i_betaald = ( value.i_betaald ) ? '<span class="dashicons dashicons-yes"></span>' : '',
                                    c_betaald = ( value.c_betaald ) ? '<span class="dashicons dashicons-yes"></span>' : '',
                                    geannuleerd = ( value.geannuleerd ) ? '<span class="dashicons dashicons-yes"></span>' : '',
                                    html = header + '<tr><td>' + value.naam + '</td><td>' + value.code + '</td><td>' + status +
                                    '</td><td>' + i_betaald + '</td><td>' + c_betaald + '</td><td>' + geannuleerd + '</td><td>',
                                    separator = '';
                                $.each(
                                    value.technieken, function ( key, value ) {
                                        html += separator + value;
                                        separator = '<br/>';
                                    }
                                );
                                $( '#kleistad_deelnemer_tabel' ).append( html + '</td></tr>' );
                                header = '';
                            }
                        );
                    } else {
                        $( '#kleistad_deelnemer_tabel' ).append( '<tr><td colspan="6" >Geen cursus inschrijvingen aanwezig</td></tr>' );
                    }
                    if ( ( typeof abonnee !== 'undefined' ) && ( abonnee.length !== 0 ) ) {
                        $( '#kleistad_deelnemer_tabel' ).append(
                            '<tr><th>Abonnement</th><th>Code</th><th>Dag</th><th>Start Datum</th></tr>' +
                            '<tr><td>' + abonnee.soort + '</td><td>' +
                            abonnee.code + '</td><td>' +
                            abonnee.dag + '</td><td>' +
                            abonnee.start_datum + '</td></tr>'
                            );
                    }
                }
            );
        }
    );
} )( jQuery );
