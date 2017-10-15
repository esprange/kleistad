/* global kleistad_data */

( function ( $ ) {
    'use strict';

    /**
     * Toon het formulier om een reservering te maken, wijzigen of verwijderen.
     * 
     * @param {array} formData bevat alle inhoud van de formuliervelden.
     * @returns {undefined}
     */
    function kleistadForm(formData) {
        var row, aantal, stokerIds, stokerPercs;

        $('#kleistad_oven_id').val(formData.oven_id);
        $('#kleistad_wanneer').text(formData.dag + '-' + formData.maand + '-' + formData.jaar);
        $('#kleistad_temperatuur').val(formData.temperatuur);
        $('#kleistad_soortstook').val(formData.soortstook);
        $('#kleistad_dag').val(formData.dag);
        $('#kleistad_maand').val(formData.maand);
        $('#kleistad_jaar').val(formData.jaar);
        $('#kleistad_gebruiker_id').val(formData.gebruiker_id);
        $('#kleistad_programma').val(formData.programma);
        /* $('#kleistad_opmerking').val(formData.opmerking); */
        $('#kleistad_stoker').html(formData.gebruiker);
        $('#kleistad_1e_stoker').val(formData.gebruiker_id);

        aantal = $( '[name=kleistad_medestoker_row]' ).length;
        while ( aantal > Math.max( formData.verdeling.length, 4 ) ) {
            $( '[name=kleistad_medestoker_row]' ).last().remove();
            aantal--;
        }
        while ( aantal < Math.max( formData.verdeling.length, 4 ) ) {
            row = $( '[name=kleistad_medestoker_row]' ).first().clone( true );
            $( '[name=kleistad_medestoker_row]' ).last().after( row );
            aantal++;
        }
        $( '[name=kleistad_stoker_id]' ).val( '0' );
        $( '[name=kleistad_stoker_perc]' ).val( '0' );

        stokerIds = $( '[name=kleistad_stoker_id]' ).toArray();
        stokerPercs = $( '[name=kleistad_stoker_perc]' ).toArray();

        formData.verdeling.forEach( function ( item, index ) {
            stokerIds[index].value = item.id;
            stokerPercs[index].value = item.perc;
        });        

        if (formData.gereserveerd === 1) {
            $('#kleistad_muteer').text('Wijzig');
            if (formData.verwijderbaar === 1) {
                $('#kleistad_tekst').text('Wil je de reservering wijzigen of verwijderen ?');
                $('#kleistad_verwijder').show();
            } else {
                $('#kleistad_tekst').text('Wil je de reservering wijzigen ?');
                $('#kleistad_verwijder').hide();
            }
        } else {
            $('#kleistad_tekst').text('Wil je de reservering toevoegen ?');
            $('#kleistad_muteer').text('Voeg toe');
            $('#kleistad_verwijder').hide();
        }
    }

    /**
     * Pas de percentages aan in het formulier zodanig dat het totaal 100% blijft.
     * 
     * @param {object} element gewijzigd percentage veld.
     * @returns {undefined}
     */
    function kleistadVerdeel( element ) {
        var stokerPercs = $( '[name=kleistad_stoker_perc]' ).toArray(),
            stokerIds = $( '[name=kleistad_stoker_id]' ).toArray(),
            sum = 0,
            selectedRow = 0;

        /*
         * Vind de geselecteerde row en bepaal de som. Het element kan zowel het select field als het percentage zijn
         * Voer tevens sanitizing uit.
         */
        switch ( element.name ) {
            case 'kleistad_stoker_id':
                stokerIds.forEach( function ( item, index ) {
                    if ( item === element ) {
                        selectedRow = index;
                        // sanitize, als geen id, dan ook geen percentage
                        if ( Number(item.value) === 0 ) {
                            stokerPercs[index] = 0;
                        }
                    }
                    sum += Number(stokerPercs[index].value);
                } );
                break;
            case 'kleistad_stoker_perc':
                stokerPercs.forEach( function ( item, index ) {
                    if ( item === element ) {
                        selectedRow = index;
                        // sanitize, als geen id, dan ook geen percentage
                        if (Number(stokerIds[index].value) === 0) {
                            item.value = 0;
                        } else {
                            // sanitize, value moet tussen 0 en 100 liggen (html moet dit al afvangen) 
                            item.value = Math.min( Math.max( +item.value, 0 ), 100 );
                        }
                    }
                    sum += Number(item.value);
                } );
                break;
            default:
                ;
        }
        
        // Pas het percentage aan
        if (sum !== 100) {
            stokerPercs[0].value = Number(stokerPercs[0].value) - ( sum - 100 );
            if (Number(stokerPercs[0].value) < 0) {
                stokerPercs[selectedRow].value = Number(stokerPercs[selectedRow].value) + Number(stokerPercs[0].value);
                stokerPercs[0].value = 0;
                alert ('De hoofdstoker heeft niets meer te verdelen.');
            }
        }
    }

    /**
     * Toon foutmelding als ajax request faalt.
     * 
     * @param {string} message foutmelding tekst.
     * @returns {undefined}
     */
    function kleistadFalen(message) {
        // rapporteer falen
        alert(message);
    }

    /**
     * Haal de inhoud van de tabel met reserveringen bij de server op.
     * 
     * @param {int} ovenId oven waarvan de gegevens worden opgevraagd.
     * @param {int} maand maandnummer van de opgevraagde periode.
     * @param {int} jaar jaarnummer van de opgevraagde periode.
     * @returns {undefined}
     */
    function kleistadShow(ovenId, maand, jaar) {
        $.ajax({
            url: kleistad_data.base_url + '/show/',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', kleistad_data.nonce);
            },
            data: {
                maand: maand,
                jaar: jaar,
                oven_id: ovenId
            }
        }).done(function (data) {
            $('#reserveringen' + data.oven_id).html(data.html);
        }).fail(function (jqXHR, textStatus, errorThrown) {
            if ('undefined' !== typeof jqXHR.responseJSON.message) {
                kleistadFalen(jqXHR.responseJSON.message);
                return;
            }
            kleistadFalen(kleistad_data.error_message);
        });
    }

    /**
     * Wijzig of verwijder de reservering in de server. 
     * 
     * @param {int} wijzigen als 1 dan wijzigen en -1 dan verwijderen.
     * @returns {undefined}
     */
    function kleistadMuteer(wijzigen) {
        var stokerPercs = $('[name=kleistad_stoker_perc]').toArray(),
            stokerIds = $('[name=kleistad_stoker_id]').toArray(),
            verdeling = [];
        stokerIds.forEach(function (item, index) {
            if ((stokerPercs[index].value !== '0') || (index === 0)) {
                verdeling.push({ id: +item.value, perc: +stokerPercs[index].value });
            }
        });
        $('#kleistad_oven').dialog('close');

        $.ajax({
            url: kleistad_data.base_url + '/reserveer/',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', kleistad_data.nonce);
            },
            data: {
                dag: $('#kleistad_dag').val(),
                maand: $('#kleistad_maand').val(),
                jaar: $('#kleistad_jaar').val(),
                oven_id: $('#kleistad_oven_id').val() * wijzigen,
                temperatuur: $('#kleistad_temperatuur').val(),
                soortstook: $('#kleistad_soortstook').val(),
                gebruiker_id: $('#kleistad_gebruiker_id').val(),
                programma: $('#kleistad_programma').val(),
                verdeling: JSON.stringify(verdeling) /* , opmerking: $('#kleistad_opmerking').val() */
            }
        }).done(function (data) {
            $('#reserveringen' + data.oven_id).html(data.html);
        }).fail(function (jqXHR, textStatus, errorThrown) {
            if ('undefined' !== typeof jqXHR.responseJSON.message) {
                kleistadFalen(jqXHR.responseJSON.message);
                return;
            }
            kleistadFalen( kleistad_data.error_message );
        } );
    }

    $( document ).ready( function () {
        /**
         * Definieer het formulier.
         */
        $( '.kleistad_form_popup' ).each( function () {
            $( this ).dialog( {
                autoOpen: false,
                height: 550,
                width: 360,
                modal: true
            } );
        } );
        
        /**
         * Toon de tabel.
         */
        $( '.kleistad_reserveringen' ).each( function () {
            var ovenId = $( this ).data( 'oven_id' ),
                maand = $( this ).data( 'maand' ),
                jaar = $( this ).data( 'jaar' );
            kleistadShow( ovenId, maand, jaar );
        } );

        /**
         * Verdeel de percentages als de gebruiker een percentage wijzigt.
         */
        $( '.kleistad_verdeel' ).change( function () {
            kleistadVerdeel( this );
        } );

        /**
         * Wijzig de periode als de gebruiker op eerder of later klikt.
         */
        $( 'body' ).on( 'click', '.kleistad_periode', function () {
            var ovenId = $( this ).data( 'oven_id' ),
                maand = $( this ).data( 'maand' ),
                jaar = $( this ).data( 'jaar' );
            kleistadShow( ovenId, maand, jaar );
        } );

        /**
         * Open een reservering (nieuw of bestaand).
         */
        $( 'body' ).on( 'click', '.kleistad_box', function () {
            $( '#kleistad_oven' ).dialog( 'open' );
            kleistadForm( $( this ).data( 'form' ) );
            return false;
        } );

        /**
         * Wijzig een reservering.
         */
        $( 'body' ).on( 'click', '.kleistad_muteer', function () {
            kleistadMuteer( 1 );
        } );

        /**
         * Verwijder een reservering
         */
        $( 'body' ).on( 'click', '.kleistad_verwijder', function () {
            kleistadMuteer( -1 );
        } );

        /**
         * Sluit het formulier
         */
        $( 'body' ).on( 'click', '.kleistad_sluit', function () {
            $( '#kleistad_oven' ).dialog( 'close' );
        } );

        /**
         * Voeg een medestoker toe
         */
        $( 'body' ).on( 'click', '#kleistad_stoker_toevoegen', function () {
            var row = $( '[name=kleistad_medestoker_row]' ).first().clone( true );
            $( '[name=kleistad_medestoker_row]' ).last().after( row );
            $( '[name=kleistad_stoker_perc]' ).last().val( 0 );
            return false;
        } );

    } );

} )( jQuery );
