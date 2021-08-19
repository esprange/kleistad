/**
 * Registratie Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

/* global wp, kleistadData */

( function( $ ) {
	'use strict';

	function checkPasswordStrength( pass1, pass2, $strengthResult, $submitButton, disallowedListArray ) {
		$submitButton.attr( 'disabled', 'disabled' );
		$strengthResult.removeClassWildcard( 'kleistad-pwd' );
		disallowedListArray = disallowedListArray.concat( wp.passwordStrength.userInputDisallowedList() );

		switch ( wp.passwordStrength.meter( pass1, disallowedListArray, pass2 ) ) {
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

	/**
	 * Document ready.
	 */
	$(
		function()
		{
			$( '.kleistad-shortcode' )
			.on(
				'keyup',
				'input[name=nieuw_wachtwoord], input[name=bevestig_wachtwoord]',
				function() {
					checkPasswordStrength(
						$( 'input[name=nieuw_wachtwoord]' ).val(),
						$( 'input[name=bevestig_wachtwoord]' ).val(),
						$( '#wachtwoord_sterkte' ),
						$( '#kleistad_wachtwoord' ),
						[ 'kleistad', 'amersfoort', 'wachtwoord', 'atelier', 'pottenbakken', 'draaischijf', 'keramiek' ]
					);
				}
			)
			.on(
				'click',
				'#kleistad_wachtwoord',
				function() {
					var data = {
						'action'    : 'kleistad_wachtwoord',
						'actie'     : 'wijzig_wachtwoord',
						'wachtwoord': $( '#nieuw_wachtwoord' ).val(),
						'security'  : kleistadData.nonce
					};
					$.post(
						kleistadData.admin_url,
						data,
						function( response ) {
							if ( response === 'success' ) {
								$( '#kleistad_wachtwoord_fout' ).hide();
								$( '#kleistad_wachtwoord_form' ).hide();
								$( '#kleistad_wachtwoord_succes' ).show();
							} else if ( response === 'error' ) {
								$( '#kleistad_wachtwoord_fout' ).show();
								$( '#kleistad_wachtwoord_form' ).show();
								$( '#kleistad_wachtwoord_succes' ).hide();
							}
						}
					);
				}
			);
		}
	);

} )( jQuery );
