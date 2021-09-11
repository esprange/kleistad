/**
 * Stookbestand Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

/* global strtodate */

( function( $ ) {
	'use strict';

	/**
	 * Document ready.
	 */
	$(
		function()
		{
			let $vanaf_datum = $( '#kleistad_vanaf_datum' ),
				$tot_datum   = $( '#kleistad_tot_datum' );
			/**
			 * Initieer de startdatum.
			 */
			$vanaf_datum.datepicker(
				'option',
				{
					minDate: new Date( '7/1/2017' ),
					maxDate: 0,
					onSelect: function( datum ) {
						$tot_datum.datepicker( 'option', { minDate: strtodate( datum ) } );
					}
				}
			);

			/**
			 * Initieer de einddatum.
			 */
			$tot_datum.datepicker(
				'option',
				{
					minDate: new Date( $vanaf_datum.val() ),
					maxDate: 0,
					onSelect: function( datum ) {
						$vanaf_datum.datepicker( 'option', { maxDate: strtodate( datum ) } );
					}
				}
			);
		}
	);

} )( jQuery );
