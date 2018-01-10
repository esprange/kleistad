( function ( $ ) {
    'use strict';

    $( document ).ready(
        function () {

            /**
             * Definieer het datum veld.
             */
            $( ".kleistad_datum" ).each(
                function () {
                    $( this ).datepicker(
                        {
                            dateFormat: "dd-mm-yy"
                        }
                    );
                }
            );

        }
    );

} )( jQuery );
