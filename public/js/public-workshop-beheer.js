/**
 * Workshop beheer Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

/* global timetostr, strtotime */

( function( $ ) {
	'use strict';

	/**
	 * Document ready.
	 */
	$(
		function()
		{
			$( '.kleistad-shortcode' )
			/**
			 * Voorkom dat checkboxes gewijzigd kunnen worden als readonly form.
			 */
			.on(
				'click',
				'#kleistad_workshop_beheer_form input[type=checkbox]',
				function() {
					return ! $( this ).attr( 'readonly' );
				}
			)
			/**
			 * Bepaal de limieten van het start tijd invoerveld.
			 */
			.on(
				'change',
				'#kleistad_start_tijd',
				function() {
					let $eind_tijd = $( '#kleistad_eind_tijd' ),
						startTijd  = strtotime( $( this ).val() ),
						eindTijd   = strtotime( $eind_tijd.val() );
					if ( startTijd + 60 > eindTijd ) {
						$eind_tijd.val( timetostr( Math.min( startTijd + 60, 24 * 60 ) ) );
					}
				}
			)
			/**
			 * Bepaal de limieten voor het eind tijd invoerveld.
			 */
			.on(
				'change',
				'#kleistad_eind_tijd',
				function() {
					let $start_tijd = $( '#kleistad_start_tijd' ),
						startTijd   = strtotime( $start_tijd.val() ),
						eindTijd    = strtotime( $( this ).val() );
					if ( startTijd > eindTijd - 60 ) {
						$start_tijd.val( timetostr( Math.max( eindTijd - 60, 0 ) ) );
					}
				}
			)
			/**
			 * Pas de ex btw kosten aan als het incl btw kosten veld wijzigt.
			 */
			.on(
				'change paste keyup',
				'#kleistad_kosten',
				function() {
					$( '#kleistad_kosten_ex_btw' ).val( ( $( this ).val() / 1.21 ).toFixed( 2 ) );
				}
			)
			/**
			 * Pas de incl btw kosten aan als het excl btw kosten veld wijzigt.
			 */
			.on(
				'change paste keyup',
				'#kleistad_kosten_ex_btw',
				function() {
					$( '#kleistad_kosten' ).val( ( $( this ).val() * 1.21 ).toFixed( 2 ) );
				}
			)
			/**
			 * Klap het veld uit.
			 */
			.on(
				'click',
				'.kleistad-workshop-unfold',
				function() {
					$( this ).parent().prev( '.kleistad-workshop-correspondentie' ).toggleClass( 'kleistad-workshop-correspondentie-folded' );
					$( this ).hide().next( '.kleistad-workshop-fold' ).show();
					return false;
				}
			)
			/**
			 * Klap het veld in.
			 */
			.on(
				'click',
				'.kleistad-workshop-fold',
				function() {
					$( this ).parent().prev( '.kleistad-workshop-correspondentie' ).toggleClass( 'kleistad-workshop-correspondentie-folded' );
					$( this ).hide().prev( '.kleistad-workshop-unfold' ).show();
					return false;
				}
			);
		}
	);

} )( jQuery );
