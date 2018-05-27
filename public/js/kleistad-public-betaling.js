( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {
            /**
             * Definieer datum veld.
             */
            $( '#kleistad_start_datum' ).datepicker(
                {
                    dateFormat: 'dd-mm-yy'
                }
            );
        
            $( 'input[name=betaal]:radio' ).change(
                function() {
                    $( '#kleistad_submit').html( ( 'ideal' === $(this).val() ) ? 'betalen' : 'opslaan' );
                }
            );

        }
    );

} )( jQuery );
