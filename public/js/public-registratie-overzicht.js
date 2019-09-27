/* global detectTap */

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
             * Filter de abonnees/cursisten.
             */
			$( '#kleistad_deelnemer_selectie' ).on( 'click',
				function() {
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
			$( '#kleistad_deelnemer_lijst tbody' ).on( 'click touchend', 'tr',
				function( event ) {
					var header, inschrijvingen, deelnemer, abonnee, dagdelenkaart;
					if ( 'click' === event.type || detectTap ) {
						inschrijvingen = $( this ).data( 'inschrijvingen' );
						deelnemer      = $( this ).data( 'deelnemer' );
						abonnee        = $( this ).data( 'abonnee' );
						dagdelenkaart  = $( this ).data( 'dagdelenkaart' );
						header         = '<tr><th>Cursus</th><th>Code</th><th>Ingedeeld</th><th>Inschrijfgeld</th><th>Cursusgeld</th><th>Geannuleerd</th><th>Technieken</th></tr>';
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
						if ( ( 'undefined' !== typeof dagdelenkaart ) && ( 0 !== dagdelenkaart.length ) ) {
							$( '#kleistad_deelnemer_tabel' ).append(
								'<tr><th>Dagdelenkaart</th><th>Code</th><th>Start Datum</th></tr><tr><th>' +
								'' + '</th><th>' +
								dagdelenkaart.code + '</th><th>' +
								dagdelenkaart.start_datum + '</th></tr>'
							);
						}
                    }
                }
            );
        }
    );
} )( jQuery );
