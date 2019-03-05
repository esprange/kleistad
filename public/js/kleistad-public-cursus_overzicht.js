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
                            $( '#kleistad_download_cursisten' ).submit();
						}
                    }
                }
            );

            /**
             * Verander de opmaak bij hovering.
             */
            $( 'body' ).on(
                'hover', '.kleistad_cursus_info', function() {
                    $( this ).css( 'cursor', 'pointer' );
                    $( this ).toggleClass( 'kleistad_hover' );
                }
            );

            /**
             * Toon de detailinformatie van de deelnemer
             */
            $( 'body' ).on(
                'click touchend', '.kleistad_cursus_info', function( event ) {
					var html, lijst, id, naam, emails;
					if ( 'click' === event.type || detectTap ) {
						html   = '<tr><th>Naam</th><th>Telefoon</th><th>Email</th><th>Technieken</th></tr>';
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
