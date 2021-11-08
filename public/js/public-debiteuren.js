/**
 * Debiteuren beheer Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

( function( $ ) {
	'use strict';

	/**
	 * Lees de zoekterm.
	 */
	function leesFilters() {
		const zoek = window.sessionStorage.getItem( 'debiteur_filter' );
		if ( zoek ) {
			$( '#kleistad_zoek_icon' ).data( 'id', zoek );
			$( '#kleistad_zoek' ).val( zoek );
		}
	}

	/**
	 * Laden bij refresh.
	 */
	function onLoad() {
		leesFilters();
	}

	/**
	 * Idem in geval van ajax return.
	 */
	$( document ).ajaxComplete(
		function() {
			onLoad();
		}
	);

	/**
	 * Document ready.
	 */
	$(
		function()
		{

			onLoad();

			$( '.kleistad-shortcode' )
			.on(
				'click',
				'#kleistad_deb_bankbetaling',
				function() {
					$( '.kleistad_deb_veld' ).hide();
					$( '.kleistad_deb_bankbetaling' ).toggle( this.checked );
					$( '#kleistad_submit_debiteuren' ).prop( 'disabled', false ).data( 'confirm', 'Debiteuren|Klopt het bedrag van de bankbetaling ?' ).val( 'bankbetaling' );
				}
			)
			.on(
				'click',
				'#kleistad_deb_korting',
				function() {
					$( '.kleistad_deb_veld' ).hide();
					$( '.kleistad_deb_korting' ).toggle( this.checked );
					$( '#kleistad_submit_debiteuren' ).prop( 'disabled', false ).data( 'confirm', 'Debiteuren|Klopt het bedrag van de korting ?' ).val( 'korting' );
				}
			)
			.on(
				'click',
				'#kleistad_deb_afboeken',
				function() {
					$( '.kleistad_deb_veld' ).hide();
					$( '.kleistad_deb_afboeken' ).toggle( this.checked );
					$( '#kleistad_submit_debiteuren' ).prop( 'disabled', false ).data( 'confirm', 'Debiteuren|Verwacht je inderdaad dat er niet meer betaald wordt ?' ).val( 'afboeken' );
				}
			)
			.on(
				'click',
				'#kleistad_deb_annulering',
				function() {
					$( '.kleistad_deb_veld' ).hide();
					$( '.kleistad_deb_annulering' ).toggle( this.checked );
					$( '#kleistad_submit_debiteuren' ).prop( 'disabled', false ).data( 'confirm', 'Debiteuren|Klopt het bedrag van het restant te betalen ?' ).val( 'annulering' );
				}
			)
			.on(
				'change',
				'#kleistad_zoek',
				function() {
					window.sessionStorage.setItem( 'debiteur_filter', $( this ).val() );
					$( '#kleistad_zoek_icon' ).data( 'id', $( this ).val() );
				}
			)
			.on(
				'keydown',
				'#kleistad_zoek',
				function( event ) {
					if ( 13 === event.which ) {
						window.sessionStorage.setItem( 'debiteur_filter', $( this ).val() );
						$( '#kleistad_zoek_icon' ).data( 'id', $( this ).val() ).trigger( 'click' );
					}
				}
			);
		}
	);

} )( jQuery );
