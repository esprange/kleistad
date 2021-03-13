( function( $ ) {
	'use strict';

	function bepaalBedrag() {
		var bedrag = $( 'input[name=bedrag]:radio:checked' ).val();
		if ( '0' === bedrag ) {
			bedrag = $( 'input[name=ander]' ).val();
		} 
		return bedrag;
	}

    function wijzigTeksten( bedrag ) {
		if ( 'undefined' !== typeof bedrag ) {
			$( 'label[for=kleistad_betaal_ideal]' ).text( 'ik betaal € ' + bedrag.toLocaleString( undefined, { style: 'currency', currency: 'EUR' } ) + ' en verhoog mijn saldo.' );
			$( 'label[for=kleistad_betaal_stort]' ).text( 'ik betaal door storting van € ' + bedrag.toLocaleString( undefined, { style: 'currency', currency: 'EUR' } ) + '. Verhoging saldo vindt daarna plaats.' );
		}
    }

	$( function()
		{
			wijzigTeksten( bepaalBedrag() );

			$( '.kleistad-shortcode' )
			/**
			 * Als er een change is van het te betalen stooksalde.
			 */
			.on( 'change', 'input[name=bedrag]:radio',
                function() {
					var bedrag = bepaalBedrag();
					$( '#kleistad_submit' ).prop( 'disabled', 15 > bedrag || 100 < bedrag );
					wijzigTeksten( bedrag );
				}
            )
			/**
			 * Als er een change is van de betaalwijze.
			 */
            .on( 'change', 'input[name=betaal]:radio',
                function() {
					$( '#kleistad_submit' ).html( ( 'ideal' === $( this ).val() ) ? 'betalen' : 'verzenden' );
                }
			)
			.on( 'input', 'input[name=ander]',
				function() {
					var bedrag = bepaalBedrag();
					$( 'input[value=0]' ).prop('checked',true);
					$( '#kleistad_submit' ).prop( 'disabled', 15 > bedrag || 100 < bedrag );
					wijzigTeksten( bedrag );
				}
			);			
        }
	);

} )( jQuery );
