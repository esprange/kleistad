/* global kleistadData, detectTap */

( function( $ ) {
    'use strict';

	/**
	 * Vind de stoker's naam op basis van het wordpress id in de lijst van stokers.
	 *
	 * @param {int} id Het Wordpress id van de stoker
	 */
	function vindStokerNaam( id ) {
		var stokers  = $( '#kleistad_reserveringen' ).data( 'stokers' );
		return stokers.filter( function( stoker ) {
			return ( stoker.id === id );
		})[0].naam;
	}

	/**
	 * Maak een selectie lijst van medestokers.
	 *
	 * @param {boolean} empty Bepaalt of de lijst wel/niet een lege optie mag bevatten.
	 * @param {int}     id    Id van de huidige medestoker.
	 */
	function selectStoker( empty, id ) {
		var i, t, stokers =  $( '#kleistad_reserveringen' ).data( 'stokers' );
		t = '<select name="kleistad_stoker_id" class="kleistad_verdeling" >' + ( empty ? '<option value="0"></option>' : '' );
		for ( i = 0; i < stokers.length; i++ ) {
			t += '<option value="' + stokers[i].id + '" ' + ( stokers[i].id === id ? 'selected >' : '>' ) + stokers[i].naam + '</option>';
		}
		t += '</select>';
		return t;
	}

	/**
	 * Toon het formulier van de stookgegevens zodat ze gewijzigd kunnen worden.
	 *
	 * @param {array} formData
	 */
	function wijzigen( formData ) {
		var row, stokerVeld, percVeld;
		$( '#kleistad_reservering table > thead' ).
			append( '<tr><td><label>Soort stook</label></td><td colspan="2"><select id="kleistad_soortstook">' +
				'<option value="Biscuit" ' + ( 'Biscuit' === formData.soortstook ? 'selected>' : '>' ) + 'Biscuit</option>' +
				'<option value="Glazuur" ' + ( 'Glazuur' === formData.soortstook ? 'selected>' : '>' ) + 'Glazuur</option>' +
				'<option value="Overig" ' + ( 'Overig' === formData.soortstook ? 'selected>' : '>' ) + 'Overig</option>' +
				'</select></td></tr>' +
				'<tr><td colspan="2"><label>Temperatuur &nbsp; &deg;C</label></td><td><input id="kleistad_temperatuur" type="number" min="0" max="1400" value="' + formData.temperatuur + '" ></td></tr>' +
				'<tr><td colspan="2"><label>Programma</label></td><td><input id="kleistad_programma" type="number" min="0" max="99" value="' + formData.programma + '" ></td></tr>' );
		if ( $( '#kleistad_reserveringen' ).data( 'override' ) ) {
			$( '#kleistad_soortstook' ).append( '<option value="Onderhoud" ' + ( 'Onderhoud' === formData.soortstook ? 'selected>' : '>' ) + 'Onderhoud</option>' );
		}

		stokerVeld = $( '#kleistad_reserveringen' ).data( 'override' ) ?
			'<td>' + selectStoker( false, formData.verdeling[0].id ) + '</td>' :
			'<td style="white-space:nowrap;text-overflow:ellipsis;overflow:hidden;"><input name="kleistad_stoker_id" type="hidden" value="' + formData.verdeling[0].id + '" >' + vindStokerNaam( formData.verdeling[0].id ) + '</td>';
		percVeld = '<td><input name="kleistad_stoker_perc" size="5" tabindex="-1" readonly style="border:0px;outline:0px;" value="' + formData.verdeling[0].perc + '" ></td>';

		$( '#kleistad_reservering table > tbody' ).append( '<tr><td><label>Stoker</label></td>' + stokerVeld + percVeld + '</tr>' );

		for ( row = 1; row < formData.verdeling.length; row++ ) {
			$( '#kleistad_reservering table > tbody > tr:last' ).
			after( '<tr><td><label>Medestoker</label></td><td>' +  selectStoker( true, formData.verdeling[row].id ) +
				'<td><input name="kleistad_stoker_perc" class="kleistad_verdeling" type="number" min="0" max="100" size="3" value="' + formData.verdeling[row].perc + '" ></td></tr>' );
		}

	}

	/**
	 * Toon de stookgegevens, ze zijn niet te wijzigen
	 *
	 * @param {array} formData
	 */
	function lezen( formData ) {
		$( '#kleistad_reservering table > thead' ).
		append( '<tr><td colspan="2">Soort stook</td><td style="text-align:right;">' + formData.soortstook + '</td></tr>' +
			'<tr><td colspan="2">Temperatuur &nbsp; &deg;C</td><td style="text-align:right;">' + formData.temperatuur + '</td></tr>' +
			'<tr><td colspan="2">Programma</td><td style="text-align:right;">' + formData.programma + '</td></tr>' );
		formData.verdeling.forEach(
			function( item, index ) {
				$( '#kleistad_reservering table > tbody' ).
					append( '<tr style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;"><td><label>' +
						( 0 === index ? 'Stoker' : 'Medestoker' ) + '</label></td><td>' + vindStokerNaam( item.id ) +
						'</td><td style="text-align:right;">' + item.perc + '</td></tr>' );
			}
		);
	}

    /**
     * Toon het formulier om een reservering te maken, wijzigen of verwijderen.
     *
     * @param {array} formData bevat alle inhoud van de formuliervelden.
     * @returns {undefined}
     */
    function kleistadForm( formData ) {
		var logica =
		{
			ongebruikt: {},
			reserveerbaar: {
				readonly: false,
				tekst:    'Wil je de reservering toevoegen ?',
				focus:    'kleistad_soortstook',
				actief:   '#kleistad_voegtoe,#kleistad_stoker_toevoegen'
			},
			verwijderbaar: {
				readonly: false,
				tekst:    'Wil je de reservering wijzigen of verwijderen ?',
				focus:    'kleistad_soortstook',
				actief:   '#kleistad_muteer,#kleistad_verwijder,#kleistad_stoker_toevoegen'
			},
			alleenlezen: {
				readonly: true,
				tekst:    'Deze reservering is niet door u te wijzigen',
				focus:    '#kleistad_sluit'
			},
			wijzigbaar: {
				readonly: false,
				tekst:    'Wil je de reservering wijzigen ?',
				focus:    'kleistad_soortstook',
				actief:   '#kleistad_muteer,#kleistad_stoker_toevoegen'
			},
			definitief: {
				readonly: true,
				tekst:    'Deze reservering is definitief',
				focus:    '#kleistad_sluit'
			}
		};

		$( '#kleistad_reservering .kleistad_button' ).hide();
		$( '#kleistad_reservering table > tbody, #kleistad_reservering table > thead' ).empty();
		$( '#kleistad_reservering' ).dialog( 'option', 'title', $( '#kleistad_reserveringen' ).data( 'oven-naam' ) + ' op ' + formData.dag + '-' + formData.maand + '-' + formData.jaar );
		$( '#kleistad_dag' ).val( formData.dag );
        $( '#kleistad_maand' ).val( formData.maand );
        $( '#kleistad_jaar' ).val( formData.jaar );
        $( '#kleistad_stoker_id' ).val( formData.gebruiker_id );
		if ( logica[formData.status].readonly ) {
			lezen( formData );
		} else {
			wijzigen( formData );
		}
		$( '#kleistad_tekst' ).text( logica[formData.status].tekst );
		$( logica[formData.status].actief ).show();
		$( logica[formData.status].focus ).focus();
		$( '.ui-dialog-titlebar' ).removeClassWildcard( 'kleistad_reservering' );
		$( '.ui-dialog-titlebar' ).addClass( formData.kleur );

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
                stokerIds.forEach( function( item, index ) {
					if ( item === element ) {
						selectedRow = index;

						// Sanitize, als geen id, dan ook geen percentage.
						if ( 0 === Number( item.value ) ) {
							stokerPercs[index].value = 0;
						}
					}
					sum += Number( stokerPercs[index].value );
				});
                break;
            case 'kleistad_stoker_perc':
                stokerPercs.forEach( function( item, index ) {
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
				});
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
     */
    function kleistadShow( maand, jaar ) {
        $.ajax(
            {
                url: kleistadData.base_url + '/reserveer/',
                method: 'GET',
                beforeSend: function( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
                },
                data: {
                    maand:   maand,
                    jaar:    jaar,
                    oven_id: $( '#kleistad_oven_id' ).val()
                }
            }
        ).done(
            function( data ) {
				$( '#kleistad_reserveringen tbody' ).html( data.html );
				$( '#kleistad_reserveringen' ).data( 'maand', data.maand ).data( 'jaar', data.jaar );
				$( '#kleistad_periode' ).html( data.periode );
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
        $( '#kleistad_reservering' ).dialog( 'close' );

        $.ajax(
            {
                url: kleistadData.base_url + '/reserveer/',
                method: method,
                beforeSend: function( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
                },
                data: {
					reservering: {
						dag:          $( '#kleistad_dag' ).val(),
						maand:        $( '#kleistad_maand' ).val(),
						jaar:         $( '#kleistad_jaar' ).val(),
						temperatuur:  $( '#kleistad_temperatuur' ).val(),
						soortstook:   $( '#kleistad_soortstook' ).val(),
						gebruiker_id: $( '#kleistad_stoker_id' ).val(),
						programma:    $( '#kleistad_programma' ).val(),
						verdeling:    verdeling
					},
					oven_id: $( '#kleistad_oven_id' ).val()
                }
            }
        ).done(
            function( data ) {
				$( '#kleistad_reserveringen tbody' ).html( data.html );
				$( '#kleistad_reserveringen' ).data( 'maand', data.maand ).data( 'jaar', data.jaar );
				$( '#kleistad_periode' ).html( data.periode );
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
             * Toon de tabel.
             */
			if ( 'undefined' !== typeof $( '#kleistad_reserveringen' ).data( 'maand' ) ) {
				kleistadShow( $( '#kleistad_reserveringen' ).data( 'maand' ), $( '#kleistad_reserveringen' ).data( 'jaar' )	);
			}

			/**
             * Wijzig de periode als de gebruiker op eerder of later klikt.
             */
            $( '#kleistad_reserveringen' ).on( 'click', '.kleistad_periode', function() {
					kleistadShow(
						parseInt( $( '#kleistad_reserveringen' ).data( 'maand' ), 10 ) + parseInt( $( this ).val(), 10 ),
						$( '#kleistad_reserveringen' ).data( 'jaar' )
					);
                }
            );

            /**
             * Verander de opmaak bij hovering.
             */
            $( '#kleistad_reserveringen tbody' ).on( 'hover', 'tr[data-form]', function() {
					$( this ).toggleClass( 'kleistad_hover_reservering' );
				}
            );

			/**
             * Open een reservering (nieuw of bestaand).
             */
            $( '#kleistad_reserveringen tbody' ).on( 'click touchend', 'tr[data-form]', function( event ) {
					if ( 'click' === event.type || detectTap ) {
						$( '#kleistad_reservering' ).dialog( 'open' );
						kleistadForm( $( this ).data( 'form' ) );
					}
				return false;
                }
            );

            /**
             * Definieer het formulier.
             */
            $( '#kleistad_reservering' ).dialog( {
					autoOpen: false,
					height: 'auto',
					width: 360,
					modal: true
				}
			);

			/**
             * Verdeel de percentages als de gebruiker een percentage wijzigt.
             */
            $( '#kleistad_reservering' ).on( 'change', '.kleistad_verdeling', function() {
                    kleistadVerdeel( this );
                }
            );

            /**
             * Voeg een reservering toe.
             */
            $( '#kleistad_reservering' ).on( 'click', '#kleistad_voegtoe', function() {
                    kleistadMuteer( 'POST' );
                }
            );

            /**
             * Wijzig een reservering.
             */
            $( '#kleistad_reservering' ).on( 'click', '#kleistad_muteer', function() {
                    kleistadMuteer( 'PUT' );
                }
            );

            /**
             * Verwijder een reservering
             */
            $( '#kleistad_reservering' ).on( 'click', '#kleistad_verwijder', function() {
                    kleistadMuteer( 'DELETE' );
                }
            );

            /**
             * Sluit het formulier
             */
            $( '#kleistad_reservering' ).on( 'click', '#kleistad_sluit', function() {
                    $( '#kleistad_reservering' ).dialog( 'close' );
                }
            );

            /**
             * Voeg een medestoker toe
             */
            $( '#kleistad_reservering' ).on( 'click', '#kleistad_stoker_toevoegen', function() {
				$( '#kleistad_reservering table > tbody > tr:last' ).
					after( '<tr><td><label>Medestoker</label></td><td>' +  selectStoker( true, 0 ) +
						'<td><input name="kleistad_stoker_perc" class="kleistad_verdeling" type="number" min="0" max="100" size="3" value="0" ></td></tr>' );
					$( '[name=kleistad_stoker_id]:last' ).focus();
                    return false;
                }
            );

        }
    );

} )( jQuery );
