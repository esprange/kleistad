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

	let verberg_vervallen;

	/**
	 * Vouw alle communicatie samen en verwijder de buttons als de tekst volledig in het venster past.
	 */
	function fold_all() {
		$( 'div[id^=kleistad_communicatie_]' ).each(
			function() {
				let $comm_veld = $( this ).find( 'div.kleistad-workshop-communicatie' ),
					overflow   = $comm_veld.prop( 'scrollHeight' ) > $comm_veld.prop( 'clientHeight' );
				$( this ).find( 'button.kleistad-workshop-unfold' ).toggle( overflow ).html( 'Meer...' );
				$comm_veld.toggleClass( 'kleistad-workshop-communicatie-folded', overflow );
			}
		)
	}

	/**
	 * Toon of verberg de vervallen workshops.
	 *
	 * @param initieel Initieel moeten de workshops verborgen zijn.
	 */
	function toon_vervallen( initieel = false ) {
		if ( initieel ) {
			verberg_vervallen = false;
		}
		$( '#kleistad_workshops' ).DataTable().column( ':contains(Status)' ).search( verberg_vervallen ? '' : '^((?!vervallen).)*$', true ).draw();
		verberg_vervallen = ! verberg_vervallen;
	}

	/**
	 * Initialisatie
	 */
	function onLoad() {
		$( '#kleistad_workshops' ).DataTable().on(
			'draw',
			function() {
				let $filter = $( '#kleistad_workshops_filter' ),
					current = $filter.html();
				if ( ! $( '#kleistad_toon_vervallen' ).length ) {
					$filter.html( current + '<div><label for="kleistad_toon_vervallen"> toon vervallen <input type="checkbox" id="kleistad_toon_vervallen"></label></div>' );
					toon_vervallen( true );
				}
			}
		)
		$( '#kleistad_workshopbeheer' ).tabs(
			{
				activate: function() {
					fold_all();
				}
			}
		);
	}

	/**
	 * Na een Ajax return.
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
			/**
			 * Toggle het toon vervallen filter.
			 */
			.on(
				'change',
				'#kleistad_toon_vervallen',
				function () {
					toon_vervallen();
				}
			)
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
					let $communicatie = $( this ).parents( 'div[id^=kleistad_communicatie_]' ),
						$comm_veld    = $communicatie.find( '.kleistad-workshop-communicatie' ),
						folded        = $comm_veld.hasClass( 'kleistad-workshop-communicatie-folded' );
					$comm_veld.toggleClass( 'kleistad-workshop-communicatie-folded', ! folded );
					$( this ).html( folded ? 'Minder...' : 'Meer...' );
					return false;
				}
			)
		}
	);

} )( jQuery );
