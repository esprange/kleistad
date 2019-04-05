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
	 * Maak een selectie lijst van de mogelijke stokers.
	 *
	 * @param {bool} empty Als waar, dan bevat de selectie lijst ook een lege optie.
	 * @param {int} id Het wordpress id van de thans geselecteerde stoker.
	 */
	$.fn.selectStoker = function( empty, id ) {
		var i, stokers  = $( '#kleistad_reserveringen' ).data( 'stokers' );
		$( this ).append(
			$( '<select>' ).attr( { 'name':'kleistad_stoker_id', 'class':'kleistad_verdeling' } )
		);
		if ( empty ) {
			$( 'select', this ).append(
				$( '<option>' ).val( 0 )
			);
		}
		for ( i = 0; i < stokers.length; i++ ) {
			$( 'select', this ).append(
				$( '<option>' ).val( stokers[i].id ).prop( 'selected',  stokers[i].id === id ).text( stokers[i].naam )
			);
		}
		return this;
	};

	/**
	 * Voeg een regel aan de tabel toe voor de hoofdstoker.
	 *
	 * @param {array} verdeling De verdeling: het wordpress id van de hoofdstoker en het percentage dat deze stookt.
	 */
	function stook( verdeling ) {
		if ( $( '#kleistad_reserveringen' ).data( 'override' ) ) {
			$( '#kleistad_reservering table > tbody' ).append(
				$( '<tr>' ).append(
					$( '<td>' ).append(
						$( '<label>' ).text( 'Stoker' )
					)
				).append(
					$( '<td>' ).selectStoker( false, verdeling.id )
				).append(
					$( '<td>' ).append(
						$( '<input>' ).attr( { name:'kleistad_stoker_perc', size:'5', tabindex:'-1' } ).prop( 'readonly', true ).val( verdeling.perc ).css( { border:0, outline:0 } )
					)
				)
			);

		} else {
			$( '#kleistad_reservering table > tbody' ).append(
				$( '<tr>' ).append(
					$( '<td>' ).append(
						$( '<label>' ).text( 'Stoker' )
					)
				).append(
					$( '<td>' ).append(
						$( '<input> ' ).attr( { name:'kleistad_stoker_id', type:'hidden' } ).val( verdeling.id )
					).append( vindStokerNaam( verdeling.id ) ).css( { 'white-space':'nowrap', 'text-overflow':'ellipsis', overflow:'hidden' } )
				).append(
					$( '<td>' ).append(
						$( '<input>' ).attr( { name:'kleistad_stoker_perc', size:'5', tabindex:'-1' } ).prop( 'readonly', true ).val( verdeling.perc ).css( { border:0, outline:0 } )
					)
				)
			);
		}
	}

	/**
	 * Voeg een regel aan de tabel toe voor een medestoker.
	 *
	 * @param {array} verdeling De verdeling: het wordpress id van de meestoker en het percentage dat deze stookt.
	 */
	function medestook( verdeling ) {
		$( '#kleistad_reservering table > tbody > tr:last' ).after(
			$( '<tr>' ).append(
				$( '<td>' ).append(
					$( '<label>' ).text( 'Medestoker' )
				)
			).append(
				$( '<td>' ).selectStoker( true, verdeling.id )
			).append(
				$( '<td>' ).append(
					$( '<input>' ).attr( { type:'number', 'class':'kleistad_verdeling', name:'kleistad_stoker_perc', min:'0', max:'100', size:'3' } ).val( verdeling.perc )
				)
			)
		);
	}

	/**
	 * Voeg de regels voor de stookparameters toe.
	 *
	 * @param {array} data De door de form meegegeven data, waaronder de stookparameters.
	 */
	function parameters( data ) {
		$( '#kleistad_reservering table > thead' ).append(
			$( '<tr>' ).append(
				$( '<td>' ).append(
					$( '<label>' ).text( 'Soort stook' )
				)
			).append(
				$( '<td>' ).attr( 'colspan', '2' ).append(
					$( '<select>' ).attr( 'id', 'kleistad_soortstook' ).append(
						$( '<option>' ).val( 'Biscuit' ).text( 'Biscuit' ).prop( 'selected', 'Biscuit' === data.soortstook )
					).append(
						$( '<option>' ).val( 'Glazuur' ).text( 'Glazuur' ).prop( 'selected', 'Glazuur' === data.soortstook )
					).append(
						$( '<option>' ).val( 'Overig' ).text( 'Overig' ).prop( 'selected', 'Overig' === data.soortstook )
					)
				)
			)
		).append(
			$( '<tr>' ).append(
				$( '<td>' ).attr( 'colspan', '2' ).append(
					$( '<label>' ).html( 'Temperatuur &nbsp; &deg;C' )
				)
			).
			append(
				$( '<td>' ).append(
					$( '<input>' ).attr( { id: 'kleistad_temperatuur', type: 'number', min:'0', max:'1400' } ).val( data.temperatuur )
				)
			)
		).append(
			$( '<tr>' ).append(
				$( '<td>' ).attr( 'colspan', '2' ).append(
					$( '<label>' ).text( 'Programma' )
				)
			).append(
				$( '<td>' ).append(
					$( '<input>' ).attr( { id: 'kleistad_programma', type: 'number', min:'0', max:'99' } ).val( data.programma )
				)
			)
		);
		if ( $( '#kleistad_reserveringen' ).data( 'override' ) ) {
			$( '#kleistad_soortstook' ).append(
				$( '<option>' ).val( 'Onderhoud' ).text( 'Onderhoud' ).prop( 'selected', 'Onderhoud' === data.soortstook )
			);
		}
	}

	/**
	 * Toon de stookgegevens, ze zijn niet te wijzigen
	 *
	 * @param {array} formData
	 */
	function alleenLezen( formData ) {
		$( '#kleistad_reservering table > thead' ).
		append( $( '<tr>' ).
			append( $( '<td>' ).text( 'Soort stook' ) ).
			append( $( '<td>' ).text( formData.soortstook ) ) ).
		append( $( '<tr>' ).
			append( $( '<td>' ).text( 'Temperatuur' ) ).
			append( $( '<td>' ).text( formData.temperatuur ) ) ).
		append( $( '<tr>' ).
			append( $( '<td>' ).text( 'Programma' ) ).
			append( $( '<td>' ).text( formData.programma ) ) );
		formData.verdeling.forEach(
			function( item, index ) {
				$( '#kleistad_reservering table > tbody' ).
					append( $( '<tr>' ).
						append( $( '<td>' ).
							append( '<label>' ).text( 0 === index ? 'Stoker' : 'Medestoker' ) ).
						append( $( '<td>' ).text( vindStokerNaam( item.id ) ) ).css( { 'white-space':'nowrap', 'text-overflow':'ellipsis', overflow:'hidden' } ).
						append( $( '<td>' ).css( { 'text-align':'right' } ).text( item.perc ) )
					);
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
		var row;
		$( '.kleistad_button' ).hide();
		$( '#kleistad_reservering table > tbody' ).empty();
		$( '#kleistad_reservering table > thead' ).empty();

		$( '#kleistad_reservering' ).dialog( 'option', 'title', $( '#kleistad_reserveringen' ).data( 'oven-naam' ) + ' op ' + formData.dag + '-' + formData.maand + '-' + formData.jaar );

		$( '#kleistad_dag' ).val( formData.dag );
        $( '#kleistad_maand' ).val( formData.maand );
        $( '#kleistad_jaar' ).val( formData.jaar );
        $( '#kleistad_stoker_id' ).val( formData.gebruiker_id );

		switch ( formData.status ) {
			case 'ongebruikt':
				break;
			case 'reserveerbaar':
				parameters( formData );
				stook( formData.verdeling[0] );
				medestook( { id:0, perc:0 } );
				$( '#kleistad_tekst' ).text( 'Wil je de reservering toevoegen ?' );
				$( '#kleistad_voegtoe,#kleistad_stoker_toevoegen' ).show();
				$( '#kleistad_soortstook' ).focus();
				break;
			case 'verwijderbaar':
				parameters( formData );
				stook( formData.verdeling[0] );
				for ( row = 1; row < formData.verdeling.length; row++ ) {
					medestook( formData.verdeling[row] );
				}
				medestook( { id:0, perc:0 } );
				$( '#kleistad_tekst' ).text( 'Wil je de reservering wijzigen of verwijderen ?' );
				$( '#kleistad_muteer,#kleistad_verwijder,#kleistad_stoker_toevoegen' ).show();
				$( '#kleistad_soortstook' ).focus();
				break;
			case 'alleenlezen':
				alleenLezen( formData );
				$( '#kleistad_tekst' ).text( 'Deze reservering is niet door u te wijzigen' );
				$( '#kleistad_sluit' ).focus();
				break;
			case 'wijzigbaar':
				parameters( formData );
				stook( formData.verdeling[0] );
				for ( row = 1; row < formData.verdeling.length; row++ ) {
					medestook( formData.verdeling[row] );
				}
				medestook( { id:0, perc:0 } );
				$( '#kleistad_tekst' ).text( 'Wil je de reservering wijzigen ?' );
				$( '#kleistad_muteer,#kleistad_stoker_toevoegen' ).show();
				$( '#kleistad_soortstook' ).focus();
				break;
			case 'definitief':
				alleenLezen( formData );
				$( '#kleistad_tekst' ).text( 'Deze reservering is definitief' );
				$( '#kleistad_sluit' ).focus();
				break;
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
			kleistadShow(
				$( '#kleistad_reserveringen' ).data( 'maand' ),
				$( '#kleistad_reserveringen' ).data( 'jaar' )
			);

			/**
             * Wijzig de periode als de gebruiker op eerder of later klikt.
             */
            $( '#kleistad_reserveringen' ).on(
                'click', '.kleistad_periode', function() {
					kleistadShow(
						parseInt( $( '#kleistad_reserveringen' ).data( 'maand' ), 10 ) + parseInt( $( this ).val(), 10 ),
						$( '#kleistad_reserveringen' ).data( 'jaar' )
					);
                }
            );

            /**
             * Verander de opmaak bij hovering.
             */
            $( '#kleistad_reserveringen' ).on( 'hover', '.kleistad_box', function() {
					$( this ).toggleClass( 'kleistad_hover_reservering' );
				}
            );

			/**
             * Open een reservering (nieuw of bestaand).
             */
            $( '#kleistad_reserveringen' ).on(
                'click touchend', 'tbody tr', function( event ) {
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
            $( '#kleistad_reservering' ).dialog(
				{
					autoOpen: false,
					height: 'auto',
					width: 360,
					modal: true
				}
			);

			/**
             * Verdeel de percentages als de gebruiker een percentage wijzigt.
             */
            $( '#kleistad_reservering' ).on(
				'change', '.kleistad_verdeling', function() {
                    kleistadVerdeel( this );
                }
            );

            /**
             * Voeg een reservering toe.
             */
            $( '#kleistad_reservering' ).on(
                'click', '#kleistad_voegtoe', function() {
                    kleistadMuteer( 'POST' );
                }
            );

            /**
             * Wijzig een reservering.
             */
            $( '#kleistad_reservering' ).on(
                'click', '#kleistad_muteer', function() {
                    kleistadMuteer( 'PUT' );
                }
            );

            /**
             * Verwijder een reservering
             */
            $( '#kleistad_reservering' ).on(
                'click', '#kleistad_verwijder', function() {
                    kleistadMuteer( 'DELETE' );
                }
            );

            /**
             * Sluit het formulier
             */
            $( '#kleistad_reservering' ).on(
                'click', '#kleistad_sluit', function() {
                    $( '#kleistad_reservering' ).dialog( 'close' );
                }
            );

            /**
             * Voeg een medestoker toe
             */
            $( '#kleistad_reservering' ).on(
                'click', '#kleistad_stoker_toevoegen', function() {
					medestook( { id:0, perc:0 } );
					$( '[name=kleistad_stoker_id]:last' ).focus();
                    return false;
                }
            );

        }
    );

} )( jQuery );
