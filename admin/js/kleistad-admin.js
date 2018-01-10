( function( $ ) {
	'use strict';

	$( document ).ready(
		function() {
				/**
		 * Voeg 15 euro toe.
		 */
				$( '#add15' ).click(
					function() {
						var saldo = $( '#saldo' ).val();
						saldo = Math.round( ( Number( saldo ) + 15 ) * 100 ) / 100;
						$( '#saldo' ).val( saldo );
						return false;
					}
				);

				/**
		 * Voeg 30 euro toe.
		 */
				$( '#add30' ).click(
					function() {
						var saldo = $( '#saldo' ).val();
						saldo = Math.round( ( Number( saldo ) + 30 ) * 100 ) / 100;
						$( '#saldo' ).val( saldo );
						return false;
					}
				);
		}
	);

} )( jQuery );
