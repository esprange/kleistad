( function( $ ) {
	'use strict';

	function wijzigTeksten( cursus ) {
		var $spin   = $( '#kleistad_aantal' ),
			aantal = $spin.spinner( 'value' ),
			bedrag;
		if ( aantal > cursus.ruimte ) {
			aantal = cursus.ruimte;
		}
		$spin.spinner( { max: cursus.ruimte } );
		$spin.spinner( 'value', aantal );
		$( '#kleistad_submit_enabler' ).hide();
		$( '#kleistad_submit' ).prop( 'disabled', false );

		bedrag = ( cursus.meer ? aantal : 1 ) * cursus.bedrag;
		$( 'label[for=kleistad_betaal_ideal]' ).text( 'Ik betaal ' + bedrag.toLocaleString( undefined, { style: 'currency', currency: 'EUR' } ) + ' en word meteen ingedeeld.' );
		$( 'label[for=kleistad_betaal_stort]' ).text( 'Ik betaal door storting van ' + bedrag.toLocaleString( undefined, { style: 'currency', currency: 'EUR' } ) + ' volgens de betaalinstructie in de te ontvangen email. Indeling vindt daarna plaats.' );
	}

	function wijzigVelden( cursus ) {
		$( '#kleistad_cursus_draaien' ).hide();
		$( '#kleistad_cursus_boetseren' ).hide();
		$( '#kleistad_cursus_handvormen' ).hide();
		$( '#kleistad_cursus_technieken' ).css( 'visibility', 'hidden' );
		$.each(
			cursus.technieken,
			function( key, value ) {
				$( '#kleistad_cursus_' + value.toLowerCase() ).show();
				$( '#kleistad_cursus_technieken' ).css( 'visibility', 'visible' );
			}
		);
		if ( cursus.meer && ! cursus.vol && ( 1 < cursus.ruimte ) ) {
			$( '#kleistad_cursus_aantal' ).css( 'visibility', 'visible' );
		} else {
			$( '#kleistad_cursus_aantal' ).css( 'visibility', 'hidden' );
		}
		if ( cursus.lopend ) {
			$( '#kleistad_cursus_betalen' ).hide();
			$( '#kleistad_cursus_lopend' ).show();
			$( '#kleistad_cursus_vol' ).hide();
			$( '#kleistad_submit' ).html( 'verzenden' );
		} else if ( ! cursus.vol ) {
			$( '#kleistad_cursus_betalen' ).show();
			$( '#kleistad_cursus_lopend' ).hide();
			$( '#kleistad_cursus_vol' ).hide();
			$( '#kleistad_submit' ).html( 'betalen' );
		} else {
			$( '#kleistad_cursus_betalen' ).hide();
			$( '#kleistad_cursus_lopend' ).hide();
			$( '#kleistad_cursus_vol' ).show();
			$( '#kleistad_submit' ).html( 'verzenden' );
		}
	}

	$(
		function() {
			if ( 0 !== $( 'input[name=cursus_id]:radio:checked' ).length ) {
				wijzigVelden( $( 'input[name=cursus_id]:radio:checked' ).data( 'cursus' ) );
			}

			$( '#kleistad_cursussen' ).tooltip(
				{
					track: true,
					content: function( callback ) {
						callback( $( this ).prop( 'title' ).replaceAll( '|', '<br />' ) );
					},
					classes: {
						'ui-tooltip': 'ui-corner-all ui-widget-shadow kleistad'
					}
				}
			);

			$( '#kleistad_aantal' ).spinner(
				{
					min:1,
					max: ( 0 !== $( 'input[name=cursus_id]:radio:checked' ).length ) ? $( 'input[name=cursus_id]:radio:checked' ).data( 'cursus' ).ruimte : 1,
					stop: function() {
						if ( 0 !== $( 'input[name=cursus_id]:radio:checked' ).length ) {
							wijzigTeksten( $( 'input[name=cursus_id]:radio:checked' ).data( 'cursus' ) );
						}
					},
					create: function() {
						if ( 0 !== $( 'input[name=cursus_id]:radio:checked' ).length ) {
							wijzigTeksten( $( 'input[name=cursus_id]:radio:checked' ).data( 'cursus' ) );
						}
					}
				}
			);

			$( 'input[name=cursus_id]:radio' ).on(
				'change',
				function() {
					var cursus = $( 'input[name=cursus_id]:radio:checked' ).data( 'cursus' );
					wijzigTeksten( cursus );
					wijzigVelden( cursus );
				}
			);

			$( 'input[name=betaal]:radio' ).on(
				'change',
				function() {
					$( '#kleistad_submit' ).html( ( 'ideal' === $( this ).val() ) ? 'betalen' : 'verzenden' );
				}
			);

			/**
			 * Vul adresvelden in
			 */
			$( '#kleistad_huisnr, #kleistad_pcode' ).on(
				'change',
				function() {
					var pcode = $( '#kleistad_pcode' );
					pcode.val( pcode.val().toUpperCase() );
					$().lookupPostcode(
						pcode.val(),
						$( '#kleistad_huisnr' ).val(),
						function( data ) {
							$( '#kleistad_straat' ).val( data.straat );
							$( '#kleistad_plaats' ).val( data.plaats );
						}
					);
				}
			);
		}
	);

} )( jQuery );
