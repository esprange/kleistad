/* global kleistadData */

( function( $ ) {
    'use strict';

    /**
     * Toon het formulier om een reservering te maken, wijzigen of verwijderen.
     *
     * @param {array} formData bevat alle inhoud van de formuliervelden.
     * @returns {undefined}
     */
    function kleistadForm( formData ) {
        var row, aantal, stokerIds, stokerPercs, verdelingAantal;

        $( '#kleistad_oven_id' ).val( formData.oven_id );
		$( '#kleistad_oven' ).dialog( 'option', 'title', 'Reserveer de oven op ' + formData.dag + '-' + formData.maand + '-' + formData.jaar );
        $( '#kleistad_temperatuur' ).val( formData.temperatuur );
        $( '#kleistad_soortstook' ).val( formData.soortstook );
        $( '#kleistad_dag' ).val( formData.dag );
        $( '#kleistad_maand' ).val( formData.maand );
        $( '#kleistad_jaar' ).val( formData.jaar );
        $( '#kleistad_stoker_id' ).val( formData.gebruiker_id );
        $( '#kleistad_programma' ).val( formData.programma );
        $( '#kleistad_stoker' ).html( formData.gebruiker );

        verdelingAantal = Math.max( formData.verdeling.length, 4 );
        aantal = $( '.kleistad_medestoker_row' ).length;
        while ( aantal > verdelingAantal ) {
            $( '.kleistad_medestoker_row' ).last().remove();
            aantal--;
        }
        while ( aantal < verdelingAantal ) {
            row = $( '.kleistad_medestoker_row' ).first().clone( true );
            $( '.kleistad_medestoker_row' ).last().after( row );
            aantal++;
        }
        $( '[name=kleistad_stoker_id]' ).val( '0' );
        $( '[name=kleistad_stoker_perc]' ).val( '0' );

        stokerIds = $( '[name=kleistad_stoker_id]' ).toArray();
        stokerPercs = $( '[name=kleistad_stoker_perc]' ).toArray();

        formData.verdeling.forEach(
            function( item, index ) {
                stokerIds[index].value = item.id;
                stokerPercs[index].value = item.perc;
            }
        );

        if ( 1 === formData.gereserveerd ) {
            // $( '#kleistad_muteer' ).text( 'Wijzig' );
			$( '#kleistad_muteer' ).show();
			$( '#kleistad_voegtoe' ).hide();
			if ( 1 === formData.verwijderbaar ) {
                $( '#kleistad_tekst' ).text( 'Wil je de reservering wijzigen of verwijderen ?' );
				$( '#kleistad_verwijder' ).show();
	        } else {
                $( '#kleistad_tekst' ).text( 'Wil je de reservering wijzigen ?' );
                $( '#kleistad_verwijder' ).hide();
            }
        } else {
            $( '#kleistad_tekst' ).text( 'Wil je de reservering toevoegen ?' );
            // $( '#kleistad_muteer' ).text( 'Voeg toe' );
			$( '#kleistad_voegtoe' ).show();
			$( '#kleistad_muteer' ).hide();
            $( '#kleistad_verwijder' ).hide();
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
                stokerIds.forEach(
                    function( item, index ) {
                        if ( item === element ) {
                            selectedRow = index;

                            // Sanitize, als geen id, dan ook geen percentage.
                            if ( 0 === Number( item.value ) ) {
                                stokerPercs[index] = 0;
                            }
                        }
                        sum += Number( stokerPercs[index].value );
                    }
                );
                break;
            case 'kleistad_stoker_perc':
                stokerPercs.forEach(
                    function( item, index ) {
                        if ( item === element ) {
                            selectedRow = index;

                            // Sanitize, als geen id, dan ook geen percentage.
                            if ( 0 === Number( stokerIds[index].value ) ) {
                                item.value = 0;
                            } else {

                                // Sanitize, value moet tussen 0 en 100 liggen (html moet dit al afvangen).
                                item.value = Math.min( Math.max( +item.value, 0 ), 100 );
                            }
                        }
                        sum += Number( item.value );
                    }
                );
                break;
            default:
        }

        // Pas het percentage aan
        if ( 100 !== sum ) {
            stokerPercs[0].value = Number( stokerPercs[0].value ) - ( sum - 100 );
            if ( Number( stokerPercs[0].value ) < 0 ) {
                stokerPercs[selectedRow].value = Number( stokerPercs[selectedRow].value ) + Number( stokerPercs[0].value );
                stokerPercs[0].value = 0;
                window.alert( 'De hoofdstoker heeft niets meer te verdelen.' );
            }
        }
    }

    /**
     * Haal de inhoud van de tabel met reserveringen bij de server op.
     *
     * @param {int} ovenId oven waarvan de gegevens worden opgevraagd.
     * @param {int} maand maandnummer van de opgevraagde periode.
     * @param {int} jaar jaarnummer van de opgevraagde periode.
     * @returns {undefined}
     */
    function kleistadShow( ovenId, maand, jaar ) {
        $.ajax(
            {
                url: kleistadData.base_url + '/reserveer/',
                method: 'GET',
                beforeSend: function( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
                },
                data: {
                    maand: maand,
                    jaar: jaar,
                    oven_id: ovenId
                }
            }
        ).done(
            function( data ) {
                $( '#reserveringen' + data.oven_id ).html( data.html );
            }
        ).fail(
			function( jqXHR ) {
				if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
					window.alert( jqXHR.responseJSON.message );
					return;
				}
				window.alert( kleistadData.error_message );
			}
		);
	}

    /**
     * Wijzig of verwijder de reservering in de server.
     *
     * @param {string} method post, put of delete.
     * @returns {undefined}
     */
    function kleistadMuteer( method ) {
        var stokerPercs = $( '[name=kleistad_stoker_perc]' ).toArray(),
            stokerIds = $( '[name=kleistad_stoker_id]' ).toArray(),
            verdeling = [ ];
        stokerIds.forEach(
            function( item, index ) {
                if ( ( '0' !== stokerPercs[index].value ) || ( 0 === index ) ) {
                    verdeling.push( { id: +item.value, perc: +stokerPercs[index].value } );
                }
            }
        );
        $( '#kleistad_oven' ).dialog( 'close' );

        $.ajax(
            {
                url: kleistadData.base_url + '/reserveer/',
                method: method,
                beforeSend: function( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
                },
                data: {
                    dag: $( '#kleistad_dag' ).val(),
                    maand: $( '#kleistad_maand' ).val(),
                    jaar: $( '#kleistad_jaar' ).val(),
                    oven_id: $( '#kleistad_oven_id' ).val(),
                    temperatuur: $( '#kleistad_temperatuur' ).val(),
                    soortstook: $( '#kleistad_soortstook' ).val(),
                    gebruiker_id: $( '#kleistad_stoker_id' ).val(),
                    programma: $( '#kleistad_programma' ).val(),
                    verdeling: JSON.stringify( verdeling ) /* , opmerking: $('#kleistad_opmerking').val() */
                }
            }
        ).done(
            function( data ) {
                $( '#reserveringen' + data.oven_id ).html( data.html );
            }
        ).fail(
			function( jqXHR ) {
				if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
					window.alert( jqXHR.responseJSON.message );
					return;
				}
				window.alert( kleistadData.error_message );
			}
		);
	}

    $( document ).ready(
        function() {
            /**
             * Definieer het formulier.
             */
            $( '.kleistad_form_popup' ).each(
                function() {
                    $( this ).dialog(
                        {
                            autoOpen: false,
                            height: 550,
                            width: 360,
                            modal: true
                        }
                    );
                }
			);

//			$( '.ui-dialog-title' ).css( 'display', 'none' );

            /**
             * Toon de tabel.
             */
            $( '.kleistad_reserveringen' ).each(
                function() {
                    var ovenId = $( this ).data( 'oven_id' ),
                        maand = $( this ).data( 'maand' ),
                        jaar = $( this ).data( 'jaar' );
                    kleistadShow( ovenId, maand, jaar );
                }
            );

            /**
             * Verdeel de percentages als de gebruiker een percentage wijzigt.
             */
            $( '.kleistad_verdeel' ).change(
                function() {
                    kleistadVerdeel( this );
                }
            );

            /**
             * Wijzig de periode als de gebruiker op eerder of later klikt.
             */
            $( 'body' ).on(
                'click', '.kleistad_periode', function() {
                    var ovenId = $( this ).data( 'oven_id' ),
                        maand = $( this ).data( 'maand' ),
                        jaar = $( this ).data( 'jaar' );
                    kleistadShow( ovenId, maand, jaar );
                }
            );

            /**
             * Open een reservering (nieuw of bestaand).
             */
            $( 'body' ).on(
                'click', '.kleistad_box', function() {
                    $( '#kleistad_oven' ).dialog( 'open' );
                    kleistadForm( $( this ).data( 'form' ) );
                    return false;
                }
            );

            /**
             * Voeg een reservering toe.
             */
            $( 'body' ).on(
                'click', '.kleistad_voegtoe', function() {
                    kleistadMuteer( 'POST' );
                }
            );

            /**
             * Wijzig een reservering.
             */
            $( 'body' ).on(
                'click', '.kleistad_muteer', function() {
                    kleistadMuteer( 'PUT' );
                }
            );

            /**
             * Verwijder een reservering
             */
            $( 'body' ).on(
                'click', '.kleistad_verwijder', function() {
                    kleistadMuteer( 'DELETE' );
                }
            );

            /**
             * Sluit het formulier
             */
            $( 'body' ).on(
                'click', '.kleistad_sluit', function() {
                    $( '#kleistad_oven' ).dialog( 'close' );
                }
            );

            /**
             * Voeg een medestoker toe
             */
            $( 'body' ).on(
                'click', '#kleistad_stoker_toevoegen', function() {
                    var row = $( '.kleistad_medestoker_row' ).first().clone( true );
                    $( '.kleistad_medestoker_row' ).last().after( row );
                    $( '[name=kleistad_stoker_perc]' ).last().val( 0 );
                    return false;
                }
            );

        }
    );

} )( jQuery );
