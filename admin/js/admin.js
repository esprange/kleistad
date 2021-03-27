( function( $ ) {
	'use strict';

	/**
	 * Converteer lokale datum in format 'd-m-Y' naar Date.
	 *
	 * @param (String) datum
	 */
	function strtodate( value ) {
		var veld = value.split( '-' );
		return new Date( veld[2], veld[1] - 1, veld[0] );
	}
	
    $( function() {
            /**
             * Voeg 15 euro toe.
             */
            $( '#add15' ).on( 'click', 
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
            $( '#add30' ).on( 'click',
                function() {
                    var saldo = $( '#saldo' ).val();
                    saldo = Math.round( ( Number( saldo ) + 30 ) * 100 ) / 100;
                    $( '#saldo' ).val( saldo );
                    return false;
                }
			);

			$( '#kleistad-extra' ).on( 'click',
				function() {
					var aantal   = $( '.kleistad-extra' ).length;
					var sjabloon = +
						'<tr>' +
						'<th scope="row">Abonnement extra #</th>' +
						'<td><input type="text" class="kleistad-extra regular-text" name="kleistad-opties[extra][#][naam]" /></td>' +
						'<th scope="row">Prijs</th>' +
						'<td><input type="number" step="0.01" min="0" name="kleistad-opties[extra][#][prijs]" class="small-text" /></td>' +
						'</tr>';
					var html     = sjabloon.replace( /#/g, ++aantal );
					$( html ).insertBefore( '#kleistad-extra-toevoegen' );
				}
			);

			$( '#kleistad-soort' ).on( 'change',
				function() {
					$( '#kleistad-dag' ).prop( 'required', ( 'beperkt' === $( this ).val() ) );
				}
			);

            /**
             * Definieer de datumpickers.
             */
            $( '.kleistad-datum' ).datepicker(
				{
					dateFormat: 'dd-mm-yy',
					beforeShowDay: function( date ) {
						var day = date.getDate();
						if ( $( this ).hasClass( 'maand' ) ) {
							return [ ( 1 === day ) ];
						}
						return [ true ];
					},
					beforeShow: function( input ) {
						if ( $( input ).attr( 'readonly' ) ) {
							return false;
						}
						return true;
					}
				}
			);

			$( '#kleistad_start_config' ).datepicker( 'option',
				{
					minDate: ( $( this ).prop( 'disabled' ) ) ? null : 0,
					maxDate: $( '#kleistad_eind_config' ).datepicker( 'getDate' ),
					onSelect: function( datum ) {
						$( '#kleistad_eind_config' ).datepicker( 'option', { 
							minDate: strtodate( datum )
						} );
					},
					beforeShowDay: function( datum ) {
						return [ 1 === datum.getDay(), '', '' ]; // Maandagen zijn selecteerbaar.
					}
				}
			);
			$( '#kleistad_eind_config' ).datepicker( 'option',
				{
					minDate: $( '#kleistad_start_config' ).datepicker( 'getDate' ),
					onSelect: function( datum ) {
						$( '#kleistad_start_config' ).datepicker( 'option', { maxDate: strtodate( datum ) } );
					},
					beforeShowDay: function( datum ) {
						return [ 0 === datum.getDay(), '', '' ]; // Zondagen zijn selecteerbaar.
					}
				}
			);
        }
    );
} )( jQuery );
