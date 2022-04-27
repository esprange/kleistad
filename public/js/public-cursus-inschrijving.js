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
		const $spin = $( '#kleistad_aantal' );
		let	aantal  = $spin.spinner( 'value' ),
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
			let $cursus_checked, $aantal;

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
			$aantal = $( '#kleistad_aantal' ).spinner(
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
			);

			/**
			 * Initialiseer velden.
			 */
			$cursus_checked	= $( 'input[name=cursus_id]:radio:checked, input[name=cursus_id]' );
			if ( 0 !== $cursus_checked.length ) {
				wijzigVelden( $cursus_checked.data( 'cursus' ) );
				wijzigTeksten( $cursus_checked.data( 'cursus' ) );
			}
			$( '#kleistad_cursus_naam' ).trigger( 'change' );
			$aantal.trigger( 'spinchange' );

			/**
			 * De event handlers.
			 */
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
			 * Bepaal de technieken
			 */
			.on(
				'change',
				'#kleistad_draaien, #kleistad_handvormen, #kleistad_boetseren',
				function() {
					const $lijst = $( '#kleistad_cursus_technieklijst' );
					let lijst    = '';
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
