<?php
/**
 * De definitie van de los artikel class
 *
 * @link       https://www.kleistad.nl
 * @since      6.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Klasse voor het beheren van losse artikelen verkoop.
 *
 * @property string code
 * @property array  regels
 * @property array  klant
 * @property float  prijs
 */
class LosArtikel extends Artikel {

	public const DEFINITIE = [
		'prefix' => 'X',
		'naam'   => 'overige verkoop',
		'pcount' => 1,
	];

	/**
	 * Lijst van orderregels
	 *
	 * @var array $orderregels De regels.
	 */
	private array $orderregels = [];

	/**
	 * De constructor
	 *
	 * @since      6.2.0
	 *
	 * @param int|null $verkoop_id Een uniek id van de verkoop.
	 */
	public function __construct( ?int $verkoop_id = null ) {
		if ( is_null( $verkoop_id ) ) {
			$verkoop_id = intval( get_option( 'kleistad_losnr', 0 ) );
			update_option( 'kleistad_losnr', ++$verkoop_id );
		}
		$this->data     = [
			'klant' => [],
			'prijs' => 0.0,
			'code'  => "X$verkoop_id",
		];
		$this->betaling = new LosArtikelBetaling( $this );
	}

	/**
	 * Getter magic functie
	 *
	 * @since      6.2.0
	 *
	 * @param string $attribuut Het attribuut waarvan de waarde wordt opgevraagd.
	 * @return mixed De waarde.
	 */
	public function __get( string $attribuut ) {
		return $this->data[ $attribuut ];
	}

	/**
	 * Setter magic functie
	 *
	 * @since      6.2.0
	 *
	 * @param string $attribuut Het attribuut waarvan de waarde wordt aangepast.
	 * @param mixed  $waarde De nieuwe waarde.
	 */
	public function __set( string $attribuut, $waarde ) {
		$this->data[ $attribuut ] = is_string( $waarde ) ? trim( $waarde ) : $waarde;
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
	 * Klant gegevens voor op de factuur.
	 *
	 * @return array De naw gegevens.
	 */
	public function naw_klant() : array {
		return $this->klant;
	}

	/**
	 * Verzenden van de verkoop email.
	 *
	 * @since      6.2.0
	 *
	 * @param string $type    Direct betaald of melding van storting.
	 * @param string $factuur Een bij te sluiten factuur.
	 * @return bool succes of falen van verzending email.
	 */
	public function verzend_email( $type = '', $factuur = '' ) : bool {
		$emailer = new Email();
		return $emailer->send(
			[
				'to'          => "{$this->klant['naam']} <{$this->klant['email']}>",
				'subject'     => 'Bestelling Kleistad op ' . date( 'd-m-Y' ),
				'slug'        => 'bestelling' . $type,
				'attachments' => $factuur ?: [],
				'parameters'  => [
					'naam'        => $this->klant['naam'],
					'bedrag'      => number_format_i18n( $this->prijs, 2 ),
					'bestel_link' => $this->betaal_link,
				],
			]
		);
	}

	/**
	 * Voeg een bestelregel toe.
	 *
	 * @param string $artikel De artikelnaam.
	 * @param float  $aantal  Het aantal artikelen.
	 * @param float  $prijs   De bruto prijs per artikel.
	 */
	public function bestelregel( string $artikel, float $aantal, float $prijs ) {
		$this->orderregels[] = new Orderregel( $artikel, $aantal, $prijs );
		$this->prijs        += $aantal * $prijs;
	}

	/**
	 * De factuur regels.
	 *
	 * @return array
	 */
	protected function geef_factuurregels() : array {
		return $this->orderregels;
	}

}
