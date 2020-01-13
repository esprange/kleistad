/* global wp, pwsL10n */

( function( $ ) {
	'use strict';

	function checkPasswordStrength( $pass1,
			$pass2,
			$strengthResult,
			$submitButton,
			blacklistArray ) {
		var pass1 = $pass1.val();
		var pass2 = $pass2.val();

		// Reset the form & meter
		$submitButton.attr( 'disabled', 'disabled' );
		$strengthResult.removeClassWildcard( 'kleistad_pwd' );

		// Extend our blacklist array with those from the inputs & site data
		blacklistArray = blacklistArray.concat( wp.passwordStrength.userInputBlacklist() );

		// Get the password strength
		var strength = wp.passwordStrength.meter( pass1, blacklistArray, pass2 );

		// Add the strength meter results
		switch ( strength ) {
			case 2:
				$strengthResult.addClass( 'kleistad_pwd_zwak' ).html( pwsL10n.bad );
				break;
			case 3:
				$strengthResult.addClass( 'kleistad_pwd_goed' ).html( pwsL10n.good );
				break;
			case 4:
				$strengthResult.addClass( 'kleistad_pwd_sterk' ).html( pwsL10n.strong );
				break;
			case 5:
				$strengthResult.addClass( 'kleistad_pwd_ongelijk' ).html( pwsL10n.mismatch );
				break;
			default:
				$strengthResult.addClass( 'kleistad_pwd_erg_zwak' ).html( pwsL10n.short );
		}
		// The meter function returns a result even if pass2 is empty,
		// enable only the submit button if the password is strong and
		// both passwords are filled up
		if ( 3 <= strength && '' !== pass2.trim() ) {
			$submitButton.removeAttr( 'disabled' );
		}
		return strength;
	}

	$( document ).ready(
        function() {

			$( '.kleistad_shortcode' )
			.on( 'keyup', 'input[name=nieuw_wachtwoord], input[name=bevestig_wachtwoord]',
			function() {
				checkPasswordStrength(
					$( 'input[name=nieuw_wachtwoord]' ),        // First password field
					$( 'input[name=bevestig_wachtwoord]' ),     // Second password field
					$( '#wachtwoord_sterkte' ),                 // Strength meter
					$( 'button[type=submit]' ),                  // Submit button
					[ 'kleistad', 'amersfoort', 'wachtwoord', 'atelier', 'pottenbakken', 'draaischijf', 'keramiek' ]  // Blacklisted words
					);
				}
			);
        }
	);

} )( jQuery );
