( function( $ ) {
	'use strict';

	function disableOnCheck() {
		$( '.kleistad_corona' ).each(
			function() {
				var pars  = $( this ).attr( 'id' ).split( '_' );
				var other = '#' + pars[0] + '_' + ( pars[1] === 'D' ? 'H' : 'D' );
				if ( $( this ).is( ':checked' ) ) {
					$( other ).button( 'disable' );
				} else {
					$( other ).button( 'enable' );
				}
			}
		);

	}

	function onLoad() {
		$( '.kleistad_corona' ).button(
			{
				'icon':false
			}
		);
		disableOnCheck();
	}

	$( document ).ajaxComplete(
        function() {
			onLoad();
		}
	);

    $( document ).ready(
		function()  {
			onLoad();

			$( '.kleistad_shortcode' )
			.on( 'change', '#kleistad_datum',
				function() {
					window.location.assign( window.location.origin + window.location.pathname + '?datum=' + $( this ).val() );
				}
			)
			.on( 'click', '.kleistad_corona',
				function() {
					$( this ).button( 'option', 'label', $( this ).is( ':checked' ) ? $( '#kleistad_naam' ).val() : 'reserveren' );
					disableOnCheck();
				}
			);
		}
    );

} )( jQuery );
