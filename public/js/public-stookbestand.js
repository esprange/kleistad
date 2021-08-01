/* global strtodate */

( function( $ ) {
	'use strict';

	$(
		function()
		{
			var $vanaf_datum = $( '#kleistad_vanaf_datum' ),
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
