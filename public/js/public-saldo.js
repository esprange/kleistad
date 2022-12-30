/**
 * Saldo Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

( function( $ ) {
	'use strict';

	/**
	 * Document ready.
	 */
	$(
		function()
		{
			$( '.kleistad-shortcode' )
			.on(
				'keydown',
				'input[name=ander]',
				function( event ) {
					$( '#kleistad_ander' ).prop( 'checked',true );
					let charC = ( event.which ) ? event.which : event.keyCode;
					if ( 32 >= charC ) { // Control keys, zoals delete, standaard afhandeling.
						return true;
					}
					let commaSet = this.value.indexOf( ',' );
					if ( ( 44 === charC || 188 === charC ) && -1 === commaSet ) {
						// De comma, 44 in ASCII, 188 in unicode. Er mag er maar 1 aanwezig zijn.
						event.target.value = event.target.value + event.key;
					}
					if ( 48 <= charC && 57 >= charC && ( -1 === commaSet || 2 >= this.value.length - commaSet ) ) {
						// Cijfer, er mogen maar twee na de comma geplaatst worden.
						event.target.value = event.target.value + event.key;
					}
					$( this ).trigger( 'input' );
					return event.preventDefault();
				}
			)
			.on(
				'input',
				'.kleistad-saldo-select',
				function() {
					$( '#kleistad_ander' ).val( $( 'input[name=ander]' ).val().replace( ',', '.' ) );
					if ( $( '#kleistad_betaal_terugboeking' ).is( ':checked' ) ) {
						$( '#kleistad_betaal_ideal' ).prop( 'checked', true );
					}
					let bedrag      = $( 'input[name=bedrag]:radio:checked' ).val();
					let bedragTekst = new Intl.NumberFormat( 'nl-NL', { style: 'currency', currency: 'EUR' } ).format( bedrag );
					let bedragValid = 2 <= bedrag && 100 >= bedrag;
					$( '#kleistad_submit' ).prop( 'disabled', ! bedragValid );
					if ( ! bedragValid ) {
						$( 'label[for=kleistad_betaal_ideal],label[for=kleistad_betaal_stort]' ).text( 'Het bij te storten bedrag moet minimaal 2 en maximaal 100 euro zijn' );
						return;
					}
					$( 'label[for=kleistad_betaal_ideal]' ).text( 'ik betaal ' + bedragTekst + ' en verhoog mijn saldo.' );
					$( 'label[for=kleistad_betaal_stort]' ).text( 'ik betaal door storting van ' + bedragTekst + '. Verhoging saldo vindt daarna plaats.' );
				}
			)
			/**
			 * Als er voor terugstorten wordt gekozen, moet er een bevestiging gevraagd worden.
			 */
			.on(
				'change',
				'input[name=betaal]',
				function() {
					const $submit = $( '#kleistad_submit' );
					if ( 'terugboeking' === $( this ).val() ) {
						$submit.prop( 'disabled', false ).data( 'confirm',  'Saldo terugstorten|Weet je zeker dat je het saldo wil laten terugboeken ?' );
						return;
					}
					$( '.kleistad-saldo-select' ).trigger( 'input');
					$submit.removeData( 'confirm' );
				}
			);

			$( '.kleistad-saldo-select' ).trigger( 'input' );

		}
	);

} )( jQuery );
