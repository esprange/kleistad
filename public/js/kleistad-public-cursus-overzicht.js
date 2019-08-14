/* global detectTap */

( function( $ ) {
	'use strict';

    $( document ).ready(
        function() {

            /**
             * Definieer de popup dialoog
             */
            $( '#kleistad_cursisten_info' ).dialog(
                {
                    autoOpen: false,
                    height: 'auto',
                    width: 1000,
                    modal: true,
                    buttons: {
                        Ok: function() {
                            $( this ).dialog( 'close' );
						},
						'Kopie naar klembord': function() {
							$( '#kleistad_email_lijst' ).kleistad_klembord();
						},
						'Download': function() {
                            $( '#kleistad_submit_cursus_overzicht' ).val( 'download_cursisten' );
							$( this ).find( 'form' ).submit();
						},
						'Restant email versturen': function() {
                            $( '#kleistad_submit_cursus_overzicht' ).val( 'restant_email' );
							$( '#kleistad_cursisten_info_form' ).submit();
						}
                    }
                }
            );

            /**
             * Toon de detailinformatie van de deelnemer
             */
            $( '#kleistad_cursussen tbody' ).on(
                'click touchend', 'tr', function( event ) {
					var html, lijst, id, naam, emails;
					if ( 'click' === event.type || detectTap ) {
						html   = '<tr><td>Naam</td><td>Telefoon</td><td>Email</td><td>Technieken</td><td>Betaald</td><td>Restant Email</td></tr>';
						lijst  = $( this ).data( 'lijst' );
						id     = $( this ).data( 'id' );
						naam   = $( this ).data( 'naam' );
						emails = '';
						$( '#kleistad_cursisten_info' ).dialog( 'option', 'title', naam ).dialog( 'open' );
						$( '#kleistad_cursus_id' ).val( id );
						$.each( lijst, function( key, value ) {
							html += '<tr><td>' + value.naam + ( 1 < value.aantal ? ' (' + value.aantal + ')' : '' ) +
									'</td><td>' + value.telnr +
									'</td><td>' + value.email +
									'</td><td>' + value.technieken +
									'</td><td>' + ( ( value.c_betaald ) ? '<span class="dashicons dashicons-yes"></span>' : '' ) +
									'</td><td>' + ( ( value.restant_email ) ? '<span class="dashicons dashicons-yes"></span>' : '' ) +
									'</td></tr>';
							emails += value.email + ';';
						} );
						$( '#kleistad_cursisten_lijst' ).empty().append( html );
						$( '#kleistad_email_lijst' ).val( emails );
					}
                }
            );
        }
    );
} )( jQuery );
