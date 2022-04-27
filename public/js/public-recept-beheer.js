/**
 * Recept beheer Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

/* global: FileReader */

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
			 * Als er een andere foto gekozen wordt.
			 */
			.on(
				'change',
				'#kleistad_foto_input',
				function() {
					const reader = new FileReader();

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
			.on(
				'click',
				'#kleistad_extra_basis, #kleistad_extra_toevoeging',
				function() {
					const $oldRow = $( this ).closest( 'tr' ).prev();
					const $newRow = $oldRow.clone().find( 'input' ).val( '' ).end();
					$oldRow.after( $newRow );
					return false;
				}
			);
		}
	);

} )( jQuery );
