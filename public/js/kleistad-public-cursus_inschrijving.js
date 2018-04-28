( function( $ ) {
    'use strict';

    function wijzigTeksten() {
        var prijs  = $( 'input[name=cursus_id]:radio:checked' ).data( 'prijs' );
        var aantal = $( '#kleistad_aantal' ).spinner( 'value' );
        var bedrag = aantal * prijs;
        $( '[name=bedrag_tekst]' ).html( bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) );
    }

    $( document ).ready(
        function() {

        /**
         * Toon afhankelijk van de cursus de technieken.
         * Toon afhankelijk van de cursus of er meer dan 1 cursist tegelijk mag worden ingeschreven.
         */
            $( 'input[name=cursus_id]:radio' ).change(
                function() {
                    var technieken = $( this ).data( 'technieken' );
                    var meer = $( this ).data( 'meer' );
                    var ruimte = $( this ).data( 'ruimte' );
                    $( '#kleistad_cursus_draaien' ).css( 'visibility', 'hidden' );
                    $( '#kleistad_cursus_boetseren' ).css( 'visibility', 'hidden' );
                    $( '#kleistad_cursus_handvormen' ).css( 'visibility', 'hidden' );
                    $( '#kleistad_cursus_technieken' ).css( 'visibility', 'hidden' );
                    $.each(
                        technieken, function( key, value ) {
                                $( '#kleistad_cursus_' + value.toLowerCase() ).css( 'visibility', 'visible' ).find( 'input' ).prop( 'checked', false );
                                $( '#kleistad_cursus_technieken' ).css( 'visibility', 'visible' );
                        }
                    );
                    if ( meer ) {
                        $( '#kleistad_cursus_aantal' ).css( 'visibility', 'visible' );
                    } else {
                        $( '#kleistad_cursus_aantal' ).css( 'visibility', 'hidden' );
                        $( '#kleistad_aantal' ).spinner( 'value', '1' );
                    }
                    $( '#kleistad_aantal' ).spinner( {
                        max:ruimte
                    });
                    wijzigTeksten();
                }
            );

            $( '#kleistad_aantal' ).spinner({
                min:1,
                max:$( 'input[name=cursus_id]:radio:checked' ).data( 'ruimte' ),
                /* jshint unused:vars */
                stop: function( event, ui ) {
                    wijzigTeksten();
                }
            });
        }
    );

} )( jQuery );
