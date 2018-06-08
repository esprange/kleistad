( function( $ ) {
    'use strict';

    function wijzigTeksten() {
        var bedrag = $( '[name=abonnement_keuze]:radio:checked' ).data( 'bedrag' );
        $( 'label[for=kleistad_betaal_ideal]' ).text( 'Ik betaal € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + ' (= 3 termijnen en borg). Ik machtig Kleistad daarna tot maandelijkse incasso van het abonnement.' );
        $( 'label[for=kleistad_betaal_stort]' ).text( 'Ik betaal door storting van € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + ' (= 3 termijnen en borg) volgens de betaalinstructie, zoals aangegeven in de te ontvangen bevestigingsemail.' );
    }

    $( document ).ready(
        function() {
            wijzigTeksten();

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
					wijzigTeksten();
                    if (  'beperkt' === this.value ) {
                        $( '#kleistad_dag' ).css( 'visibility', 'visible' );

                    } else {
                        $( '#kleistad_dag' ).css( 'visibility', 'hidden' );
                    }
                }
            );

            $( 'input[name=betaal]:radio' ).change(
                function() {
                    $( '#kleistad_submit' ).html( ( 'ideal' === $( this ).val() ) ? 'betalen' : 'opslaan' );
                }
            );
        }
    );

} )( jQuery );
