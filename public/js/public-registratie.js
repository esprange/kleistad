/* global wp */

( function( $ ) {
	'use strict';

	function checkPasswordStrength( $pass1,
			$pass2,
			$strengthResult,
			$submitButton,
			blacklistArray ) {
		var pass1 = $pass1.val(),
			pass2 = $pass2.val(),
			strength;

		$submitButton.attr( 'disabled', 'disabled' );
		$strengthResult.removeClassWildcard( 'kleistad_pwd' );
		blacklistArray = blacklistArray.concat( wp.passwordStrength.userInputBlacklist() );
		strength       = wp.passwordStrength.meter( pass1, blacklistArray, pass2 );

		switch ( strength ) {
			case 2:
				$strengthResult.addClass( 'kleistad_pwd_zwak' ).html( 'zwak' );
				break;
			case 3:
				$strengthResult.addClass( 'kleistad_pwd_goed' ).html( 'goed' );
				break;
			case 4:
				$strengthResult.addClass( 'kleistad_pwd_sterk' ).html( 'sterk' );
				break;
			case 5:
				$strengthResult.addClass( 'kleistad_pwd_ongelijk' ).html( 'verschillend' );
				break;
			default:
				$strengthResult.addClass( 'kleistad_pwd_erg_zwak' ).html( 'zeer zwak' );
		}
		if ( 3 <= strength && '' !== pass2.trim() ) {
			$submitButton.removeAttr( 'disabled' );
		}
	}

	$( document ).ready(
        function() {

			$( '.kleistad_shortcode' )
			.on( 'keyup', 'input[name=nieuw_wachtwoord], input[name=bevestig_wachtwoord]',
			function() {
				checkPasswordStrength(
					$( 'input[name=nieuw_wachtwoord]' ),
					$( 'input[name=bevestig_wachtwoord]' ),
					$( '#wachtwoord_sterkte' ),
					$( 'button[type=submit]' ),
					[ 'kleistad', 'amersfoort', 'wachtwoord', 'atelier', 'pottenbakken', 'draaischijf', 'keramiek' ]
				);
				}
			);
        }
	);

} )( jQuery );
