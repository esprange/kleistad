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

/**
 * Kleistad Google class.
 *
 * @since 5.4.1
 */
class Kleistad_Google {

	const ACCESS_TOKEN  = 'kleistad_google_access_token';
	const REFRESH_TOKEN = 'kleistad_google_refresh_token';
	const REDIRECT_URI  = 'kleistad_google_redirect_uri';

	/**
	 * De Google kalender service.
	 *
	 * @var Google_Service $service De google service.
	 */
	private static $service = null;

	/**
	 * Het Google kalender id.
	 *
	 * @var string $kalender_id De google kalender id.
	 */
	private static $kalender_id = null;

	/**
	 * Maak een Google API client aan.
	 *
	 * @return Google_Client|bool $client of false.
	 */
	private static function maak_client() {
		$redirect_uri = get_option( self::REDIRECT_URI );
		if ( false === $redirect_uri ) {
			return false;
		}
		$refresh_token = get_option( self::REFRESH_TOKEN );
		if ( false === $refresh_token ) {
			return false;
		}
		$options           = Kleistad::get_options();
		self::$kalender_id = $options['google_kalender_id'];
		$client            = new Google_Client();
		$client->setApplicationName( 'Kleistad_Calendar' );
		$client->setAccessType( 'offline' );
		$client->setClientId( $options['google_client_id'] );
		$client->setClientSecret( $options['google_sleutel'] );
		$client->setIncludeGrantedScopes( true );
		$client->addScope( 'email' );
		$client->addScope( Google_Service_Calendar::CALENDAR_EVENTS );
		$client->setRedirectUri( $redirect_uri );
		$client->refreshToken( $refresh_token );
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
		$error = new WP_Error();

		$authorization_code = filter_input( INPUT_GET, 'code' );
		if ( ! empty( $authorization_code ) ) {
			delete_option( self::REFRESH_TOKEN );
			delete_option( self::ACCESS_TOKEN );

			$client = self::maak_client();
			if ( false === $client ) {
				$error->add( 'google', 'Client service is niet aangemaakt' );
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
	 * Maak de Google Calendar service aan.
	 *
	 * @since 5.0.0
	 * @return de service.
	 */
	public static function service() {
		if ( ! is_null( self::$service ) ) {
			return self::$service;
		}
		$client = self::maak_client();
		if ( false === $client ) {
			error_log( '!!! Google maak client failure' ); //phpcs:ignore
			return false;
		}
		if ( $client->isAccessTokenExpired() ) {
			if ( $client->getRefreshToken() ) {
				$client->fetchAccessTokenWithRefreshToken( $client->getRefreshToken() );
				update_option( self::ACCESS_TOKEN, $client->getAccessToken() );
			} else {
				error_log( '!!! Google refresh token failure' ); //phpcs:ignore
				delete_option( self::ACCESS_TOKEN );
				return false;
			}
		}
		self::$service = new Google_Service_Calendar( $client );
		return self::$service;
	}

	/**
	 * Geef kalender id terug.
	 *
	 * @since 5.4.1
	 * @return string Kalender id.
	 */
	public static function kalender_id() {
		if ( is_null( self::$kalender_id ) ) {
			$options           = Kleistad::get_options();
			self::$kalender_id = $options['google_kalender_id'];
		}
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
			return self::maak_service();
		}
		return false;
	}

}
