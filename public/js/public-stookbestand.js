/* global strtodate */

( function( $ ) {
	'use strict';

    $( function()
		{
			/**
			 * Initieer de startdatum.
			 */
			$( '#kleistad_vanaf_datum' ).datepicker( 'option',
				{
					minDate: new Date( '7/1/2017' ),
					maxDate: 0,
					onSelect: function( datum ) {
						$( '#kleistad_tot_datum' ).datepicker( 'option', { minDate: strtodate( datum ) } );
					}
				}
			);

			/**
			 * Initieer de einddatum.
			 */
			$( '#kleistad_tot_datum' ).datepicker( 'option',
				{
					minDate: new Date( $( '#kleistad_vanaf_datum' ).val() ),
					maxDate: 0,
					onSelect: function( datum ) {
						$( '#kleistad_vanaf_datum' ).datepicker( 'option', { maxDate: strtodate( datum ) } );
					}
				}
			);

		}

    );

} )( jQuery );
