( function( $ ) {
	'use strict';

    function wijzigTeksten() {
        var bedrag = $( 'input[name=bedrag]:radio:checked' ).val();
        $( 'label[for=kleistad_betaal_ideal]' ).text( 'ik betaal € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + ' en verhoog mijn saldo.' );
        $( 'label[for=kleistad_betaal_stort]' ).text( 'ik betaal door storting van € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + '. Verhoging saldo vindt daarna plaats.' );
    }

    $( document ).ready(
        function() {
            wijzigTeksten();

            $( 'input[name=bedrag]:radio' ).change(
                function() {
                    wijzigTeksten();
                }
            );

            $( 'input[name=betaal]:radio' ).change(
                function() {
                    $( '#kleistad_submit' ).html( ( 'ideal' === $( this ).val() ) ? 'betalen' : 'verzenden' );
                }
            );

        }
);

} )( jQuery );
