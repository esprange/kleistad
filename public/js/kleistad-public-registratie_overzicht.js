( function ( $ ) {
    'use strict';

    $( document ).ready( function () {
        $( '#kleistad_deelnemer_info' ).dialog( {
                    autoOpen: false,
                    height: 400,
                    width: 750,
            modal: true,
            buttons: {
                Ok: function () {
                    $( this ).dialog( 'close' );
                }
            }
        } );

        var kleistad_deelnemer_lijst = $( '#kleistad_deelnemer_lijst' ).DataTable( {
            language: {
                    "sProcessing": "Bezig...",
                    "sLengthMenu": "_MENU_ resultaten weergeven",
                    "sZeroRecords": "Geen resultaten gevonden",
                    "sInfo": "_START_ tot _END_ van _TOTAL_ resultaten",
                    "sInfoEmpty": "Geen resultaten om weer te geven",
                    "sInfoFiltered": " (gefilterd uit _MAX_ resultaten)",
                    "sInfoPostFix": "",
                    "sSearch": "Zoeken:",
                    "sEmptyTable": "Geen resultaten aanwezig in de tabel",
                    "sInfoThousands": ".",
                    "sLoadingRecords": "Een moment geduld aub - bezig met laden...",
                    "oPaginate": {
                            "sFirst": "Eerste",
                            "sLast": "Laatste",
                            "sNext": "Volgende",
                            "sPrevious": "Vorige"
                    },
                    "oAria": {
                            "sSortAscending":  ": activeer om kolom oplopend te sorteren",
                            "sSortDescending": ": activeer om kolom aflopend te sorteren"
                    }
            },
            "columnDefs": [
                { "visible": false, "targets": [ 0, 1 ] }
              ]

        } );

        $( 'body' ).on( 'hover', '.kleistad_deelnemer_info', function () {
            $( this ).css( 'cursor', 'pointer' );
            $( this ).toggleClass( 'kleistad_hover' );
        } );

        $( 'body' ).on( 'click', '#kleistad_deelnemer_selectie', function () {
            var selectie = $( this ).val();
            switch ( selectie ) {
                case '*':
                    kleistad_deelnemer_lijst.search( '' ).columns().search( '' );
                    kleistad_deelnemer_lijst.columns().search( '', false, false ).draw();
                    break;

                case '0':
                    kleistad_deelnemer_lijst.search( '' ).columns().search( '' );
                    kleistad_deelnemer_lijst.columns( 0 ).search( '1', false, false ).draw();
                    break;

                default:
                    kleistad_deelnemer_lijst.search( '' ).columns().search( '' );
                    kleistad_deelnemer_lijst.columns( 1 ).search( selectie, false, false ).draw();
            }
        } );

        $( 'body' ).on( 'click', '.kleistad_deelnemer_info', function () {
            $( '#kleistad_deelnemer_info' ).dialog( 'open' );
            var inschrijvingen = $( this ).data( 'inschrijvingen' );
            var deelnemer = $( this ).data( 'deelnemer' );
            var abonnee = $( this ).data( 'abonnee' );
            $( '#kleistad_deelnemer_tabel' ).empty();
            $( '#kleistad_deelnemer_tabel' )
                .append( '<tr><th colspan="6">' + deelnemer.naam + '<br/>' +
                    deelnemer.straat + ' ' + deelnemer.huisnr + ' ' +
                    deelnemer.pcode + ' ' + deelnemer.plaats + '</td></tr>' );

            var header = '<tr><th>Cursus</th><th>Code</th><th>Ingedeeld</th><th>Inschrijfgeld<br/>voldaan</th><th>Cursusgeld<br/>voldaan</th><th>Technieken</th></tr>';
            if ( typeof inschrijvingen !== 'undefined' ) {
                $.each( inschrijvingen, function ( key, value ) {
                    var status = ( value.ingedeeld ) ? '<span class="dashicons dashicons-yes"></span>' : '';
                    var i_betaald = ( value.i_betaald ) ? '<span class="dashicons dashicons-yes"></span>' : '';
                    var c_betaald = ( value.c_betaald ) ? '<span class="dashicons dashicons-yes"></span>' : '';

                    var html = header + '<tr><td>' + value.naam + '</td><td>' + value.code + '</td><td>' + status + '</td><td>' + i_betaald + '</td><td>' + c_betaald + '</td><td>';
                    header = '';
                    var separator = '';
                    $.each( value.technieken, function ( key, value ) {
                        html += separator + value;
                        separator = '<br/>';
                    } );
                    $( '#kleistad_deelnemer_tabel' ).append( html + '</td></tr>' );
                } );
            } else {
                $( '#kleistad_deelnemer_tabel' ).append( '<tr><td colspan="6" >Geen cursus inschrijvingen aanwezig</td></tr>' );
            }
            if ( ( typeof abonnee !== 'undefined' ) && ( abonnee.length !== 0 ) ) {
                $( '#kleistad_deelnemer_tabel' ).append( '<tr><th colspan="2" ><Abonnee code</th><th>Type<br/>abonnement</th><th>Dag</th><th>Start<br/>Datum</th></tr>' +
                    '<tr><td colspan="2" >' + abonnee.code + '</td><td>' +
                    abonnee.beperkt + '</td><td>' +
                    abonnee.dag + '</td><td>' +
                    abonnee.start_datum + '</td></tr>' );
            }
        } );
    } );
} )( jQuery );
