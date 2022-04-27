/**
 * Verkoop losse artikelen Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

( function( $ ) {
	'use strict';

	$(
		function()
		{
			$( '#kleistad_tabs' ).tabs(
				{
					heightStyle: 'auto',
					activate: function( event, ui ) {
						ui.newPanel.find( 'input,select' ).prop( 'required', true );
						ui.oldPanel.find( 'input,select' ).prop( 'required', false );
						$( '#kleistad_klant_type' ).val( $( this ).tabs( 'option', 'active' ) ? 'bestaand' : 'nieuw' );
					}
				}
			);

			$( '.kleistad-shortcode' )
			/**
			 * Als er een andere foto gekozen wordt.
			 */
			.on(
				'click',
				'#kleistad_extra_regel',
				function() {
					const $oldRow = $( this ).closest( '.kleistad-row' ).prev();
					const $newRow = $oldRow.clone().find( 'input' ).val( '' ).end();
					$oldRow.after( $newRow );
					return false;
				}
			)
			.on(
				'change',
				'[name^=aantal],[name^=prijs]',
				function() {
					let totaal = 0;
					$( '[name^=prijs]' ).each(
						function() {
							let prijs  = $( this ).val(),
								aantal = $( this ).closest( 'div' ).next( 'div' ).find( '[name^=aantal]' ).val();
							$( this ).closest( 'div' ).prev( 'div' ).find( '[name^=omschrijving]' ).attr( 'required', aantal > 0 );
							totaal += prijs * aantal;
						}
					);
					$( '#kleistad_submit_verkoop' ).data( 'confirm', 'Verkoop|Het totaal bedrag is ' + totaal.toLocaleString( undefined, { style: 'currency', currency: 'EUR' } ) + '. Is dit correct ?' );
				}
			);
		}
	);

} )( jQuery );
