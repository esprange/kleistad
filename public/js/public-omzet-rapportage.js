( function( $ ) {
	'use strict';

    $( document ).ready(
		function() {

			$( '.kleistad_shortcode' )
			.on( 'change', '#kleistad_maand',
				function() {
					$( '#kleistad_rapport' ).data( 'id', $( '#kleistad_jaar' ).val() + '-' +  $( this ).val() ).click();
				}
			)
			.on( 'change', '#kleistad_jaar',
				function() {
					$( '#kleistad_rapport' ).data( 'id', $( this ).val() + '-' + $( '#kleistad_maand' ).val() ).click();
				}
			);

		}

    );

} )( jQuery );
