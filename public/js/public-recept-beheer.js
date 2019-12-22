/* global: FileReader */

( function( $ ) {
    'use strict';

	$( document ).ready(
        function() {

			$( '.kleistad_shortcode' )
			/**
			 * Als er een andere foto gekozen wordt.
			 */
			.on( 'change', '#kleistad_foto_input',
				function() {
					var reader = new FileReader();

					if ( this.files && this.files[0] ) {
						if ( this.files[0].size > 2000000 ) {
							window.alert( 'deze foto is te groot (' + this.files[0].size + ' bytes)' );
							$( this ).val( '' );
							return false;
						}
						reader.onload = function() {
							$( '#kleistad_foto' ).attr( 'src', reader.result );
						};
						reader.readAsDataURL( this.files[0] );
					}
					return undefined;
				}
			)
			/**
			 * Extra regel toevoegen.
			 */
			.on( 'click', '.extra_regel',
				function() {
					var $oldRow, $newRow;
					$oldRow = $( this ).closest( 'tr' ).prev();
					$newRow = $oldRow.clone().find( 'input' ).val( '' ).end();
					$oldRow.after( $newRow );
					return false;
				}
			);
        }
    );

} )( jQuery );
