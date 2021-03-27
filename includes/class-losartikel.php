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
	 * Het betaling object
	 *
	 * @var LosArtikelBetaling $betaling Het betaal object.
	 */
	public LosArtikelBetaling $betaling;

	/**
	 * Lijst van orderregels
	 *
	 * @var array $orderregels De regels.
	 */
	private $orderregels = [];

	/**
	 * De constructor
	 *
	 * @since      6.2.0
	 *
	 * @param int $verkoop_id Een uniek id van de verkoop.
	 */
	public function __construct( $verkoop_id ) {
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
	public function __get( $attribuut ) {
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
	public function __set( $attribuut, $waarde ) {
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
	 * @return boolean succes of falen van verzending email.
	 */
	public function verzend_email( $type = '', $factuur = '' ) {
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
	public function bestelregel( $artikel, $aantal, $prijs ) {
		$this->orderregels[] = new Orderregel( $artikel, $aantal, $prijs );
		$this->prijs        += $aantal * $prijs;
	}

	/**
	 * De factuur regels.
	 *
	 * @return array
	 */
	protected function geef_factuurregels() {
		return $this->orderregels;
	}

}
