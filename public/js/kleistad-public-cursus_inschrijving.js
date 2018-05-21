( function( $ ) {
    'use strict';

    function wijzigTeksten() {
        var cursus = $( 'input[name=cursus_id]:radio:checked' ).data( 'cursus' );
        var bedrag = $( '#kleistad_aantal' ).spinner( 'value' ) * cursus.prijs;
        $( 'label[for=kleistad_betaal_ideal]' ).text( 'Ik betaal € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + ' en word meteen ingedeeld.' );
        $( 'label[for=kleistad_betaal_stort]' ).text( 'Ik betaal later door storting van € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + ' volgens de betaalinstructie, zoals aangegeven in de bevestigingsemail. Indeling vindt daarna plaats.' );
    }

    $( document ).ready(
        function() {

        /**
         * Toon afhankelijk van de cursus de technieken.
         * Toon afhankelijk van de cursus of er meer dan 1 cursist tegelijk mag worden ingeschreven.
         */
            $( 'input[name=cursus_id]:radio' ).change(
                function() {
                    var cursus = $( this ).data( 'cursus' );
                    $( '#kleistad_cursus_draaien' ).css( 'visibility', 'hidden' );
                    $( '#kleistad_cursus_boetseren' ).css( 'visibility', 'hidden' );
                    $( '#kleistad_cursus_handvormen' ).css( 'visibility', 'hidden' );
                    $( '#kleistad_cursus_technieken' ).css( 'visibility', 'hidden' );
                    $.each(
                        cursus.technieken, function( key, value ) {
                                $( '#kleistad_cursus_' + value.toLowerCase() ).css( 'visibility', 'visible' ).find( 'input' ).prop( 'checked', false );
                                $( '#kleistad_cursus_technieken' ).css( 'visibility', 'visible' );
                        }
                    );
                    if ( cursus.meer ) {
                        $( '#kleistad_cursus_aantal' ).css( 'visibility', 'visible' );
                    } else {
                        $( '#kleistad_cursus_aantal' ).css( 'visibility', 'hidden' );
                        $( '#kleistad_aantal' ).spinner( 'value', '1' );
                    }
                    if ( cursus.lopend ) {
                        $( '#kleistad_cursus_betalen' ).hide();
                        $( '#kleistad_cursus_lopend' ).css( 'visibility', 'visible' );
                    } else {
                        $( '#kleistad_cursus_betalen' ).show();
                        $( '#kleistad_cursus_lopend' ).css( 'visibility', 'hidden' );
                    }
                    $( '#kleistad_aantal' ).spinner( {
                        max:cursus.ruimte
                    });
                    wijzigTeksten();
                }
            );

            $( '#kleistad_aantal' ).spinner({
                min:1,
                max:$( 'input[name=cursus_id]:radio:checked' ).data( 'cursus' ).ruimte,
                /* jshint unused:vars */
                stop: function( event, ui ) {
                    wijzigTeksten();
                },
                create: function( event, ui ) {
                    wijzigTeksten();
                }
            });
        }
    );

} )( jQuery );
