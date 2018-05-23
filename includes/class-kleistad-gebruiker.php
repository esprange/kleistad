<?php
/**
 * The file that defines the gebruikers class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * The Kleistad_gebruiker class
 */
class Kleistad_Gebruiker extends Kleistad_Entity {

	/**
	 * Store the gebruiker id
	 *
	 * @since 4.0.87
	 * @access private
	 * @var int $_gebruiker_id contains WP user id.
	 */
	protected $_gebruiker_id = 0;

	/**
	 * Constructor
	 *
	 * Create the gebruiker.
	 *
	 * @since 4.0.87
	 *
	 * @param int $gebruiker_id id of the gebruiker.
	 * @return null.
	 */
	public function __construct( $gebruiker_id = null ) {
		$default_data = [
			'telnr' => '',
			'straat' => '',
			'huisnr' => '',
			'pcode' => '',
			'plaats' => '',
			'email' => '',
			'voornaam' => '',
			'achternaam' => '',
			'gebruikersnaam' => '',
			'rol' => '',
		];
		if ( ! is_null( $gebruiker_id ) && $gebruiker_id ) {
			$this->_gebruiker_id = $gebruiker_id;
			$gebruiker = get_userdata( $gebruiker_id );
			$contactinfo = get_user_meta( $gebruiker_id, 'contactinfo', true );
			$this->_data = wp_parse_args( $contactinfo, $default_data );
			$this->achternaam = $gebruiker->last_name;
			$this->voornaam = $gebruiker->first_name;
			$this->email = $gebruiker->user_email;
			$this->gebruikersnaam = $gebruiker->user_login;
			$this->rol = $gebruiker->roles;
		} else {
			$this->_data = $default_data;
		}
	}

	/**
	 * Getter, using the magic function
	 *
	 * Get attribuut from the object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut name.
	 * @return mixed Attribute value.
	 */
	public function __get( $attribuut ) {
		switch ( $attribuut ) {
			case 'id':
				return $this->_gebruiker_id;
			default:
				return $this->_data[ $attribuut ];
		}
	}

	/**
	 * Setter, using the magic function
	 *
	 * Set attribuut from the object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut name.
	 * @param mixed  $waarde Attribuut value.
	 */
	public function __set( $attribuut, $waarde ) {
		switch ( $attribuut ) {
			default:
				$this->_data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Save the data
	 *
	 * Saves the data to the database.
	 *
	 * @since 4.0.87
	 *
	 * @return null.
	 */
	public function save() {

		$gebruiker_id = email_exists( $this->email );
		if ( $gebruiker_id ) {
			$user = get_userdata( $gebruiker_id );

			if ( ( $this->_gebruiker_id == $user->ID ) // WPCS: loose comparison ok.
				|| ( '' === $user->role ) ) { // Existing user with no role re-registered.
				$this->_gebruiker_id = $user->ID;
				$userdata = [
					'ID'            => $user->ID,
					'user_nicename' => $this->voornaam . ' ' . $this->achternaam,
					'display_name'  => $this->voornaam . ' ' . $this->achternaam,
					'first_name'    => $this->voornaam,
					'last_name'     => $this->achternaam,
				];
				$result = wp_update_user( $userdata );
			} else {
				return false; // Email exists, but entered as new user.
			}
		} elseif ( 0 === $this->_gebruiker_id ) { // New email, thus new user.
			$uniek = '';
			$nice_voornaam = strtolower( preg_replace( '/[^a-zA-Z\s]/', '', remove_accents( $this->voornaam ) ) );
			$nice_achternaam = strtolower( preg_replace( '/[^a-zA-Z\s]/', '', remove_accents( $this->achternaam ) ) );

			$startnaam = $nice_voornaam;
			if ( 4 > mb_strlen( $startnaam ) ) {
				$startnaam = substr( $startnaam . $nice_achternaam, 0, 4 );
			}
			while ( username_exists( $startnaam . $uniek ) ) {
				$uniek = intval( $uniek ) + 1;
			}
			$this->gebruikersnaam = $startnaam . $uniek;

			$userdata = [
				'user_login'      => $this->gebruikersnaam,
				'user_pass'       => wp_generate_password( 12, true ),
				'user_email'      => $this->email,
				'user_nicename'   => $nice_voornaam . '-' . $nice_achternaam,
				'display_name'    => $this->voornaam . ' ' . $this->achternaam,
				'first_name'      => $this->voornaam,
				'last_name'       => $this->achternaam,
				'user_registered' => date( 'Y-m-d H:i:s' ),
				'role' => '',
			];
			$result = wp_insert_user( $userdata );
		} else { // This should not happen.
			return false;
		}

		if ( is_wp_error( $result ) ) {
			return false;
		} else {
			$this->_gebruiker_id = $result;
			update_user_meta(
				$this->_gebruiker_id, 'contactinfo', [
					'telnr'  => $this->telnr,
					'straat' => $this->straat,
					'huisnr' => $this->huisnr,
					'pcode'  => $this->pcode,
					'plaats' => $this->plaats,
				]
			);
			return $this->_gebruiker_id;
		}
	}

}

/**
 * Collection of Gebruiker
 *
 * Collection of Gebruiker, loaded from the database.
 *
 * @since 4.0.87
 *
 * @see class Kleistad_Inschrijving
 * @link URL
 */
class Kleistad_Gebruikers extends Kleistad_EntityStore {

	/**
	 * Constructor
	 *
	 * Loads the data from the database.
	 *
	 * @since 4.0.87
	 *
	 * @return null.
	 */
	public function __construct() {
		$gebruikers = get_users(
			[
				'fields' => [ 'id' ],
				'orderby' => [ 'nicename' ],
			]
		);
		foreach ( $gebruikers as $gebruiker ) {
			$this->_data[ $gebruiker->id ] = new Kleistad_Gebruiker( $gebruiker->id );
		}
	}

}
