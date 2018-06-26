<?php
/**
 * Definieer de Kleistad gebruikers class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * De Kleistad gebruiker class
 */
class Kleistad_Gebruiker extends Kleistad_Entity {

	const META_KEY = 'contactinfo';

	/**
	 * Het gebruiker id
	 *
	 * @since 4.0.87
	 *
	 * @access private
	 * @var int $_gebruiker_id het WP user id.
	 */
	protected $_gebruiker_id = 0;

	/**
	 * Constructor
	 *
	 * @since 4.0.87
	 *
	 * @param int $gebruiker_id wp user id van de gebruiker.
	 * @return null.
	 */
	public function __construct( $gebruiker_id = null ) {
		$default_data = [
			'telnr'          => '',
			'straat'         => '',
			'huisnr'         => '',
			'pcode'          => '',
			'plaats'         => '',
			'email'          => '',
			'voornaam'       => '',
			'achternaam'     => '',
			'gebruikersnaam' => '',
			'rol'            => '',
		];
		if ( ! is_null( $gebruiker_id ) && $gebruiker_id ) {
			$this->_gebruiker_id  = $gebruiker_id;
			$gebruiker            = get_userdata( $gebruiker_id );
			$contactinfo          = get_user_meta( $gebruiker_id, self::META_KEY, true );
			$this->_data          = wp_parse_args( $contactinfo, $default_data );
			$this->achternaam     = $gebruiker->last_name;
			$this->voornaam       = $gebruiker->first_name;
			$this->email          = $gebruiker->user_email;
			$this->gebruikersnaam = $gebruiker->user_login;
			$this->rol            = $gebruiker->roles;
		} else {
			$this->_data = $default_data;
		}
	}

	/**
	 * Export functie privacy gevoelige data.
	 *
	 * @since 4.3.0
	 *
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return array De persoonlijke data (contact info).
	 */
	public static function export( $gebruiker_id ) {
		$gebruiker = new static( $gebruiker_id );
		$items[]   = [
			'group_id'    => self::META_KEY,
			'group_label' => 'contact informatie',
			'item_id'     => 'contactinfo-1',
			'data'        => [
				[
					'name'  => 'telefoonnummer',
					'value' => $gebruiker->telnr,
				],
				[
					'name'  => 'straat',
					'value' => $gebruiker->straat,
				],
				[
					'name'  => 'nummer',
					'value' => $gebruiker->huisnr,
				],
				[
					'name'  => 'postcode',
					'value' => $gebruiker->pcode,
				],
				[
					'name'  => 'plaats',
					'value' => $gebruiker->plaats,
				],
			],
		];
		return $items;
	}

	/**
	 * Erase functie privacy gevoelige data.
	 *
	 * @since 4.3.0
	 *
	 * @param  int $gebruiker_id Het gebruiker id.
	 * @return int Aantal persoonlijke data (contact info) verwijderd.
	 */
	public static function erase( $gebruiker_id ) {
		$gebruiker         = new static( $gebruiker_id );
		$gebruiker->telnr  = '******';
		$gebruiker->straat = '******';
		$gebruiker->huisnr = '******';
		$gebruiker->pcode  = '******';
		$gebruiker->plaats = '******';
		$gebruiker->save();
		return 5;
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
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
	 * Set attribuut van het object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( $attribuut, $waarde ) {
		switch ( $attribuut ) {
			default:
				$this->_data[ $attribuut ] = $waarde;
		}
	}

	/**
	 * Bewaar de gebruiker als meta data in de database.
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
				|| ( '' === $user->role ) ) { // Bestaande gebruiker zonder rol (een cursist).
				$this->_gebruiker_id = $user->ID;
				$userdata            = [
					'ID'            => $user->ID,
					'user_nicename' => $this->voornaam . ' ' . $this->achternaam,
					'display_name'  => $this->voornaam . ' ' . $this->achternaam,
					'first_name'    => $this->voornaam,
					'last_name'     => $this->achternaam,
				];
				$result              = wp_update_user( $userdata );
			} else {
				return false; // Email adres bestaat al.
			}
		} elseif ( 0 === $this->_gebruiker_id ) { // Nieuw email adres, dus nieuwe gebruiker.
			$uniek           = '';
			$nice_voornaam   = strtolower( preg_replace( '/[^a-zA-Z\s]/', '', remove_accents( $this->voornaam ) ) );
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
				'role'            => '',
			];
			$result   = wp_insert_user( $userdata );
		} else { // Dit zou niet mogen gebeuren.
			return false;
		}

		if ( is_wp_error( $result ) ) {
			return false;
		} else {
			$this->_gebruiker_id = $result;
			update_user_meta(
				$this->_gebruiker_id, self::META_KEY, [
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

	/**
	 * Return alle gebruikers.
	 *
	 * @return array gebruikers.
	 */
	public static function all() {
		$gebruikers = get_users(
			[
				'fields'  => [ 'id' ],
				'orderby' => [ 'nicename' ],
			]
		);
		foreach ( $gebruikers as $gebruiker ) {
			$arr[ $gebruiker->id ] = new Kleistad_Gebruiker( $gebruiker->id );
		}
		return $arr;
	}
}
