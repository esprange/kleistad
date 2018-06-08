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
                    height: 650,
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
             * Toon de details van de geselecteerde cursus.
             */
            $( 'body' ).on(
                'click', '.kleistad_cursus_info', function() {
                    var cursus = $( this ).data( 'cursus' ),
                        wachtlijst = $( this ).data( 'wachtlijst' ),
                        ingedeeld = $( this ).data( 'ingedeeld' );
                    $( '#kleistad_cursus' ).dialog( 'open' );
                    $( 'input[name="cursus_id"]' ).val( cursus.id );
                    $( '#kleistad_cursus_naam' ).val( cursus.naam );
                    $( '#kleistad_cursus_docent' ).val( cursus.docent );
                    $( '#kleistad_cursus_start_datum' ).val( cursus.start_datum );
                    $( '#kleistad_cursus_eind_datum' ).val( cursus.eind_datum );
                    $( '#kleistad_cursus_start_tijd' ).val( cursus.start_tijd ); // .substr(0, 5)
                    $( '#kleistad_cursus_eind_tijd' ).val( cursus.eind_tijd ); // .substr(0, 5)
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
                    $( '#kleistad_wachtlijst' ).children().remove().end();
                    $.each(
                        wachtlijst, function( key, value ) {
                            $( '#kleistad_wachtlijst' ).append( new Option( value.naam, JSON.stringify( value ), false, false ) );
                        }
                    );
                    $( '#kleistad_indeling' ).children().remove().end();
                    $.each(
                        ingedeeld, function( key, value ) {
                            var option = new Option( value.naam, JSON.stringify( value ), false, false );
                            option.style.backgroundColor = 'lightgreen';
                            option.style.fontWeight = '700'; // Bold
                            $( '#kleistad_indeling' ).append( option );
                        }
                    );
                }
            );

            /**
             * Toon een lege popup dialoog voor een nieuwe cursus
             */
            $( 'body' ).on(
                'click', '#kleistad_cursus_toevoegen', function() {
                    $( '#kleistad_cursus' ).dialog( 'open' );
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
                    $( '#kleistad_wachtlijst' ).children().remove().end();
                    $( '#kleistad_indeling' ).children().remove().end();
                }
            );

            /**
             * Wijzig de cursus
             */
            $( 'body' ).on(
                'click', '[name="kleistad_submit_cursus_beheer"]', function() {
                    var element,
                        options = $( '#kleistad_indeling option' ),
                        cursisten = $.map(
                            options, function( option ) {
                                element = JSON.parse( option.value );
                                return Number( element.id );
                            }
                        );
                    $( '#kleistad_indeling_lijst' ).val( JSON.stringify( cursisten ) );
                }
            );

            /**
             * Wissel een cursist van wachtlijst naar indeling en v.v.
             */
            $( 'body' ).on(
                'click', '#kleistad_wissel_indeling', function() {
                    var element,
                        ingedeeld = $( '#kleistad_indeling option:selected' ),
                        wachtend = $( '#kleistad_wachtlijst option:selected' );
                    if ( ingedeeld.length ) {
                        element = JSON.parse( ingedeeld.val() );
                        if ( 0 === element.ingedeeld ) {
                            return ! ingedeeld.remove().appendTo( '#kleistad_wachtlijst' );
                        }
                    }
                    if ( wachtend.length ) {
                        return ! wachtend.remove().appendTo( '#kleistad_indeling' );
                    }
                    return false;
                }
            );

            /**
             * Toon de wachtende cursist info indien geselecteerd.
             */
            $( 'body' ).on(
                'click', '#kleistad_wachtlijst', function() {
                    var cursist = $( 'option:selected', this );
                    $( '#kleistad_indeling option:selected' ).prop( 'selected', false );
                    $( '#kleistad_cursist_technieken' ).empty();
                    $( '#kleistad_cursist_opmerking' ).empty();
                    if ( cursist.length ) {
                        kleistadToonCursist( cursist );
                    }
                }
            );

            /**
             * Toon de ingedeelde cursist info indien geselecteerd.
             */
            $( 'body' ).on(
                'click', '#kleistad_indeling', function() {
                    var cursist = $( 'option:selected', this );
                    $( '#kleistad_wachtlijst option:selected' ).prop( 'selected', false );
                    $( '#kleistad_cursist_technieken' ).empty();
                    $( '#kleistad_cursist_opmerking' ).empty();
                    if ( cursist.length ) {
                        kleistadToonCursist( cursist );
                    }
                }
            );

        }
    );

    /**
     * Toon cursus en abonnee detail informatie.
     *
     * @param {object} cursist de geselecteerde cursist.
     */
    function kleistadToonCursist( cursist ) {
        var techniekTekst,
            element = JSON.parse( cursist.val() ),
            opmerking = element.opmerking,
            technieken = element.technieken;

        if ( null !== technieken ) {
            techniekTekst = '<p>Gekozen technieken : ';
            $.each(
                technieken, function( key, value ) {
                    techniekTekst += value + ' ';
                }
            );
            techniekTekst += '</p>';
            $( '#kleistad_cursist_technieken' ).html( techniekTekst );
        }
        if ( opmerking.length > 0 ) {
            $( '#kleistad_cursist_opmerking' ).html( '<p>Opmerking : ' + opmerking + '</p>' );
        }
    }

} )( jQuery );
