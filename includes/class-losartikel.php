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

	/**
	 * De constructor
	 *
	 * @since      6.2.0
	 *
	 * @param int $verkoop_id Een uniek id van de verkoop.
	 */
	public function __construct( $verkoop_id ) {
		$this->betalen = new \Kleistad\Betalen();
		$this->data    = [
			'regels' => [],
			'klant'  => [],
			'prijs'  => 0.0,
			'code'   => "X$verkoop_id",
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
		$this->data[ $attribuut ] = $waarde;
	}

	/**
	 * Geef de artikel naam.
	 *
	 * @return string
	 */
	public function artikel_naam() {
		return 'losse verkoop';
	}

	/**
	 * Betalen functie, wordt niet gebruikt.
	 *
	 * @param string $bericht Dummy variable.
	 */
	public function betalen( $bericht ) {
		$order_id = \Kleistad\Order::zoek_order( $this->referentie() );
		$order    = new \Kleistad\Order( $order_id );
		return $this->betalen->order(
			[
				'naam'     => $order->klant['naam'],
				'email'    => $order->klant['email'],
				'order_id' => $this->code,
			],
			__CLASS__ . '-' . $this->code,
			$order->te_betalen(),
			'Kleistad bestelling ' . $this->code,
			$bericht
		);
	}

	/**
	 * Geef de code terug.
	 *
	 * @return string
	 */
	public function referentie() {
		return $this->code;
	}

	/**
	 * Klant gegevens voor op de factuur.
	 *
	 * @return array De naw gegevens.
	 */
	public function naw_klant() {
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
	public function email( $type = '', $factuur = '' ) {
		$emailer = new \Kleistad\Email();
		return $emailer->send(
			[
				'to'          => "{$this->klant['naam']} <{$this->klant['email']}>",
				'subject'     => 'Bestelling Kleistad op ' . date( 'd-m-Y' ),
				'slug'        => 'bestelling' . $type,
				'attachments' => $factuur,
				'parameters'  => [
					'naam'        => $this->klant['naam'],
					'bedrag'      => number_format_i18n( $this->prijs, 2 ),
					'bestel_link' => $this->betaal_link(),
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
		$regels       = $this->regels;
		$regels[]     = array_merge(
			self::split_bedrag( $prijs ),
			[
				'artikel' => $artikel,
				'aantal'  => $aantal,
			]
		);
		$this->regels = $regels;
		$this->prijs += $aantal * $prijs;
	}

	/**
	 * Dummy functie voor status.
	 *
	 * @param bool $uitgebreid Dummy variabele.
	 */
	public function status( $uitgebreid = false ) {
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
	protected function factuurregels() {
		return $this->regels;
	}

	/**
	 * Dummy functie voor dagelijks.
	 */
	public static function dagelijks() {
	}

	/**
	 * Verwerk een betaling. Wordt aangeroepen vanuit de betaal callback
	 *
	 * @since      6.2.0
	 *
	 * @param array $parameters De parameters 0: volgnummer bestelling.
	 * @param float $bedrag     Het bedrag dat betaald is.
	 * @param bool  $betaald    Of er werkelijk betaald is.
	 */
	public static function callback( $parameters, $bedrag, $betaald ) {
		if ( $betaald ) {
			$losartikel        = new static( intval( $parameters[0] ) );
			$order_id          = \Kleistad\Order::zoek_order( $losartikel->code );
			$order             = new \Kleistad\Order( $order_id );
			$losartikel->klant = $order->klant;
			$losartikel->ontvang_order( $order_id, $bedrag );
			$losartikel->email( '_ideal' );
		}
	}

}
