( function( $ ) {
	'use strict';

    $( function()
		{
			$( '.kleistad-input' ).on( 'change',
				function() {
					var waarde       = '',
						$medecursist = $( this ).parents( '.medecursist' );
					$medecursist.find( '.kleistad-input' ).each(
						function() {
							waarde += $( this ).val();
						}
					);
					$medecursist.find( '.kleistad-input').prop( 'required', '' !== waarde );
				} 
			);
        }
    );

} )( jQuery );
