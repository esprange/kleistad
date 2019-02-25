( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {
			$( '#kleistad_klembord' ).click(
				function() {
					$( '#kleistad_email_lijst' ).kleistad_klembord();
				}
			);
        }
    );
} )( jQuery );
