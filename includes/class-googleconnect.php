<?php
/**
 * Definieer de google class
 *
 * @link       https://www.kleistad.nl
 * @since      5.4.1
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use WP_Error;
use Exception;
use Google;
use Google\Service\Calendar;

/**
 * Kleistad Google class.
 *
 * @since 5.4.1
 */
class Googleconnect {

	const ACCESS_TOKEN  = 'kleistad_google_access_token';
	const REFRESH_TOKEN = 'kleistad_google_refresh_token';
	const REDIRECT_URI  = 'kleistad_google_redirect_uri';

	/**
	 * Maak een Google API client aan.
	 *
	 * @return Google\Client|bool $client of false.
	 */
	private function maak_client() {
		$redirect_uri = get_option( self::REDIRECT_URI );
		if ( false === $redirect_uri ) {
			return false;
		}
		$client = new Google\Client();
		$client->setApplicationName( 'Kleistad_Calendar' );
		$client->setAccessType( 'offline' );
		$client->setClientId( setup()['google_client_id'] );
		$client->setClientSecret( setup()['google_sleutel'] );
		$client->setIncludeGrantedScopes( true );
		$client->addScope( Calendar::CALENDAR_EVENTS );
		$client->setRedirectUri( $redirect_uri );
		$refresh_token = get_option( self::REFRESH_TOKEN );
		if ( false !== $refresh_token ) {
			$client->refreshToken( $refresh_token );
		}
		$token = $client->getAccessToken();
		if ( ! empty( $token ) ) {
			update_option( self::ACCESS_TOKEN, $token );
			$client->setAccessToken( $token );
		}
		return $client;
	}

	/**
	 * Vraag koppeling met google service aan.
	 *
	 * @param  string $redirect_url De url welke gebruikt moet worden na authenticatie.
	 * @suppressWarnings(PHPMD.ExitExpression)
	 */
	public function vraag_service_aan( string $redirect_url ) {
		update_option( self::REDIRECT_URI, $redirect_url );
		delete_option( self::REFRESH_TOKEN );
		// 13-09-2020 i.p.v. update_option naar lege string.
		$client = $this->maak_client();
		if ( false === $client ) {
			return;
		}
		wp_redirect( $client->createAuthUrl() ); // phpcs:ignore
		die();
	}

	/**
	 * Koppel met google service.
	 *
	 * @return WP_ERROR|bool Succes of error(s).
	 */
	public function koppel_service() {
		$authorization_code = filter_input( INPUT_GET, 'code' );
		if ( ! empty( $authorization_code ) ) {
			delete_option( self::REFRESH_TOKEN );
			delete_option( self::ACCESS_TOKEN );

			$client = $this->maak_client();
			if ( false === $client ) {
				return new WP_Error( 'google', 'Client service is niet aangemaakt' );
			}
			$token = $client->fetchAccessTokenWithAuthCode( $authorization_code );
			if ( isset( $token['error'] ) && ! empty( $token['error'] ) ) {
				return new WP_Error( 'google', 'Authenticatie fout, Google meldt ' . $token['error_description'] . ' : ' . $token['error'] );
			}
			$client->setAccessToken( $token );
			if ( ! isset( $token['refresh_token'] ) ) {
				return new WP_Error( 'google', 'Google heeft niet het juiste token gestuurd, trek de toestemming voor de app in via: https://myaccount.google.com/u/0/permissions' );
			}
			update_option( self::REFRESH_TOKEN, $token['refresh_token'] );
			if ( ! isset( $token['access_token'] ) ) {
				return new WP_Error( 'google', 'Google heeft niet het juiste token gestuurd, onduidelijk wat er aan de hand is....' );
			}
			update_option( self::ACCESS_TOKEN, $token );
		}
		return true;
	}

	/**
	 * Maak de Google services aan.
	 *
	 * @throws Exception Als er geen connecties gemaakt kunnen worden.
	 */
	private function create_calendar_service() : Calendar {
		$client = $this->maak_client();
		if ( false === $client ) {
			throw new Exception( 'Google maak client failure' );
		}
		if ( $client->isAccessTokenExpired() ) {
			$refreshtoken = $client->getRefreshToken();
			if ( ! $refreshtoken ) {
				delete_option( self::ACCESS_TOKEN );
				throw new Exception( 'Google refresh token failure' );
			}
			$client->fetchAccessTokenWithRefreshToken( $refreshtoken );
			update_option( self::ACCESS_TOKEN, $client->getAccessToken() );
		}
		return new Calendar( $client );
	}

	/**
	 * Maak de Google Calendar service aan.
	 *
	 * @return Calendar de service.
	 * @throws Kleistad_Exception Als er iets fout gaat.
	 */
	public function calendar_service() : Calendar {
		static $calendar_service = null;
		try {
			if ( is_null( $calendar_service ) ) {
				$calendar_service = $this->create_calendar_service();
			}
			return $calendar_service;
		} catch ( Exception $e ) {
			error_log( 'Calendar: ' . $e->getMessage() ); // phpcs:ignore
			throw new Kleistad_Exception( 'Interne fout' );
		}
	}

	/**
	 * Bepaal of er connectie is met Google.
	 *
	 * @return bool succes of falen
	 * @throws Kleistad_Exception Als er iets fout gaat.
	 */
	public function is_authorized() : bool {
		if ( false !== get_option( self::ACCESS_TOKEN ) ) {
			try {
				return is_object( $this->calendar_service() );
			} catch ( Exception $e ) {
				error_log( 'Calendar: ' . $e->getMessage() ); // phpcs:ignore
				throw new Kleistad_Exception( 'Interne fout' );
			}
		}
		return false;
	}

}
