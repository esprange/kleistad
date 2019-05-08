( function( $ ) {
	'use strict';

    $( document ).ready(
        function() {

            $( 'input[name=betaal]:radio' ).change(
                function() {
                    $( '#kleistad_submit' ).html( ( 'ideal' === $( this ).val() ) ? 'betalen' : 'verzenden' );
                }
            );

			/**
             * Definieer datum veld.
             */
            $( '#kleistad_start_datum' ).datepicker( 'option',
                {
					minDate: 0,
					maxDate: '+3M'
                }
            );

			/**
			 * Vul adresvelden in
			 */
			$( '#kleistad_huisnr, #kleistad_pcode' ).change(
				function() {
					var pcode = $( '#kleistad_pcode' );
					pcode.val( pcode.val().toUpperCase() );
					$().lookupPostcode( pcode.val(), $( '#kleistad_huisnr' ).val(), function( data ) {
						$( '#kleistad_straat' ).val( data.straat );
						$( '#kleistad_plaats' ).val( data.plaats );
					} );
				}
			);
        }
    );

} )( jQuery );
