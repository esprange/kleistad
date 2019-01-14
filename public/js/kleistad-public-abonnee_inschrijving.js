( function( $ ) {
    'use strict';

    function wijzigTeksten() {
		var bedrag      = $( '[name=abonnement_keuze]:radio:checked' ).data( 'bedrag' );
		var bedragtekst = $( '[name=abonnement_keuze]:radio:checked' ).data( 'bedragtekst' );

		$( 'input[name^=extras]:checkbox:checked' ).each(
			function() {
				bedrag += $( this ).data( 'bedrag' );
			}
		);
        $( 'label[for=kleistad_betaal_ideal]' ).text( 'Ik betaal € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + ' ' + bedragtekst );
        $( 'label[for=kleistad_betaal_stort]' ).text( 'Ik betaal door storting van € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + ' ' + bedragtekst + ' volgens de betaalinstructie, zoals aangegeven in de te ontvangen bevestigingsemail.' );
    }

    $( document ).ready(
        function() {
            wijzigTeksten();

            /**
             * Definieer datum veld.
             */
            $( '#kleistad_start_datum' ).datepicker(
                {
					dateFormat: 'dd-mm-yy',
					minDate: 0,
					maxDate: '+3M'
                }
            );

            /**
             * Afhankelijk van keuze abonnement al dan niet tonen dag waarvoor beperkt abo geldig is.
             */
            $( 'input[name=abonnement_keuze]:radio' ).change(
                function() {
					wijzigTeksten();
                    if (  'beperkt' === this.value ) {
                        $( '#kleistad_dag' ).css( 'visibility', 'visible' );

                    } else {
                        $( '#kleistad_dag' ).css( 'visibility', 'hidden' );
                    }
                }
			);

			$( 'input[name^=extras]:checkbox' ).change(
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
