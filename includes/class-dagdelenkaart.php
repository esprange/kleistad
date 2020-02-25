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
 * @property string opmerking
 */
class Dagdelenkaart extends Artikel {

	const META_KEY = 'kleistad_dagdelenkaart';

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
		$this->betalen   = new \Kleistad\Betalen();
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
		switch ( $attribuut ) {
			case 'datum':
			case 'start_datum':
				return strtotime( $this->data[ $attribuut ] );
			default:
				if ( is_string( $this->data[ $attribuut ] ) ) {
					return htmlspecialchars_decode( $this->data[ $attribuut ] );
				}
				return $this->data[ $attribuut ];
		}
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( $attribuut, $waarde ) {
		switch ( $attribuut ) {
			case 'datum':
			case 'start_datum':
				$this->data[ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			default:
				$this->data[ $attribuut ] = is_string( $waarde ) ? trim( $waarde ) : $waarde;
		}
	}

	/**
	 * Verwijder de dagdelenkaart, niet alleen de laatste maar ook alle voorgaande.
	 */
	public function erase() {
		delete_user_meta( $this->klant_id, self::META_KEY );
	}

	/**
	 * Geef de artikel naam.
	 *
	 * @return string
	 */
	public function artikel_naam() {
		return 'dagdelenkaart';
	}

	/**
	 * Start de betaling van een nieuw dagdelenkaart.
	 *
	 * @param string $bericht  Te tonen melding als betaling gelukt.
	 * @return string|bool De redirect url van een ideal betaling of false als het niet lukt.
	 */
	public function ideal( $bericht ) {
		$options = \Kleistad\Kleistad::get_options();

		return $this->betalen->order(
			$this->klant_id,
			$this->referentie(),
			$options['dagdelenkaart'],
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
	public function referentie() {
		return $this->code;
	}

	/**
	 * Verzenden van de welkomst email.
	 *
	 * @param string $type    Welke email er verstuurd moet worden.
	 * @param string $factuur Bij te sluiten factuur.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type, $factuur = '' ) {
		$emailer   = new \Kleistad\Email();
		$options   = \Kleistad\Kleistad::get_options();
		$gebruiker = get_userdata( $this->klant_id );
		return $emailer->send(
			[
				'to'          => "$gebruiker->display_name <$gebruiker->user_email>",
				'subject'     => 'Welkom bij Kleistad',
				'slug'        => "dagdelenkaart$type",
				'attachments' => $factuur,
				'parameters'  => [
					'voornaam'                => $gebruiker->first_name,
					'achternaam'              => $gebruiker->last_name,
					'start_datum'             => strftime( '%d-%m-%Y', $this->start_datum ),
					'dagdelenkaart_code'      => $this->code,
					'dagdelenkaart_opmerking' => empty( $this->opmerking ) ? '' : "De volgende opmerking heb je doorgegeven: $this->opmerking",
					'dagdelenkaart_prijs'     => number_format_i18n( $options['dagdelenkaart'], 2 ),
					'dagdelenkaart_link'      => $this->betaal_link(),
				],
			]
		);
	}

	/**
	 * Geef de factuur regels.
	 *
	 * @return array De regels.
	 */
	protected function factuurregels() {
		$options = \Kleistad\Kleistad::get_options();
		return [
			array_merge(
				self::split_bedrag( $options['dagdelenkaart'] ),
				[
					'artikel' => 'dagdelenkaart, start datum ' . strftime( '%d-%m-%Y', $this->start_datum ),
					'aantal'  => 1,
				]
			),
		];
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
	public function status( $uitgebreid = false ) {
		$vandaag = strtotime( 'today' );
		if ( $this->start_datum > $vandaag ) {
			return $uitgebreid ? 'gaat starten per ' . strftime( '%d-%m-%Y', $this->start_datum ) : 'nieuw';
		} elseif ( strtotime( '+3 month', $this->start_datum ) <= $vandaag ) {
			return $uitgebreid ? 'actief tot ' . strftime( '%d-%m-%Y', strtotime( '+3 month', $this->start_datum ) ) : 'actief';
		} else {
			return $uitgebreid ? 'voltooid per ' . strftime( '%d-%m-%Y', strtotime( '+3 month', $this->start_datum ) ) : 'voltooid';
		}
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
					$this->email( '_ideal_betaald' );
				}
			} else { // Betaling vanuit inschrijvingformulier.
				$this->email( '_ideal', $this->bestel_order( $bedrag, $this->start_datum, '', $transactie_id ) );
			}
		}
	}

	/**
	 * Dagelijkse handelingen.
	 */
	public static function dagelijks() {
		// Geen functionaliteit vooralsnog.
	}

	/**
	 * Return alle dagdelenkaarten.
	 *
	 * @return array dagdelenkaarten.
	 */
	public static function all() {
		$arr        = [];
		$gebruikers = get_users(
			[
				'meta_key' => self::META_KEY,
				'fields'   => [ 'ID' ],
			]
		);
		foreach ( $gebruikers as $gebruiker ) {
			$arr[ $gebruiker->ID ] = new \Kleistad\Dagdelenkaart( $gebruiker->ID );
		}
		return $arr;
	}
}
