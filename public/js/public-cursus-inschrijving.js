/**
 * Cursus inschrijving Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

( function( $ ) {
	'use strict';

	/**
	 * Wijzig de teksten van het betaal formulier.
	 *
	 * @param {object}  cursus
	 * @param {integer} cursus.ruimte
	 * @param {boolean} cursus.meer
	 * @param {float}   cursus.bedrag
	 * @param {array}   cursus.technieken
	 * @param {boolean} cursus.vol
	 * @param {boolean} cursus.lopend
	 * @param {string}  cursus.naam
	 */
	function wijzigTeksten( cursus ) {
		let $spin  = $( '#kleistad_aantal' ),
			aantal = $spin.spinner( 'value' ),
			bedrag;
		if ( aantal > cursus.ruimte ) {
			aantal = cursus.ruimte;
		}
		$spin.spinner( { max: cursus.ruimte } );
		$spin.spinner( 'value', aantal );

		bedrag = ( cursus.meer ? aantal : 1 ) * cursus.bedrag;
		$( 'label[for=kleistad_betaal_ideal]' ).text( 'Ik betaal ' + bedrag.toLocaleString( undefined, { style: 'currency', currency: 'EUR' } ) + ' en word meteen ingedeeld.' );
		$( 'label[for=kleistad_betaal_stort]' ).text( 'Ik betaal door storting van ' + bedrag.toLocaleString( undefined, { style: 'currency', currency: 'EUR' } ) + ' volgens de betaalinstructie in de te ontvangen email. Indeling vindt daarna plaats.' );
		$( '#kleistad_cursus_naam' ).val( cursus.naam ).trigger( 'change' );
	}

	function wijzigVelden( cursus ) {
		$( '#kleistad_cursus_draaien' ).hide();
		$( '#kleistad_cursus_boetseren' ).hide();
		$( '#kleistad_cursus_handvormen' ).hide();
		$( '#kleistad_cursus_technieken' ).hide();
		$( 'input[name^=technieken]' ).prop( 'checked', false );
		$( '#kleistad_cursus_technieklijst' ).html( '' );
		$.each(
			cursus.technieken,
			function( key, value ) {
				$( '#kleistad_cursus_' + value.toLowerCase() ).show();
				$( '#kleistad_cursus_technieken' ).show();
			}
		);
		if ( cursus.meer && ! cursus.vol && ( 1 < cursus.ruimte ) ) {
			$( '#kleistad_cursus_aantal' ).show();
		} else {
			$( '#kleistad_cursus_aantal' ).hide();
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
			let $cursus_checked = $( 'input[name=cursus_id]:radio:checked' );
			if ( 0 !== $cursus_checked.length ) {
				wijzigVelden( $cursus_checked.data( 'cursus' ) );
			}

			/**
			 * Initialiseer de tooltips voor de cursussen.
			 */
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

			/**
			 * Initialiseer de aantal spinner.
			 */
			$( '#kleistad_aantal' ).spinner(
				{
					min:1,
					max: function() {
						$cursus_checked = $( 'input[name=cursus_id]:radio:checked' );
						return ( 0 !== $cursus_checked.length ) ? $cursus_checked.data( 'cursus' ).ruimte : 1
					},
					stop: function() {
						$cursus_checked = $( 'input[name=cursus_id]:radio:checked' );
						if ( 0 !== $cursus_checked.length ) {
							wijzigTeksten( $cursus_checked.data( 'cursus' ) );
						}
					},
					create: function() {
						$cursus_checked = $( 'input[name=cursus_id]:radio:checked' );
						if ( 0 !== $cursus_checked.length ) {
							wijzigTeksten( $cursus_checked.data( 'cursus' ) );
						}
					}
				}
			).trigger( 'change' );

			$( '.kleistad-shortcode' )
			/**
			 * De teksten en velden zijn afhankelijk van de cursus keuze.
			 */
			.on(
				'change',
				'input[name=cursus_id]:radio',
				function() {
					let cursus = $( 'input[name=cursus_id]:radio:checked' ).data( 'cursus' );
					wijzigTeksten( cursus );
					wijzigVelden( cursus );
				}
			)
			/**
			 * Wijzig de tekst button afhankelijk of er per bank betaald gaat worden of per ideal.
			 */
			.on(
				'change',
				'input[name=betaal]:radio',
				function() {
					$( '#kleistad_submit' ).html( ( 'ideal' === $( this ).val() ) ? 'betalen' : 'verzenden' );
				}
			)
			/**
			 * Vul adresvelden in
			 */
			.on(
				'change',
				'#kleistad_huisnr, #kleistad_pcode',
				function() {
					let pcode = $( '#kleistad_pcode' );
					pcode.val( pcode.val().toUpperCase() );
					$().lookupPostcode(
						pcode.val(),
						$( '#kleistad_huisnr' ).val(),
						/**
						 * Anonieme functie, simuleer de trigger zodat de data ook beschikbaar is voor de bevestig velden.
						 *
						 * @param {object} data
						 * @param {string} data.straat
						 * @param {string} data.plaats
						 */
						function( data ) {
							$( '#kleistad_straat' ).val( data.straat ).trigger( 'change' );
							$( '#kleistad_plaats' ).val( data.plaats ).trigger( 'change' );
						}
					);
				}
			)
			.on(
				'change',
				'#kleistad_draaien, #kleistad_handvormen, #kleistad_boetseren',
				function() {
					let $lijst = $( '#kleistad_cursus_technieklijst' ),
					lijst      = '';
					$( 'input[name^=technieken]' ).each(
						function() {
							if ( $( this ).is( ':checked' ) ) {
								lijst += ( lijst.length ? ', ' : '' ) + $( this ).val().toLowerCase();
							}
						}
					);
					$lijst.val( lijst.length ? ( ' met gekozen technieken: ' + lijst ) : '' );
					$lijst.trigger( 'change' );
				}
			);

		}
	);

} )( jQuery );
