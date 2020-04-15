( function( $ ) {
	'use strict';

	var datums;

	function disableOnCheck() {
		$( '.kleistad_corona' ).each(
			function() {
				var id   = $( this ).attr( 'id' );
				var pars = id.split( '_' );
				if ( $( this ).is( ':checked' ) ) {
					$( '[id^="' + pars[0] + '"]' ).not( '#' + id ).button( 'disable' );
				} else {
 					if ( false === $( '[id^="' + pars[0] + '"]' ).is( ':checked' ) ) {
						$( '[id^="' + pars[0] + '"]' ).button( 'enable' );
					}
				}
			}
		);

	}

	function beschikbaar( datum ) {
		var unixTime = datum.getTime() / 1000 - datum.getTimezoneOffset() * 60;
		if ( $.inArray( unixTime, datums ) !== -1 ) {
			return [true, '', 'beschikbaar' ];
		} else {
			return [false, '', 'gesloten' ];
		}
	}

	function onLoad() {
		datums = $( '#kleistad_datum' ).data( 'datums' );
		$( '.kleistad_corona' ).button(
			{
				'icon':false
			}
		);
		$( '#kleistad_datum' ).datepicker( 'option',
			{
				beforeShowDay: beschikbaar,
				minDate: new Date()
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
