( function( $ ) {
	'use strict';

    $( document ).ready(
        function() {
            $( 'input[name=betaal]:radio' ).on( 'change',
                function() {
                    $( '#kleistad_submit' ).html( ( 'ideal' === $( this ).val() ) ? 'betalen' : 'verzenden' );
                }
            );

        }
    );

} )( jQuery );
