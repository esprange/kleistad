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
                    order: [ 0, 'desc' ],
                    columnDefs: [
                        { visible: false, targets: [ 0 ] }
                    ]

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
            $( '#kleistad_cursus' ).dialog(
                {
                    autoOpen: false,
                    height: 'auto',
                    width: 750,
                    modal: true,
                    /* jshint unused:vars */
                    open: function( event, ui ) {
                        $( '#kleistad_cursus_tabs' ).tabs( { active: 0 } );
                    }
                }
            );

            /**
             * Verander de opmaak bij hover
             */
            $( 'body' ).on(
                'hover', '.kleistad_cursus_info', function() {
                    $( this ).css( 'cursor', 'pointer' );
                    $( this ).toggleClass( 'kleistad_hover' );
                }
            );

           /**
             * Verander de opmaak bij hovering.
             */
            $( 'body' ).on(
                'hover', '.kleistad_cursist', function() {
                    $( this ).css( 'cursor', 'pointer' );
                    $( this ).toggleClass( 'kleistad_hover' );
                }
            );

            /**
             * Toon de details van de geselecteerde cursus.
             */
            $( 'body' ).on(
                'click', '.kleistad_cursus_info', function() {
                    var cursus = $( this ).data( 'cursus' ),
						ingedeeld = $( this ).data( 'ingedeeld' );
                    $( '#kleistad_cursus' ).dialog( 'option', 'title', cursus.naam ).dialog( 'open' );
                    $( 'input[name="cursus_id"]' ).val( cursus.id );
                    $( '#kleistad_cursus_naam' ).val( cursus.naam );
                    $( '#kleistad_cursus_docent' ).val( cursus.docent );
                    $( '#kleistad_cursus_start_datum' ).val( cursus.start_datum );
                    $( '#kleistad_cursus_eind_datum' ).val( cursus.eind_datum );
                    $( '#kleistad_cursus_start_tijd' ).val( cursus.start_tijd );
                    $( '#kleistad_cursus_eind_tijd' ).val( cursus.eind_tijd );
                    $( '#kleistad_cursuskosten' ).val( cursus.cursuskosten );
                    $( '#kleistad_inschrijfkosten' ).val( cursus.inschrijfkosten );
                    $( '#kleistad_inschrijfslug' ).val( cursus.inschrijfslug );
                    $( '#kleistad_indelingslug' ).val( cursus.indelingslug );
                    $( '#kleistad_maximum' ).val( cursus.maximum );
                    $( '#kleistad_draaien' ).prop( 'checked', String( cursus.technieken ).indexOf( 'Draaien' ) >= 0 );
                    $( '#kleistad_handvormen' ).prop( 'checked', String( cursus.technieken ).indexOf( 'Handvormen' ) >= 0 );
                    $( '#kleistad_boetseren' ).prop( 'checked', String( cursus.technieken ).indexOf( 'Boetseren' ) >= 0 );
                    $( '#kleistad_techniekkeuze' ).prop( 'checked', cursus.techniekkeuze > 0 );
                    $( '#kleistad_vol' ).prop( 'checked', cursus.vol > 0 );
                    $( '#kleistad_meer' ).prop( 'checked', cursus.meer > 0 );
                    $( '#kleistad_tonen' ).prop( 'checked', cursus.tonen > 0 );
                    $( '#kleistad_vervallen' ).prop( 'checked', cursus.vervallen > 0 );
					$( '#kleistad_indeling' ).children().remove().end();
					$( '#kleistad_restant_email' ).hide();
                    $.each(
                        ingedeeld, function( key, value ) {
							var cursisten = $( '#kleistad_indeling' );
							if ( cursus.gedeeld ) {
								if ( 0 ===  cursisten.children().length ) {
									cursisten.append( '<tr><th>Naam</th><th>Cursusgeld betaald</th><th>Restant email is verstuurd</th></tr>' );
									if ( 'actief' !== cursus.status && 'voltooid' !== cursus.status ) {
										$( '#kleistad_restant_email' ).show();
									}
								}
								cursisten.append( '<tr class="kleistad_cursist" ><td title="' + value.extra_info + '" >' +
									value.naam + '</td><td style="text-align:center" >' +
									( ( value.c_betaald ) ? '<span class="dashicons dashicons-yes"></span>' : '' ) + '</td><td style="text-align:center" >' +
									( ( value.restant_email ) ? '<span class="dashicons dashicons-yes"></span>' : '' ) + '</td></tr>'
								);
							} else {
								if ( 0 === cursisten.children().length ) {
									cursisten.append( '<tr><th>Naam</th></tr>' );
								}
								cursisten.append( '<tr class="kleistad_cursist" ><td  title="' + value.extra_info + '" >' + value.naam + '</td></tr>' );
							}
                        }
					);
                }
            );

            /**
             * Toon een lege popup dialoog voor een nieuwe cursus
             */
            $( 'body' ).on(
                'click', '#kleistad_cursus_toevoegen', function() {
                    $( '#kleistad_cursus' ).dialog( 'option', 'title', ' ' ).dialog( 'open' );
                    $( 'input[name="cursus_id"]' ).removeAttr( 'value' );
                    $( '#kleistad_cursus_naam' ).removeAttr( 'value' );
                    $( '#kleistad_cursus_docent' ).removeAttr( 'value' );
                    $( '#kleistad_cursus_start_datum' ).removeAttr( 'value' );
                    $( '#kleistad_cursus_eind_datum' ).removeAttr( 'value' );
                    $( '#kleistad_cursus_start_tijd' ).removeAttr( 'value' );
                    $( '#kleistad_cursus_eind_tijd' ).removeAttr( 'value' );
                    $( '#kleistad_cursuskosten' ).prop( 'defaultValue' );
                    $( '#kleistad_inschrijfkosten' ).prop( 'defaultValue' );
                    $( '#kleistad_inschrijfslug' ).prop( 'defaultValue' );
                    $( '#kleistad_indelingslug' ).prop( 'defaultValue' );
                    $( '#kleistad_maximum' ).prop( 'defaultValue' );
                    $( '#kleistad_draaien' ).prop( 'checked', false );
                    $( '#kleistad_handvormen' ).prop( 'checked', false );
                    $( '#kleistad_boetseren' ).prop( 'checked', false );
                    $( '#kleistad_techniekkeuze' ).prop( 'checked', false );
                    $( '#kleistad_vol' ).prop( 'checked', false );
                    $( '#kleistad_meer' ).prop( 'checked', false );
                    $( '#kleistad_tonen' ).prop( 'checked', false );
                    $( '#kleistad_vervallen' ).prop( 'checked', false );
                    $( '#kleistad_indeling' ).children().remove().end();
                }
            );
        }
    );

} )( jQuery );
