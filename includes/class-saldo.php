<?php
/**
 * De definitie van de (stook) saldo class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Klasse voor het beheren van de stook saldo.
 *
 * @property float  bedrag
 * @property array  storting
 * @property float  prijs
 */
class Saldo extends Artikel {

	public const DEFINITIE = [
		'prefix' => 'S',
		'naam'   => 'stooksaldo',
		'pcount' => 1,
	];
	public const META_KEY  = 'kleistad_stooksaldo';

	/**
	 * De beginwaarden van een dagdelenkaart.
	 *
	 * @access private
	 * @var array $default_data de standaard waarden bij het aanmaken van een dagdelenkaart.
	 */
	private array $default_data = [
		'storting' => [
			[],
		],
		'bedrag'   => 0.0,
	];

	/**
	 * De reden waarvoor het saldo gewijzigd wordt. Is alleen voor de logging bedoeld.
	 *
	 * @access public
	 * @var string $reden De reden.
	 */
	public string $reden = '';

	/**
	 * Het actie object
	 *
	 * @var SaldoActie $actie De saldo acties.
	 */
	public SaldoActie $actie;

	/**
	 * De constructor
	 *
	 * @since      4.0.87
	 *
	 * @param int $klant_id De gebruiker waarvoor het saldo wordt gemaakt.
	 */
	public function __construct( int $klant_id ) {
		$this->klant_id = $klant_id;
		$saldo          = get_user_meta( $this->klant_id, self::META_KEY, true ) ?: $this->default_data;
		$this->data     = wp_parse_args( $saldo, $this->default_data );
		$this->actie    = new SaldoActie( $this );
		$this->betaling = new SaldoBetaling( $this );
	}

	/**
	 * Getter magic functie
	 *
	 * @since      4.0.87
	 *
	 * @param string $attribuut Het attribuut waarvan de waarde wordt opgevraagd.
	 * @return mixed De waarde.
	 */
	public function &__get( string $attribuut ) {
		if ( array_key_exists( $attribuut, $this->data ) ) {
			return $this->data[ $attribuut ];
		}
		$laatste_storting = end( $this->data['storting'] );
		if ( array_key_exists( $attribuut, $laatste_storting ) ) {
			return $laatste_storting[ $attribuut ];
		}
		$null = null; // nodig omdat de waarde by reference teruggegeven moet worden.
		return $null;
	}

	/**
	 * Setter magic functie
	 *
	 * @since      4.0.87
	 *
	 * @param string $attribuut Het attribuut waarvan de waarde wordt aangepast.
	 * @param mixed  $waarde De nieuwe waarde.
	 */
	public function __set( string $attribuut, $waarde ) {
		if ( 'bedrag' === $attribuut ) {
			$this->data[ $attribuut ] = round( $waarde, 2 );
			return;
		}
		if ( 'storting' === $attribuut ) {
			$this->data['storting'][] = $waarde;
			return;
		}
		$this->data['storting'][ array_key_last( $this->data['storting'] ) ][ $attribuut ] = $waarde;
	}

	/**
	 * Geef de code terug.
	 *
	 * @return string
	 */
	public function geef_referentie() : string {
		return $this->code;
	}

	/**
	 * Verzenden van de saldo verhoging email.
	 *
	 * @since      4.0.87
	 *
	 * @param string $type    Direct betaald of melding van storting.
	 * @param string $factuur Een bij te sluiten factuur.
	 * @return boolean succes of falen van verzending email.
	 */
	public function verzend_email( string $type, string $factuur = '' ) : bool {
		$emailer   = new Email();
		$gebruiker = get_userdata( $this->klant_id );
		return $emailer->send(
			[
				'to'          => "$gebruiker->display_name <$gebruiker->user_email>",
				'subject'     => 'Bijstorting stooksaldo',
				'slug'        => 'saldo' . $type,
				'attachments' => $factuur ?: [],
				'parameters'  => [
					'voornaam'   => $gebruiker->first_name,
					'achternaam' => $gebruiker->last_name,
					'bedrag'     => number_format_i18n( $this->prijs, 2 ),
					'saldo'      => number_format_i18n( $this->bedrag, 2 ),
					'saldo_link' => $this->betaal_link,
				],
			]
		);
	}

	/**
	 * Bewaar het aangepaste saldo
	 *
	 * @since      4.0.87
	 *
	 * @return bool True als saldo is aangepast.
	 */
	public function save() : bool {
		$saldo = get_user_meta( $this->klant_id, self::META_KEY, true );
		if ( $saldo === $this->data ) {
			return true;
		}
		$huidig_saldo = $saldo['bedrag'] ?? 0.0;
		if ( update_user_meta( $this->klant_id, self::META_KEY, $this->data ) ) {
			$tekst = get_userdata( $this->klant_id )->display_name . ' nu: ' . number_format_i18n( $huidig_saldo, 2 ) . ' naar: ' . number_format_i18n( $this->bedrag, 2 ) . ' vanwege ' . $this->reden;
			file_put_contents(  // phpcs:ignore
				wp_upload_dir()['basedir'] . '/stooksaldo.log',
				date( 'c' ) . " : $tekst\n",
				FILE_APPEND
			);
			return true;
		}
		return false;
	}

	/**
	 * Geef de status van het artikel als een tekst terug.
	 *
	 * @return string De status tekst.
	 */
	public function geef_statustekst() : string {
		return 0 < $this->bedrag ? 'saldo' : '';
	}

	/**
	 * Verwijder het saldo
	 */
	public function erase() {
		delete_user_meta( $this->klant_id, self::META_KEY );
	}

	/**
	 * De factuur regels.
	 *
	 * @return Orderregel
	 */
	protected function geef_factuurregels() : Orderregel {
		return new Orderregel( 'stooksaldo', 1, $this->prijs );
	}

}
