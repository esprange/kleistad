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

            /**
             * Afhankelijk van keuze abonnement al dan niet tonen dag waarvoor beperkt abo geldig is.
             */
            $( 'input[name=abonnement_keuze]' ).change(
                function() {
                    var bedrag = $( this ).data( 'bedrag' );
                    $( '[name=bedrag_tekst]').html( bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) );
                    if (  'beperkt' === this.value ) {
                        $( '#kleistad_dag' ).css( 'visibility', 'visible' );
                        
                    } else {
                        $( '#kleistad_dag' ).css( 'visibility', 'hidden' );
                    }
                }
            );
        }
    );

} )( jQuery );
