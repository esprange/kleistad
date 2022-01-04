/**
 * Email versturen Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

( function( $ ) {
	'use strict';

	/**
	 * Na refresh.
	 *
	 * @property {object} $.fn.jstree
	 */
	function onLoad() {
		$( '#kleistad_gebruikers' ).jstree(
			{
				'plugins': [ 'checkbox' ]
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

			/**
			 * Event functies
			 *
			 * @property {object} $.fn.jstree
			 */
			$( '#kleistad_gebruikers' ).on(
				'changed.jstree',
				function() {
					let gebruikerIds  = [],
						selectIndexes = $( this ).jstree( 'get_selected', true );
					$.each(
						selectIndexes,
						function() {
							/**
							 * Beschrijving van deze extra properties
							 *
							 * @property li_attr
							 * @property li_attr.gebruikerid
							 */
							let gebruikerId = this.li_attr.gebruikerid;
							if ( undefined !== gebruikerId ) {
								gebruikerIds.push( gebruikerId );
							}
						}
					);
					$( '#kleistad_gebruikerids' ).val( gebruikerIds.join( ',' ) );
				}
			).on(
				'ready.jstree',
				function() {
					$( this ).show();
				}
			);
		}
	);

} )( jQuery );
