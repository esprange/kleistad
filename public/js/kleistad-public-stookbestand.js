( function ( $ ) {
    'use strict';

    $( document ).ready( function () {

        /**
         * Definieer de datum velden.
         */
        $( ".kleistad_datum" ).each( function () {
            $( this ).datepicker( {
                dateFormat: "dd-mm-yy"
            } );
        } );

    } );

} )( jQuery );
