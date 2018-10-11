( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {
            /**
             * Voeg 15 euro toe.
             */
            $( '#add15' ).click(
                function() {
                    var saldo = $( '#saldo' ).val();
                    saldo = Math.round( ( Number( saldo ) + 15 ) * 100 ) / 100;
                    $( '#saldo' ).val( saldo );
                    return false;
               }
            );

            /**
             * Voeg 30 euro toe.
             */
            $( '#add30' ).click(
                function() {
                    var saldo = $( '#saldo' ).val();
                    saldo = Math.round( ( Number( saldo ) + 30 ) * 100 ) / 100;
                    $( '#saldo' ).val( saldo );
                    return false;
                }
			);

			$( '#kleistad-extra' ).click(
				function() {
					var aantal   = $( '.kleistad-extra' ).length;
					var sjabloon = +
						'<tr>' +
						'<th scope="row">Abonnement extra #</th>' +
						'<td><input type="text" class="kleistad-extra" name="kleistad-opties[extra][#][naam]" /></td>' +
						'<th scope="row">Prijs</th>' +
						'<td><input type="number" step="0.01" min="0" name="kleistad-opties[extra][#][prijs]" /></td>' +
						'</tr>';
					var html     = sjabloon.replace( /#/g, ++aantal );
					$( html ).insertBefore( '#kleistad-extra-toevoegen' );
				}
			);

			$( '#kleistad-soort' ).change(
				function() {
					$( '#kleistad-dag' ).prop( 'required', ( 'beperkt' === $( this ).val() ) );
				}
			);

            /**
             * Definieer de datumpickers.
             */
            $( '.kleistad_datum' ).each(
                function() {
                    $( this ).datepicker(
                        {
							dateFormat: 'dd-mm-yy',
							beforeShowDay: function( date ) {
								var day = date.getDate();
								if ( $( this ).hasClass( 'maand' ) ) {
									return [ ( day === 1 ) ];
								} else {
									return [ true ];
								}
							},
							beforeShow: function( input ) {
								if ( $( input ).attr( 'readonly' ) ) {
									return false;
								}
							}
                        }
                    );
                }
            );
        }
    );
} )( jQuery );
