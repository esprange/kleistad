/* global wp, kleistadData */

( function( $ ) {
	'use strict';

	var strength = 0;

	function checkPasswordStrength( $pass1,
			$pass2,
			$strengthResult,
			$submitButton,
			disallowedListArray ) {
		var pass1 = $pass1.val(),
			pass2 = $pass2.val();

		$submitButton.attr( 'disabled', 'disabled' );
		$strengthResult.removeClassWildcard( 'kleistad-pwd' );
		disallowedListArray = disallowedListArray.concat( wp.passwordStrength.userInputDisallowedList() );
		strength            = wp.passwordStrength.meter( pass1, disallowedListArray, pass2 );

		switch ( strength ) {
			case 2:
				$strengthResult.addClass( 'kleistad-pwd-zwak' ).html( 'zwak' );
				break;
			case 3:
				$strengthResult.addClass( 'kleistad-pwd-goed' ).html( 'goed' );
				if ( '' !== pass2.trim() ) {
					$submitButton.removeAttr( 'disabled' );
				}
				break;
			case 4:
				$strengthResult.addClass( 'kleistad-pwd-sterk' ).html( 'sterk' );
				if ( '' !== pass2.trim() ) {
					$submitButton.removeAttr( 'disabled' );
				}
				break;
			case 5:
				$strengthResult.addClass( 'kleistad-pwd-ongelijk' ).html( 'verschillend' );
				break;
			default:
				$strengthResult.addClass( 'kleistad-pwd-erg-zwak' ).html( 'zeer zwak' );
		}
	}

	$( function()
		{
			$( '.kleistad-shortcode' )
			.on( 'keyup', 'input[name=nieuw_wachtwoord], input[name=bevestig_wachtwoord]',
			function() {
				strength = checkPasswordStrength(
					$( 'input[name=nieuw_wachtwoord]' ),
					$( 'input[name=bevestig_wachtwoord]' ),
					$( '#wachtwoord_sterkte' ),
					$( '#kleistad_wachtwoord' ),
					[ 'kleistad', 'amersfoort', 'wachtwoord', 'atelier', 'pottenbakken', 'draaischijf', 'keramiek' ]
				);
				}
			)
			.on( 'click', '#kleistad_wachtwoord',
			function() {
				var data = {
					'action'    : 'kleistad_wachtwoord',
					'actie'     : 'wijzig_wachtwoord',
					'wachtwoord': $( '#nieuw_wachtwoord' ).val(),
					'security'  : kleistadData.nonce
				};
				$.post( kleistadData.admin_url, data, function( response ) {
					if ( response === 'success' ) {
						$( '#kleistad_wachtwoord_fout' ).hide();
						$( '#kleistad_wachtwoord_form' ).hide();
						$( '#kleistad_wachtwoord_succes' ).show();
					} else if ( response === 'error' ) {
						$( '#kleistad_wachtwoord_fout' ).show();
						$( '#kleistad_wachtwoord_form' ).show();
						$( '#kleistad_wachtwoord_succes' ).hide();
					}
				});
			});
        }
	);

} )( jQuery );
