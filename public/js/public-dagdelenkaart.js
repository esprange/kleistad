( function( $ ) {
	'use strict';

    $( document ).ready(
        function() {

			/**
             * Definieer datum veld.
             */
            $( '#kleistad_start_datum' ).datepicker(
                {
					minDate: 0,
					maxDate: '+3M'
                }
            );

            $( 'input[name=betaal]:radio' ).on( 'change',
                function() {
                    $( '#kleistad_submit' ).html( ( 'ideal' === $( this ).val() ) ? 'betalen' : 'verzenden' );
                }
            );

			/**
			 * Vul adresvelden in
			 */
			$( '#kleistad_huisnr, #kleistad_pcode' ).on( 'change',
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
