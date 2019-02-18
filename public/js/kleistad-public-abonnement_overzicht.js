( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {
			$( '#kleistad_klembord' ).click(
				function() {
					var range = document.createRange(),
					lijst     = $( '#kleistad_email_lijst' ).val(),
					selection, $temp;

					// For IE.
					if ( window.clipboardData ) {
						window.clipboardData.setData( 'Text', lijst );
					} else {
						$temp = $( '<div>' );
						$temp.css( {
							position: 'absolute',
							left:     '-1000px',
							top:      '-1000px'
						} );
						$temp.text( lijst );
						$( 'body' ).append( $temp );
						range.selectNodeContents( $temp.get( 0 ) );
						selection = window.getSelection();
						selection.removeAllRanges();
						selection.addRange( range );
						document.execCommand( 'copy', false, null );
						$temp.remove();
					}
				}
			);

        }
    );
} )( jQuery );
