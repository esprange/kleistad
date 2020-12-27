<?php
/**
 * Definieer de dagdelenkaart class
 *
 * @link       https://www.kleistad.nl
 * @since      4.3.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad dagdelenkaart.
 *
 * @property int    start_datum
 * @property int    eind_datum
 * @property string opmerking
 */
class Dagdelenkaart extends Artikel {

	public const DEFINITIE = [
		'prefix' => 'K',
		'naam'   => 'dagdelenkaart',
		'pcount' => 1,
	];
	public const META_KEY  = 'kleistad_dagdelenkaart_v2';

	/**
	 * De beginwaarden van een dagdelenkaart.
	 *
	 * @access private
	 * @var array $default_data de standaard waarden bij het aanmaken van een dagdelenkaart.
	 */
	private $default_data = [
		'code'        => '',
		'datum'       => '',
		'start_datum' => '',
		'geannuleerd' => false,
		'opmerking'   => '',
	];

	/**
	 * Het volgnummer van de dagdelenkaart.
	 *
	 * @access private
	 * @var int $volgnr Het volgnummer.
	 */
	private $volgnr;

	/**
	 * Constructor
	 *
	 * @param int $klant_id wp id van de gebruiker.
	 */
	public function __construct( $klant_id ) {
		$this->klant_id  = $klant_id;
		$this->betalen   = new Betalen();
		$dagdelenkaarten = get_user_meta( $this->klant_id, self::META_KEY, true ) ?: $this->default_data;
		$this->volgnr    = count( /* @scrutinizer ignore-type */ $dagdelenkaarten );
		$this->data      = wp_parse_args( end( /* @scrutinizer ignore-type */ $dagdelenkaarten ), $this->default_data );
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( $attribuut ) {
		return array_key_exists( $attribuut, $this->data ) ? $this->data[ $attribuut ] : null;
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( $attribuut, $waarde ) {
		$this[ $attribuut ] = $waarde;
	}

	/**
	 * Verwijder de dagdelenkaart, niet alleen de laatste maar ook alle voorgaande.
	 */
	public function erase() {
		delete_user_meta( $this->klant_id, self::META_KEY );
	}

	/**
	 * Start de betaling van een nieuw dagdelenkaart.
	 *
	 * @param  string $bericht  Te tonen melding als betaling gelukt.
	 * @param  string $referentie De referentie van het artikel.
	 * @param  float  $openstaand Het bedrag dat openstaat.
	 * @return string|bool De redirect url van een ideal betaling of false als het niet lukt.
	 */
	public function doe_idealbetaling( $bericht, $referentie, $openstaand = null ) {
		$options = opties();

		return $this->betalen->order(
			$this->klant_id,
			$referentie,
			$openstaand ?? $options['dagdelenkaart'],
			'Kleistad dagdelenkaart ' . $this->code,
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
	 * Verzenden van de welkomst email.
	 *
	 * @param string $type    Welke email er verstuurd moet worden.
	 * @param string $factuur Bij te sluiten factuur.
	 * @return boolean succes of falen van verzending email.
	 */
	public function verzend_email( $type, $factuur = '' ) {
		$emailer   = new Email();
		$options   = opties();
		$gebruiker = get_userdata( $this->klant_id );
		return $emailer->send(
			[
				'to'          => "$gebruiker->display_name <$gebruiker->user_email>",
				'subject'     => 'Welkom bij Kleistad',
				'slug'        => "dagdelenkaart$type",
				'attachments' => $factuur ?: [],
				'parameters'  => [
					'voornaam'                => $gebruiker->first_name,
					'achternaam'              => $gebruiker->last_name,
					'start_datum'             => strftime( '%d-%m-%Y', $this->start_datum ),
					'dagdelenkaart_code'      => $this->code,
					'dagdelenkaart_opmerking' => empty( $this->opmerking ) ? '' : "De volgende opmerking heb je doorgegeven: $this->opmerking",
					'dagdelenkaart_prijs'     => number_format_i18n( $options['dagdelenkaart'], 2 ),
					'dagdelenkaart_link'      => $this->betaal_link,
				],
			]
		);
	}

	/**
	 * Geef de factuur regels.
	 *
	 * @return Orderregel De regels.
	 */
	protected function geef_factuurregels() {
		return new Orderregel( 'dagdelenkaart, start datum ' . strftime( '%d-%m-%Y', $this->start_datum ), 1, opties()['dagdelenkaart'] );
	}

	/**
	 * Bewaar de dagdelenkaart als metadata in de database.
	 */
	public function save() {
		$dagdelenkaarten                  = get_user_meta( $this->klant_id, self::META_KEY, true ) ?: [];
		$dagdelenkaarten[ $this->volgnr ] = $this->data;
		update_user_meta( $this->klant_id, self::META_KEY, $dagdelenkaarten );
	}

	/**
	 * Voeg een nieuwe dagdelenkaart toe.
	 *
	 * @param int    $start_datum De datum waarop de kaart in gaat.
	 * @param string $opmerking Een eventuele opmerking.
	 */
	public function nieuw( $start_datum, $opmerking ) {
		$this->volgnr++;
		$this->datum       = strtotime( 'today' );
		$this->start_datum = $start_datum;
		$this->opmerking   = $opmerking;
		$datum             = strftime( '%y%m%d', $this->datum );
		$this->code        = "K$this->klant_id-$datum-$this->volgnr";
		$this->save();
	}

	/**
	 * Geef de status van het abonnement als een tekst terug.
	 *
	 * @param  boolean $uitgebreid Uitgebreide tekst of korte tekst.
	 * @return string De status tekst.
	 */
	public function geef_statustekst( bool $uitgebreid ) : string {
		$vandaag = strtotime( 'today' );
		if ( $this->start_datum > $vandaag ) {
			return $uitgebreid ? 'gaat starten per ' . strftime( '%d-%m-%Y', $this->start_datum ) : 'nieuw';
		} elseif ( strtotime( '+3 month', $this->start_datum ) <= $vandaag ) {
			return $uitgebreid ? 'actief tot ' . strftime( '%d-%m-%Y', strtotime( '+3 month', $this->start_datum ) ) : 'actief';
		}
		return $uitgebreid ? 'voltooid per ' . strftime( '%d-%m-%Y', strtotime( '+3 month', $this->start_datum ) ) : 'voltooid';
	}

	/**
	 * Activeer een dagdelenkaart. Wordt aangeroepen vanuit de betaal callback.
	 *
	 * @param int    $order_id      De order id, als die al bestaat.
	 * @param float  $bedrag        Het bedrag dat betaald is.
	 * @param bool   $betaald       Of er werkelijk betaald is.
	 * @param string $type          Het type betaling.
	 * @param string $transactie_id De betaling id.
	 */
	public function verwerk_betaling( $order_id, $bedrag, $betaald, $type, $transactie_id = '' ) {
		if ( $betaald ) {
			if ( $order_id ) { // Factuur is eerder al aangemaakt. Betaling vanuit betaal link of bank.
				$this->ontvang_order( $order_id, $bedrag, $transactie_id );
				if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting.
					$this->verzend_email( '_ideal_betaald' );
				}
				return;
			}
			// Betaling vanuit inschrijvingformulier.
			$this->verzend_email( '_ideal', $this->bestel_order( $bedrag, $this->start_datum, '', $transactie_id ) );
		}
	}

	/**
	 * Dagelijkse handelingen.
	 */
	public static function doe_dagelijks() {
		// Geen functionaliteit vooralsnog.
	}
}
