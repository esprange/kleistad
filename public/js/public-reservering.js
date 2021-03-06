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
			return ( parseInt( stoker.ID, 10 ) === id );
		})[0].display_name;
	}

	/**
	 * Maak een selectie lijst van medestokers.
	 *
	 * @param {boolean} empty Bepaalt of de lijst wel/niet een lege optie mag bevatten.
	 * @param {int}     id    Id van de huidige medestoker.
	 */
	function selectStoker( empty, id ) {
		var i, t, stokers =  $( '#kleistad_reserveringen' ).data( 'stokers' );
		t = '<select name="stoker_id" class="kleistad_verdeling" >' + ( empty ? '<option value="0"></option>' : '' );
		for ( i = 0; i < stokers.length; i++ ) {
			t += '<option value="' + stokers[i].ID + '" ' + ( parseInt( stokers[i].ID, 10 ) === id ? 'selected >' : '>' ) + stokers[i].display_name + '</option>';
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
			append( '<tr><th><label>Soort stook</label></th><td colspan="2"><select id="kleistad_soortstook">' +
				'<option value="Biscuit" ' + ( 'Biscuit' === formData.soortstook ? 'selected' : '' ) + ' >Biscuit</option>' +
				'<option value="Glazuur" ' + ( 'Glazuur' === formData.soortstook ? 'selected' : '' ) + ' >Glazuur</option>' +
				'<option value="Overig" ' + ( 'Overig' === formData.soortstook ? 'selected' : '' ) + ' >Overig</option>' +
				'</select></td></tr>' +
				'<tr><th colspan="2"><label>Temperatuur &nbsp; &deg;C</label></th><td><input id="kleistad_temperatuur" name="temperatuur" type="number" min="100" max="1400" required value="' + formData.temperatuur + '" ></td></tr>' +
				'<tr><th colspan="2"><label>Programma</label></th><td><input id="kleistad_programma" type="number" min="0" max="99" value="' + formData.programma + '" ></td></tr>' );
		if ( $( '#kleistad_reserveringen' ).data( 'override' ) ) {
			$( '#kleistad_soortstook' ).append( '<option value="Onderhoud" ' + ( 'Onderhoud' === formData.soortstook ? 'selected' : '' ) + ' >Onderhoud</option>' );
		}

		stokerVeld = $( '#kleistad_reserveringen' ).data( 'override' ) ?
			'<td>' + selectStoker( false, formData.verdeling[0].medestoker ) + '</td>' :
			'<td style="white-space:nowrap;text-overflow:ellipsis;overflow:hidden;"><input name="stoker_id" type="hidden" value="' + formData.verdeling[0].medestoker + '" >' + vindStokerNaam( formData.verdeling[0].medestoker ) + '</td>';
		percVeld = '<td><input name="stoker_perc" type="number" tabindex="-1" readonly style="border:0;outline:0;" value="' + formData.verdeling[0].percentage + '" ></td>';

		$( '#kleistad_reservering table > tbody' ).append( '<tr><th><label>Stoker</label></th>' + stokerVeld + percVeld + '</tr>' );

		for ( row = 1; row < formData.verdeling.length; row++ ) {
			$( '#kleistad_reservering table > tbody > tr:last' ).
			after( '<tr><th><label>Medestoker</label></th><td>' +  selectStoker( true, formData.verdeling[row].medestoker ) +
				'<td><input name="stoker_perc" class="kleistad_verdeling" type="number" min="0" max="100" value="' + formData.verdeling[row].percentage + '" ></td></tr>' );
		}

	}

	/**
	 * Toon de stookgegevens, ze zijn niet te wijzigen
	 *
	 * @param {array} formData
	 */
	function lezen( formData ) {
		$( '#kleistad_reservering table > thead' ).
		append( '<tr><th colspan="2">Soort stook</th><td style="text-align:right;">' + formData.soortstook + '</td></tr>' +
			'<tr><th colspan="2">Temperatuur &nbsp; &deg;C</th><td style="text-align:right;">' + formData.temperatuur + '</td></tr>' +
			'<tr><th colspan="2">Programma</th><td style="text-align:right;">' + formData.programma + '</td></tr>' );
		formData.verdeling.forEach(
			function( item, index ) {
				$( '#kleistad_reservering table > tbody' ).
					append( '<tr style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;"><th><label>' +
						( 0 === index ? 'Stoker' : 'Medestoker' ) + '</label></th><td>' + vindStokerNaam( item.medestoker ) +
						'</td><td style="text-align:right;">' + item.percentage + '</td></tr>' );
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

		$( '#kleistad_voegtoe, #kleistad_verwijder, #kleistad_muteer' ).hide();
		$( '#kleistad_reservering table > tbody, #kleistad_reservering table > thead' ).empty();
		$( '#kleistad_reservering' ).dialog( 'option', 'title', $( '#kleistad_reserveringen' ).data( 'oven-naam' ) + ' op ' + formData.dag + '-' + formData.maand + '-' + formData.jaar );
		$( '#kleistad_dag' ).val( formData.dag );
        $( '#kleistad_maand' ).val( formData.maand );
        $( '#kleistad_jaar' ).val( formData.jaar );
        $( '#stoker_id' ).val( formData.gebruiker_id );
		if ( logica[formData.status].readonly ) {
			lezen( formData );
		} else {
			wijzigen( formData );
		}
		$( '#kleistad_tekst' ).text( logica[formData.status].tekst );
		$( logica[formData.status].actief ).show();
		$( logica[formData.status].focus ).trigger( 'focus' );
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
		var sum       = 0,
			vervallen = false,
			$stokers, $medestoker, hoofdstokerPerc, medestokerPerc;

		if ( 0 === parseInt( $( element ).val(), 10 ) ) { // Als het percentage of het id gelijk wordt aan 0, dan is vervalt de medestoker.
			$( element ).parents( 'tr' ).remove();
			vervallen = true;
		}

		$stokers = $( '[name=stoker_perc]' );
		$stokers.each( function() {
			sum += parseInt( $( this ).val(), 10 );
		} );

		hoofdstokerPerc = parseInt( $stokers.first().val(), 10 ) - ( sum - 100 );

		if ( ! vervallen ) {
			$medestoker     = ( 'stoker_id' === element.name ) ? $( element ).parents( 'tr' ).find( '[name=stoker_perc]' ) : $( element );
			medestokerPerc  = parseInt( $medestoker.val(), 10 );
			if ( hoofdstokerPerc < 0 ) {
				medestokerPerc  = medestokerPerc + hoofdstokerPerc;
				hoofdstokerPerc = 0;
				window.alert( 'De hoofdstoker heeft niets meer te verdelen.' );
			}
			$medestoker.val( medestokerPerc );
		}
		$stokers.first().val( hoofdstokerPerc );
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
				$( '#kleistad_reserveringen tbody' ).html( data.content );
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
        var stokerPercs = $( '[name=stoker_perc]' ).toArray(),
            stokerIds = $( '[name=stoker_id]' ).toArray(),
            verdeling = [ ];
        stokerIds.forEach(
            function( item, index ) {
                if ( ( '0' !== stokerPercs[index].value ) || ( 0 === index ) ) {
                    verdeling.push( { id: +item.value, perc: +stokerPercs[index].value } );
                }
            }
		);
		if ( 'Onderhoud' !== $( '#kleistad_soortstook' ).val() ) {
			if ( ! $( '#kleistad_temperatuur' )[0].checkValidity() ) {
				$( '#kleistad_temperatuur' )[0].reportValidity();
				return;
			}
		}
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
						gebruiker_id: $( '#stoker_id' ).val(),
						programma:    $( '#kleistad_programma' ).val(),
						verdeling:    verdeling
					},
					oven_id: $( '#kleistad_oven_id' ).val()
                }
            }
        ).done(
            function( data ) {
				$( '#kleistad_reserveringen tbody' ).html( data.content );
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

    $( function()
		{
			if ( window.navigator.userAgent === 'msie' ) {
				$( '#kleistad_reserveringen' ).hide();
				$( '#kleistad_geen_ie').show();
			}

			/**
             * Toon de tabel.
             */
			if ( 'undefined' !== typeof $( '#kleistad_reserveringen' ).data( 'maand' ) ) {
				kleistadShow( $( '#kleistad_reserveringen' ).data( 'maand' ), $( '#kleistad_reserveringen' ).data( 'jaar' )	);
			}

			$( '#kleistad_reserveringen' )
			/**
             * Wijzig de periode als de gebruiker op eerder of later klikt.
             */
			.on( 'click', '.kleistad_periode', function() {
					kleistadShow(
						parseInt( $( '#kleistad_reserveringen' ).data( 'maand' ), 10 ) + parseInt( $( this ).val(), 10 ),
						$( '#kleistad_reserveringen' ).data( 'jaar' )
					);
                }
            )
			/**
             * Open een reservering (nieuw of bestaand).
             */
            .on( 'click touchend', 'tr[data-form]', function( event ) {
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
					width: 400,
					modal: true,
					classes: {
						'ui-button': 'kleistad-button'
					}
				}
			)
			/**
             * Verdeel de percentages als de gebruiker een percentage wijzigt.
             */
            .on( 'change', '.kleistad_verdeling', function() {
                    kleistadVerdeel( this );
                }
            )
            /**
             * Voeg een reservering toe.
             */
            .on( 'click', '#kleistad_voegtoe', function() {
                    kleistadMuteer( 'POST' );
                }
            )
            /**
             * Wijzig een reservering.
             */
            .on( 'click', '#kleistad_muteer', function() {
                    kleistadMuteer( 'PUT' );
                }
            )
            /**
             * Verwijder een reservering
             */
            .on( 'click', '#kleistad_verwijder', function() {
                    kleistadMuteer( 'DELETE' );
                }
			)
			/**
             * Sluit het formulier
             */
            .on( 'click', '#kleistad_sluit', function() {
                    $( '#kleistad_reservering' ).dialog( 'close' );
                }
			)
			/**
             * Voeg een medestoker toe
             */
            .on( 'click', '#kleistad_stoker_toevoegen', function() {
				$( '#kleistad_reservering table > tbody > tr:last' ).
					after( '<tr><th><label>Medestoker</label></th><td>' +  selectStoker( true, 0 ) +
						'<td><input name="stoker_perc" class="kleistad_verdeling" type="number" min="0" max="100" value="0" ></td></tr>' );
					$( '[name=stoker_id]:last' ).trigger( 'focus' );
                    return false;
                }
            );

        }
    );

} )( jQuery );
