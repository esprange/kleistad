( function( $ ) {
    'use strict';

    function wijzigTeksten( cursus ) {
		var bedrag        = cursus.prijs;
		var spin          = $( '#kleistad_aantal' );
		var aantal        = spin.spinner( 'value' );
		if ( aantal > cursus.ruimte ) {
			aantal = cursus.ruimte;
		}
		spin.spinner( { max: cursus.ruimte } );
		spin.spinner( 'value', aantal );
		$( '#kleistad_submit_enabler' ).hide();
		$( '#kleistad_submit' ).prop( 'disabled', false );

		bedrag = ( cursus.meer ? aantal : 1 ) * bedrag;
		if ( cursus.inschrijfgeld ) {
			$( 'label[for=kleistad_betaal_ideal]' ).text( 'Ik betaal € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + ' voor de inschrijving en word meteen ingedeeld.' );
			$( 'label[for=kleistad_betaal_stort]' ).text( 'Ik betaal de inschrijving door storting van € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + ' volgens de betaalinstructie, zoals aangegeven in de te ontvangen bevestigingsemail. Indeling vindt daarna plaats.' );
		} else {
			$( 'label[for=kleistad_betaal_ideal]' ).text( 'Ik betaal € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + ' en word meteen ingedeeld.' );
			$( 'label[for=kleistad_betaal_stort]' ).text( 'Ik betaal door storting van € ' + bedrag.toLocaleString( undefined, { minimumFractionDigits: 2 } ) + ' volgens de betaalinstructie, zoals aangegeven in de te ontvangen bevestigingsemail. Indeling vindt daarna plaats.' );
		}
    }

    function wijzigVelden( cursus ) {
        $( '#kleistad_cursus_draaien' ).css( 'visibility', 'hidden' );
        $( '#kleistad_cursus_boetseren' ).css( 'visibility', 'hidden' );
        $( '#kleistad_cursus_handvormen' ).css( 'visibility', 'hidden' );
        $( '#kleistad_cursus_technieken' ).css( 'visibility', 'hidden' );
        $.each(
            cursus.technieken, function( key, value ) {
                $( '#kleistad_cursus_' + value.toLowerCase() ).css( 'visibility', 'visible' );
                $( '#kleistad_cursus_technieken' ).css( 'visibility', 'visible' );
            }
        );
        if ( cursus.meer ) {
            $( '#kleistad_cursus_aantal' ).css( 'visibility', 'visible' );
        } else {
            $( '#kleistad_cursus_aantal' ).css( 'visibility', 'hidden' );
        }
        if ( cursus.lopend ) {
            $( '#kleistad_cursus_betalen' ).hide();
            $( '#kleistad_cursus_lopend' ).show();
            $( '#kleistad_submit' ).html( 'verzenden' );
        } else {
            $( '#kleistad_cursus_betalen' ).show();
            $( '#kleistad_cursus_lopend' ).hide();
            $( '#kleistad_submit' ).html( 'betalen' );
        }
    }

    $( document ).ready(
        function() {
			if ( 0 !== $( 'input[name=cursus_id]:radio:checked' ).length ) {
				wijzigVelden( $( 'input[name=cursus_id]:radio:checked' ).data( 'cursus' ) );
			}
			if ( 0 < $( '#cursus_id' ).val() ) {
				wijzigVelden( $( '#cursus_id' ).data( 'cursus' ) );
			}

            $( '#kleistad_aantal' ).spinner({
                min:1,
                max: ( 0 !== $( 'input[name=cursus_id]:radio:checked' ).length ) ? $( 'input[name=cursus_id]:radio:checked' ).data( 'cursus' ).ruimte : 1,
                /* jshint unused:vars */
                stop: function( event, ui ) {
					if ( 0 !== $( 'input[name=cursus_id]:radio:checked' ).length ) {
						wijzigTeksten( $( 'input[name=cursus_id]:radio:checked' ).data( 'cursus' ) );
					}
					if ( 0 < $( '#cursus_id' ).val() ) {
						wijzigTeksten( $( '#cursus_id' ).data( 'cursus' ) );
					}
                },
                create: function( event, ui ) {
					if ( 0 !== $( 'input[name=cursus_id]:radio:checked' ).length ) {
						wijzigTeksten( $( 'input[name=cursus_id]:radio:checked' ).data( 'cursus' ) );
					}
					if ( 0 < $( '#cursus_id' ).val() ) {
						wijzigTeksten( $( '#cursus_id' ).data( 'cursus' ) );
					}
				}
            });

            $( 'input[name=cursus_id]:radio' ).change(
			function() {
					var cursus = $( 'input[name=cursus_id]:radio:checked' ).data( 'cursus' );
                    wijzigTeksten( cursus );
                    wijzigVelden( cursus );
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
