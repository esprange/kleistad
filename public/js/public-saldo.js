( function( $ ) {
	'use strict';

    function wijzigTeksten() {
		var bedrag = $( 'input[name=bedrag]:radio:checked' ).val();
		if ( 'undefined' !== typeof bedrag ) {
			$( 'label[for=kleistad_betaal_ideal]' ).text( 'ik betaal ' + bedrag.toLocaleString( undefined, { style: 'currency', currency: 'EUR' } ) + ' en verhoog mijn saldo.' );
			$( 'label[for=kleistad_betaal_stort]' ).text( 'ik betaal door storting van ' + bedrag.toLocaleString( undefined, { style: 'currency', currency: 'EUR' } ) + '. Verhoging saldo vindt daarna plaats.' );
		}
    }

	$( document ).ready(
        function() {
			wijzigTeksten();

			$( '.kleistad_shortcode' )
			/**
			 * Als er een change is van het te betalen stooksalde.
			 */
			.on( 'change', 'input[name=bedrag]:radio',
                function() {
                    wijzigTeksten();
                }
            )
			/**
			 * Als er een change is van de betaalwijze.
			 */
            .on( 'change', 'input[name=betaal]:radio',
                function() {
                    $( '#kleistad_submit' ).html( ( 'ideal' === $( this ).val() ) ? 'betalen' : 'verzenden' );
                }
            );

        }
	);

} )( jQuery );
