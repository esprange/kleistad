( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {
            /**
             * Definieer de popup dialoog
             */
            $( '#kleistad_deelnemer_info' ).dialog(
                {
                    autoOpen: false,
                    height: 'auto',
                    width: 1000,
                    modal: true,
                    buttons: {
                        Ok: function() {
                            $( this ).dialog( 'close' );
                        }
                    }
                }
            );

            /**
             * Verander de opmaak bij hovering.
             */
            $( 'body' ).on(
                'hover', '.kleistad_deelnemer_info', function() {
                    $( this ).css( 'cursor', 'pointer' );
                    $( this ).toggleClass( 'kleistad_hover' );
                }
            );

            /**
             * Filter de abonnees/cursisten.
             */
            $( 'body' ).on(
                'click', '#kleistad_deelnemer_selectie', function() {
					var selectie = $( this ).val();
					var kleistadDeelnemerLijst = $( '#kleistad_deelnemer_lijst' ).DataTable();
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
                'click touchstart', '.kleistad_deelnemer_info', function() {
                    var header = '<tr><th>Cursus</th><th>Code</th><th>Ingedeeld</th><th>Inschrijfgeld</th><th>Cursusgeld</th><th>Geannuleerd</th><th>Technieken</th></tr>',
                        inschrijvingen = $( this ).data( 'inschrijvingen' ),
                        deelnemer = $( this ).data( 'deelnemer' ),
                        abonnee = $( this ).data( 'abonnee' );
                    $( '#kleistad_deelnemer_info' ).dialog( 'option', 'title', deelnemer.naam ).dialog( 'open' );
                    $( '#kleistad_deelnemer_tabel' ).empty();
                    $( '#kleistad_deelnemer_tabel' )
                        .append(
                            '<tr><th>Adres</<th><td colspan="6" style="text-align:left" >' +
							deelnemer.straat + ' ' + deelnemer.huisnr + ' ' + deelnemer.pcode + ' ' + deelnemer.plaats +
							'</td></tr>'
                        );

                    if ( 'undefined' !== typeof inschrijvingen ) {
                        $.each(
                            inschrijvingen, function( key, value ) {
                                var status = ( value.ingedeeld ) ? '<span class="dashicons dashicons-yes"></span>' : '',
                                    ibetaald = ( value.i_betaald ) ? '<span class="dashicons dashicons-yes"></span>' : '',
                                    cbetaald = ( value.c_betaald ) ? '<span class="dashicons dashicons-yes"></span>' : '',
                                    geannuleerd = ( value.geannuleerd ) ? '<span class="dashicons dashicons-yes"></span>' : '',
                                    code = value.code + ( ( 1 < value.aantal ) ? '(' + value.aantal + ')' : '' ),
                                    html = header + '<tr><td>' + value.naam + '</td><th>' + code + '</th><th>' + status +
                                    '</th><th>' + ibetaald + '</th><th>' + cbetaald + '</th><th>' + geannuleerd + '</th><th>',
                                    separator = '';
                                $.each(
                                    value.technieken, function( key, value ) {
                                        html += separator + value;
                                        separator = '<br/>';
                                    }
                                );
                                $( '#kleistad_deelnemer_tabel' ).append( html + '</th></tr>' );
                                header = '';
                            }
                        );
                    } else {
                        $( '#kleistad_deelnemer_tabel' ).append( '<tr><td colspan="6" >Geen cursus inschrijvingen aanwezig</td></tr>' );
                    }
                    if ( ( 'undefined' !== typeof abonnee ) && ( 0 !== abonnee.length ) ) {
                        $( '#kleistad_deelnemer_tabel' ).append(
							'<tr><th>Abonnement</th><th>Code</th><th>Dag</th><th>Start Datum</th><th>Pauze Datum</th><th>Herstart Datum</th><th>Eind Datum</th></tr><tr><th>' +
							abonnee.soort + '<br/>' + abonnee.extras + '</th><th>' +
                            abonnee.code + '</th><th>' +
                            abonnee.dag + '</th><th>' +
							abonnee.start_datum + '</th><th>' +
							abonnee.pauze_datum + '</th><th>' +
							abonnee.herstart_datum + '</th><th>' +
							abonnee.eind_datum + '</th></tr>'
                            );
                    }
                }
            );
        }
    );
} )( jQuery );
