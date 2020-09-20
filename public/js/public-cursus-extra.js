( function( $ ) {
	'use strict';

    $( document ).ready(
        function() {
			$( '.kleistad_input' ).on( 'change',
				function() {
					var waarde       = '',
						$medecursist = $( this ).parents( '.medecursist' );
					$medecursist.find( '.kleistad_input' ).each(
						function() {
							waarde += $( this ).val();
						}
					);
					$medecursist.find( '.kleistad_input').prop( 'required', '' !== waarde );
				} 
			);
        }
    );

} )( jQuery );
