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

        }
    );

} )( jQuery );
