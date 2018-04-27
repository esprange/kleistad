( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {

            $( 'input[name=bedrag]:radio' ).change(
                function() {
                    var bedrag = $('input[name=bedrag]:radio:checked').val();
                    $( '[name=bedrag_tekst]').html( bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) );
                }
            );
        }
    );

} )( jQuery );
