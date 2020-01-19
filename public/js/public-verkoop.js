( function( $ ) {
    'use strict';

	$( document ).ready(
        function() {

			$( '.kleistad_shortcode' )
			/**
			 * Als er een andere foto gekozen wordt.
			 */
			.on( 'click', '.extra_regel',
				function() {
					var $oldRow, $newRow;
					$oldRow = $( this ).closest( '.kleistad_row' ).prev();
					$newRow = $oldRow.clone().find( 'input' ).val( '' ).end();
					$oldRow.after( $newRow );
					return false;
				}
			);
        }
    );

} )( jQuery );
