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
	private $orderregels = [];

	/**
	 * De constructor
	 *
	 * @since      6.2.0
	 *
	 * @param int $verkoop_id Een uniek id van de verkoop.
	 */
	public function __construct( $verkoop_id ) {
		$this->betalen = new Betalen();
		$this->data    = [
			'klant' => [],
			'prijs' => 0.0,
			'code'  => "X$verkoop_id",
		];
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
	 * Betalen functie, wordt niet gebruikt.
	 *
	 * @param  string $bericht Dummy variable.
	 * @param  float  $openstaand Het bedrag dat openstaat.
	 * @return string|bool De redirect url ingeval van een ideal betaling of false als het mislukt.
	 */
	public function doe_idealbetaling( string $bericht, float $openstaand = null ) {
		$order = new Order( $this->geef_referentie() );
		return $this->betalen->order(
			[
				'naam'     => $order->klant['naam'],
				'email'    => $order->klant['email'],
				'order_id' => $this->code,
			],
			$this->geef_referentie(),
			$openstaand ?? $order->te_betalen(),
			'Kleistad bestelling ' . $this->code,
			$bericht,
			false
		);
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
	 * Dummy functie voor status.
	 *
	 * @param bool $uitgebreid Dummy variabele.
	 */
	public function geef_statustekst( $uitgebreid = false ) {
		return $uitgebreid ? '' : '';
	}

	/**
	 * Dummy functie voor save.
	 */
	public function save() {
		return true;
	}

	/**
	 * De factuur regels.
	 *
	 * @return array
	 */
	protected function geef_factuurregels() {
		return $this->orderregels;
	}

	/**
	 * Dummy functie voor dagelijks.
	 */
	public static function doe_dagelijks() {
	}

	/**
	 * Verwerk een betaling. Wordt aangeroepen vanuit de betaal callback
	 *
	 * @since      6.2.0
	 *
	 * @param int    $order_id      De order_id, als die al bekend is.
	 * @param float  $bedrag        Het bedrag dat betaald is.
	 * @param bool   $betaald       Of er werkelijk betaald is.
	 * @param string $type          Type betaling, ideal , directdebit of bank.
	 * @param string $transactie_id De betaling id.
	 */
	public function verwerk_betaling( $order_id, $bedrag, $betaald, $type, $transactie_id = '' ) {
		if ( $betaald ) {
			if ( $order_id ) {
				$order       = new Order( $order_id );
				$this->klant = $order->klant;
				$this->ontvang_order( $order_id, $bedrag, $transactie_id );
				if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting.
					$this->verzend_email( '_ideal_betaald' );
				}
			}
		}
	}

}
