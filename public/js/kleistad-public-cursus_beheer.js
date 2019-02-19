( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {

			// $( '#kleistad_cursus_start_tijd' ).change(
			// 	function() {
			// 		var start_tijd = strtotime( $( this ).val() );
			// 		var eind_tijd  = strtotime( $( '#kleistad_cursus_eind_tijd' ).val() );
			// 		if ( start_tijd + 60 > eind_tijd ) {
			// 			$( '#kleistad_cursus_eind_tijd' ).val( timetostr( Math.min( start_tijd + 60, 24 * 60 ) ) );
			// 		}
			// 	}
			// );

			// $( '#kleistad_cursus_eind_tijd' ).change(
			//  	function() {
			// 		var start_tijd = strtotime( $( this ).val() );
			// 		var eind_tijd  = strtotime( $( '#kleistad_cursus_eind_tijd' ).val() );
			// 		if ( start_tijd > eind_tijd - 60 ) {
			// 			$( '#kleistad_cursus_start_tijd' ).val( timetostr( Math.max( eind_tijd - 60, 0 ) ) );
			// 		}
			// 	}
			// );

			$( '#kleistad_cursus_start_datum' ).change(
				function() {
					$( '#kleistad_cursus_eind_datum' ).datepicker( 'option', 'minDate', $( this ).val() );

				}
			);

			$( '#kleistad_cursus_eind_datum' ).change(
				function() {
					$( '#kleistad_cursus_start_datum' ).datepicker( 'option', 'maxDate', $( this ).val() );
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
                    open: function() {
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
                    $( '#kleistad_docent' ).val( cursus.docent );
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
					$( '#kleistad_cursus_beheer_form' )[0].reset();
                    $( '#kleistad_indeling' ).children().remove().end();
                }
            );
        }
    );

} )( jQuery );
