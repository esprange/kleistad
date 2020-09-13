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

/**
 * Kleistad Google class.
 *
 * @since 5.4.1
 */
class Google {

	const ACCESS_TOKEN  = 'kleistad_google_access_token';
	const REFRESH_TOKEN = 'kleistad_google_refresh_token';
	const REDIRECT_URI  = 'kleistad_google_redirect_uri';

	/**
	 * De Google kalender service.
	 *
	 * @var \Google_Service $calendar_service De google service.
	 */
	private static $calendar_service = null;

	/**
	 * Het Google kalender id.
	 *
	 * @var string $kalender_id De google kalender id.
	 */
	private static $kalender_id = null;

	/**
	 * Maak een Google API client aan.
	 *
	 * @return \Google_Client|bool $client of false.
	 */
	private static function maak_client() {
		$redirect_uri = get_option( self::REDIRECT_URI );
		if ( false === $redirect_uri ) {
			return false;
		}
		$setup             = \Kleistad\Kleistad::get_setup();
		self::$kalender_id = $setup['google_kalender_id'];
		$client            = new \Google_Client();
		$client->setApplicationName( 'Kleistad_Calendar' );
		$client->setAccessType( 'offline' );
		$client->setClientId( $setup['google_client_id'] );
		$client->setClientSecret( $setup['google_sleutel'] );
		$client->setIncludeGrantedScopes( true );
		$client->addScope( \Google_Service_Calendar::CALENDAR_EVENTS );
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
	 * @since 5.0.0
	 * @param  string $redirect_url De url welke gebruikt moet worden na authenticatie.
	 */
	public static function vraag_service_aan( $redirect_url ) {
		update_option( self::REDIRECT_URI, $redirect_url );
		delete_option( self::REFRESH_TOKEN );
		// 13-09-2020 i.p.v. update_option naar lege string.
		$client = self::maak_client();
		if ( false === $client ) {
			return;
		}
		wp_redirect( $client->createAuthUrl() ); // phpcs:ignore
		die();
	}

	/**
	 * Koppel met google service.
	 *
	 * @since 5.0.0
	 * @return \WP_ERROR|bool Succes of error(s).
	 */
	public static function koppel_service() {
		$error = new \WP_Error();

		$authorization_code = filter_input( INPUT_GET, 'code' );
		if ( ! empty( $authorization_code ) ) {
			delete_option( self::REFRESH_TOKEN );
			delete_option( self::ACCESS_TOKEN );

			$client = self::maak_client();
			if ( false === $client ) {
				$error->add( 'google', 'Client service is niet aangemaakt' );
				return $error;
			}
			$token = $client->fetchAccessTokenWithAuthCode( $authorization_code );
			if ( isset( $token['error'] ) && ! empty( $token['error'] ) ) {
				$error->add( 'google', 'Authenticatie fout, Google meldt ' . $token['error_description'] . ' : ' . $token['error'] );
				return $error;
			} else {
				$client->setAccessToken( $token );
				if ( isset( $token['refresh_token'] ) ) {
					update_option( self::REFRESH_TOKEN, $token['refresh_token'] );
				} else {
					$error->add( 'google', 'Google heeft niet het juiste token gestuurd, trek de toestemming voor de app in via: https://myaccount.google.com/u/0/permissions' );
					return $error;
				}
				if ( isset( $token['access_token'] ) ) {
					update_option( self::ACCESS_TOKEN, $token );
				} else {
					delete_option( self::ACCESS_TOKEN );
					$error->add( 'google', 'Google heeft niet het juiste token gestuurd, onduidelijk wat er aan de hand is....' );
					return $error;
				}
			}
		}
		return true;
	}

	/**
	 * Maak de Google services aan.
	 *
	 * @since 6.1.0
	 *
	 * @throws \Exception Als er geen connecties gemaakt kunnen worden.
	 */
	private static function create_services() {
		$client = self::maak_client();
		if ( false === $client ) {
			throw new \Exception( 'Google maak client failure' );
		}
		if ( $client->isAccessTokenExpired() ) {
			if ( $client->getRefreshToken() ) {
				$client->fetchAccessTokenWithRefreshToken( $client->getRefreshToken() );
				update_option( self::ACCESS_TOKEN, $client->getAccessToken() );
			} else {
				delete_option( self::ACCESS_TOKEN );
				throw new \Exception( 'Google refresh token failure' );
			}
		}
		self::$calendar_service = new \Google_Service_Calendar( $client );
	}

	/**
	 * Maak de Google Calendar service aan.
	 *
	 * @since 5.0.0
	 * @return \Google_Service_Calendar de service.
	 */
	public static function calendar_service() {
		if ( is_null( self::$calendar_service ) ) {
			self::create_services();
		}
		return self::$calendar_service;
	}

	/**
	 * Geef kalender id terug.
	 *
	 * @since 5.4.1
	 * @return string Kalender id.
	 */
	public static function kalender_id() {
		$setup             = \Kleistad\Kleistad::get_setup();
		self::$kalender_id = $setup['google_kalender_id'];
		return self::$kalender_id;
	}

	/**
	 * Bepaal of er connectie is met Google.
	 *
	 * @since 5.0.0
	 * @return bool succes of falen
	 */
	public static function is_authorized() {
		if ( false !== get_option( self::ACCESS_TOKEN ) ) {
			return is_object( self::calendar_service() );
		}
		return false;
	}

}
