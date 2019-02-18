/* global jdecode */

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
							var range     = document.createRange(),
								lijst     = $( '#kleistad_email_lijst' ).val(),
								selection, $temp;

							// For IE.
							if ( window.clipboardData ) {
								window.clipboardData.setData( 'Text', lijst );
							} else {
								$temp = $( '<div>' );
								$temp.css( {
									position: 'absolute',
									left:     '-1000px',
									top:      '-1000px'
								} );
								$temp.text( lijst );
								$( 'body' ).append( $temp );
								range.selectNodeContents( $temp.get( 0 ) );
								selection = window.getSelection();
								selection.removeAllRanges();
								selection.addRange( range );
								document.execCommand( 'copy', false, null );
								$temp.remove();
							}
						},
						Download: function() {
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
                'click', '.kleistad_cursus_info', function() {
                    var html   = '<tr><th>Naam</th><th>Telefoon</th><th>Email</th><th>Technieken</th></tr>',
						lijst  = $( this ).data( 'lijst' ),
						id     = $( this ).data( 'id' ),
						naam   = $( this ).data( 'naam' ),
						emails = '';
					$( '#kleistad_cursisten_info' ).dialog( 'option', 'title', jdecode( naam ) ).dialog( 'open' );
					$( '#kleistad_cursus_id' ).val( id );
					$.each( lijst, function( key, value ) {
						html += '<tr><td>' + jdecode( value.naam ) + ( 1 < value.aantal ? ' (' + value.aantal + ')' : '' ) +
								'</td><td>' + value.telnr +
								'</td><td>' + value.email +
								'</td><td>' + value.technieken +
								'</td></tr>';
						emails += value.email + ';';
					} );
					$( '#kleistad_cursisten_lijst' ).empty().append( html );
					$( '#kleistad_email_lijst' ).val( emails );
                }
            );
        }
    );
} )( jQuery );
