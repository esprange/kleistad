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
		}
		return [false, '', 'gesloten' ];
	}

	function eerder( datum ) {
		var unixTime  = datum.getTime() / 1000 - datum.getTimezoneOffset() * 60;
		var index     = $.inArray( unixTime, datums );
		var datumEerder;
		if ( 0 === index ) {
			return datum.toLocaleDateString();
		} else {
			datumEerder = new Date( datums[ index - 1 ] * 1000 );
			return datumEerder.toLocaleDateString();
		}
	}

	function later( datum ) {
		var unixTime  = datum.getTime() / 1000 - datum.getTimezoneOffset() * 60;
		var index     = $.inArray( unixTime, datums );
		var datumLater;
		if ( index === datums.lastIndexOf() ) {
			return datum.toLocaleDateString();
		} else {
			datumLater = new Date( datums[ index + 1 ] * 1000 );
			return datumLater.toLocaleDateString();
		}
	}

	function onLoad() {
		datums = $( '#kleistad_datum' ).data( 'datums' );
		$( '.kleistad_corona, .kleistad_meester' ).button(
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
			var dialogMeester, dialogReserveer;

			onLoad();

			dialogMeester = $( '#kleistad_meester' ).dialog(
				{
					autoOpen: false,
					height: 'auto',
					width: 360,
					modal: true,
					open: function() {
						var blokdeel = $( this ).data( 'blokdeel' );
						$( '#kleistad_meester_selectie' ).val( $( '#meester' + blokdeel ).val() );
						$( '#kleistad_meester_standaard').val( $( '#meester' + blokdeel ).prev( 'input' ).val() );
					},
					buttons: {
						'OK': function() {
							var blokdeel = $( this ).data( 'blokdeel' );
							$( '#meester' + blokdeel ).val( $( '#kleistad_meester_selectie' ).val() );
							$( '#meester' + blokdeel ).button( 'option', 'label', $( "#kleistad_meester_selectie option:selected" ).text() );
							$( '#standaard' + blokdeel ).val( $( '#kleistad_meester_standaard' ).prop( 'checked') ? 1 : 0 );
							dialogMeester.dialog( 'close' );
						}
					}
				}
			);

			dialogReserveer = $( '#kleistad_reserveer' ).dialog(
				{
					autoOpen: false,
					height: 'auto',
					width: 360,
					modal: true,
					open: function() {
						// var blokdeel = $( this ).data( 'blokdeel' );
						// $( '#kleistad_meester_selectie' ).val( $( '#meester' + blokdeel ).val() );
						// $( '#kleistad_meester_standaard').val( $( '#meester' + blokdeel ).prev( 'input' ).val() );
					},
					buttons: {
						'OK': function() {
							// var blokdeel = $( this ).data( 'blokdeel' );
							// $( '#meester' + blokdeel ).val( $( '#kleistad_meester_selectie' ).val() );
							// $( '#meester' + blokdeel ).button( 'option', 'label', $( "#kleistad_meester_selectie option:selected" ).text() );
							// $( '#standaard' + blokdeel ).val( $( '#kleistad_meester_standaard' ).prop( 'checked') ? 1 : 0 );
							dialogReserveer.dialog( 'close' );
						}
					}
				}
			);


			$( '.kleistad_shortcode' )
			.on( 'change', '#kleistad_datum',
				function() {
					window.location.assign( window.location.origin + window.location.pathname + '?datum=' + $( this ).val() );
				}
			)
			.on( 'click', '#kleistad_eerder',
				function() {
					window.location.assign( window.location.origin + window.location.pathname + '?datum=' + eerder( $( '#kleistad_datum' ).datepicker( 'getDate') ) );
				}
			)
			.on( 'click', '#kleistad_later',
				function() {
					window.location.assign( window.location.origin + window.location.pathname + '?datum=' + later( $( '#kleistad_datum' ).datepicker( 'getDate') ) );
				}
			)
			.on( 'click', '.kleistad_corona',
				function() {
					$( this ).button( 'option', 'label', $( this ).is( ':checked' ) ? $( '#kleistad_naam' ).val() : 'reserveren' );
					disableOnCheck();
				}
			).on( 'change', '#kleistad_gebruiker',
				function() {
					window.location.assign( window.location.origin + window.location.pathname + '?gebruiker=' + $( this ).val() );
				}
			).on( 'click', '.kleistad_meester',
				function() {
					$( '#kleistad_meester' ).data( 'blokdeel', $( this ).data( 'blokdeel' ) );
					$( '#kleistad_meester' ).dialog( 'option', 'title', $( this ).data( 'tijd' ) ).dialog( 'open' );
				}
			);
		}
    );

} )( jQuery );
