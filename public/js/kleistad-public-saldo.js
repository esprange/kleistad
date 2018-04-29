( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {

            $( 'input[name=bedrag]:radio' ).change(
                function() {
                    var bedrag = $( 'input[name=bedrag]:radio:checked' ).val();
                    $( 'label[for=kleistad_betaal_ideal]').text( 'ik betaal € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + ' en verhoog mijn saldo.' );
                    $( 'label[for=kleistad_betaal_stort]').text( 'ik betaal door storting van € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + '. Verhoging saldo vindt daarna plaats.');

                }
            );
        }
    );

} )( jQuery );
