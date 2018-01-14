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
			$this->_data = ( '' === $contactinfo ) ? $default_data : $contactinfo;
			$this->_data['achternaam'] = $gebruiker->last_name;
			$this->_data['voornaam'] = $gebruiker->first_name;
			$this->_data['email'] = $gebruiker->user_email;
			$this->_data['gebruikersnaam'] = $gebruiker->user_login;
			$this->_data['rol'] = $gebruiker->roles;
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

		$gebruiker_id = email_exists( $this->_data['email'] );
		if ( $gebruiker_id ) {
			$user = get_userdata( $gebruiker_id );

			if ( ( $this->_gebruiker_id == $user->ID ) // WPCS: loose comparison ok.
				|| ( '' === $user->role ) ) { // Existing user with no role re-registered.
				$this->_gebruiker_id = $user->ID;
				$userdata = [
					'ID' => $user->ID,
					'user_nicename' => $this->_data['voornaam'] . ' ' . $this->_data['achternaam'],
					'display_name' => $this->_data['voornaam'] . ' ' . $this->_data['achternaam'],
					'first_name' => $this->_data['voornaam'],
					'last_name' => $this->_data['achternaam'],
				];
				$result = wp_update_user( $userdata );
			} else {
				return false; // Email exists, but entered as new user.
			}
		} elseif ( 0 === $this->_gebruiker_id ) { // New email, thus new user.
			$uniek = '';
			$startnaam = strtolower( $this->_data['voornaam'] );
			while ( username_exists( $startnaam . $uniek ) ) {
				$uniek = intval( $uniek ) + 1;
			}
			$this->_data['gebruikersnaam'] = $this->_data['voornaam'] . $uniek;

			$userdata = [
				'user_login' => $this->_data['gebruikersnaam'],
				'user_pass' => wp_generate_password( 12, true ),
				'user_email' => $this->_data['email'],
				'user_nicename' => $this->_data['voornaam'] . ' ' . $this->_data['achternaam'],
				'display_name' => $this->_data['voornaam'] . ' ' . $this->_data['achternaam'],
				'first_name' => $this->_data['voornaam'],
				'last_name' => $this->_data['achternaam'],
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
					'telnr' => $this->_data['telnr'],
					'straat' => $this->_data['straat'],
					'huisnr' => $this->_data['huisnr'],
					'pcode' => $this->_data['pcode'],
					'plaats' => $this->_data['plaats'],
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
