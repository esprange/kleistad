( function( $ ) {
	'use strict';

    $( document ).ready(
        function() {
            $( 'input[name=betaal]:radio' ).change(
                function() {
                    $( '#kleistad_submit' ).html( ( 'ideal' === $( this ).val() ) ? 'betalen' : 'verzenden' );
                }
            );

        }
    );

} )( jQuery );
