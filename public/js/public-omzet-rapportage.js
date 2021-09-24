/**
 * Omzet rapportage javascript functies.
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
				'change',
				'#kleistad_maand',
				function() {
					$( '#kleistad_downloadrapport' ).prop( 'disabled', 0 === $( this ).val() );
					$( '#kleistad_rapport' ).data(
						'id',
						$( '#kleistad_jaar' ).val() + '-' + $( this ).val()
					).click();
				}
			)
			.on(
				'change',
				'#kleistad_jaar',
				function() {
					$( '#kleistad_rapport' ).data(
						'id',
						$( this ).val() + '-' + $( '#kleistad_maand' ).val()
					).click();
				}
			);
		}
	);

} )( jQuery );
