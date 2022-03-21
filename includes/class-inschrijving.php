<?php
/**
 * Definieer de inschrijving class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * Kleistad Inschrijving class.
 *
 * @since 4.0.87
 *
 * @property array  technieken
 * @property array  extra_cursisten
 * @property int    hoofd_cursist_id
 * @property bool   ingedeeld
 * @property bool   geannuleerd
 * @property string opmerking
 * @property int    aantal
 * @property int    wacht_datum
 * @property bool   restant_email
 * @property bool   herinner_email
 * @property float  maatwerkkosten
 */
class Inschrijving extends Artikel {

	public const DEFINITIE         = [
		'prefix'       => 'C',
		'naam'         => 'cursus',
		'pcount'       => 2,
		'annuleerbaar' => false,
	];
	private const OPM_INSCHRIJVING = 'Een week voorafgaand de start datum van de cursus zal je een betaalinstructie ontvangen voor het restant bedrag.';
	private const EMAIL_SUBJECT    = [
		'inschrijving'     => 'Inschrijving cursus',
		'indeling'         => 'Indeling cursus',
		'_extra'           => 'Welkom cursus',
		'_herinnering'     => 'Herinnering betaling cursus',
		'_ideal'           => 'Betaling cursus',
		'_ideal_betaald'   => 'Betaling cursus',
		'_lopend'          => 'Inschrijving lopende cursus',
		'_lopend_betalen'  => 'Betaling bedrag voor reeds gestarte cursus',
		'_restant'         => 'Betaling restant bedrag cursus',
		'_wijziging'       => 'Wijziging inschrijving cursus',
		'_wachtlijst'      => 'Plaatsing op wachtlijst cursus',
		'_ruimte'          => 'Er is een cursusplek vrijgekomen',
		'_naar_wachtlijst' => 'De cursus is vol, aanmelding verplaatst naar wachtlijst',
	];

	/**
	 * Of de inschrijving al bestond
	 *
	 * @var bool $ingeschreven Of er al eerder was ingeschreven.
	 */
	public bool $ingeschreven = false;

	/**
	 * De cursus
	 *
	 * @since 4.0.87
	 *
	 * @access public
	 * @var Cursus $cursus cursus object.
	 */
	public Cursus $cursus;

	/**
	 * Het actie object
	 *
	 * @var InschrijvingActie $actie De acties.
	 */
	public InschrijvingActie $actie;

	/**
	 * Constructor
	 *
	 * @since 4.0.87
	 *
	 * @param int        $cursus_id id van de cursus.
	 * @param int        $klant_id  wp user id van de cursist.
	 * @param array|null $load (optioneel) data waarmee het object geladen kan worden (ivm performance).
	 */
	public function __construct( int $cursus_id, int $klant_id, ?array $load = null ) {
		global $wpdb;
		$this->cursus   = new Cursus( $cursus_id );
		$this->klant_id = $klant_id;
		$this->data     = [
			'code'             => "C$cursus_id-$klant_id",
			'datum'            => time(),
			'technieken'       => [],
			'ingedeeld'        => 0,
			'geannuleerd'      => 0,
			'opmerking'        => '',
			'aantal'           => 1,
			'restant_email'    => 0,
			'herinner_email'   => 0,
			'wacht_datum'      => 0,
			'extra_cursisten'  => [],
			'hoofd_cursist_id' => 0,
			'maatwerkkosten'   => 0.0,
		];
		$this->actie    = new InschrijvingActie( $this );
		$this->betaling = new InschrijvingBetaling( $this );
		$inschrijving   = $load ?? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_inschrijvingen WHERE cursus_id = %d AND cursist_id = %d", $cursus_id, $klant_id ), ARRAY_A );
		if ( is_null( $inschrijving ) ) {
			$this->ingeschreven = false;
			return;
		}
		$this->data['datum']            = strtotime( $inschrijving['datum'] );
		$this->data['technieken']       = json_decode( $inschrijving['technieken'], true );
		$this->data['ingedeeld']        = boolval( $inschrijving['ingedeeld'] );
		$this->data['geannuleerd']      = boolval( $inschrijving['geannuleerd'] );
		$this->data['opmerking']        = htmlspecialchars_decode( $inschrijving['opmerking'] ?? '' );
		$this->data['aantal']           = intval( $inschrijving['aantal'] );
		$this->data['restant_email']    = boolval( $inschrijving['restant_email'] );
		$this->data['herinner_email']   = boolval( $inschrijving['herinner_email'] );
		$this->data['wacht_datum']      = strtotime( $inschrijving['wacht_datum'] );
		$this->data['extra_cursisten']  = json_decode( $inschrijving['extra_cursisten'], true );
		$this->data['hoofd_cursist_id'] = intval( $inschrijving['hoofd_cursist_id'] );
		$this->data['maatwerkkosten']   = floatval( $inschrijving['maatwerkkosten'] );
		$this->ingeschreven             = true;
	}

	/**
	 * Get attribuut van het object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut naam.
	 * @return mixed Attribuut waarde.
	 */
	public function __get( string $attribuut ) {
		return array_key_exists( $attribuut, $this->data ) ? $this->data[ $attribuut ] : null;
	}

	/**
	 * Set attribuut van het object.
	 *
	 * @since 4.0.87
	 *
	 * @param string $attribuut Attribuut naam.
	 * @param mixed  $waarde Attribuut waarde.
	 */
	public function __set( string $attribuut, mixed $waarde ) {
		$this->data[ $attribuut ] = $waarde;
	}

	/**
	 * Verwijder de inschrijving
	 */
	public function erase() : bool {
		global $wpdb;
		return boolval(
			$wpdb->delete(
				"{$wpdb->prefix}kleistad_inschrijvingen",
				[
					'cursus_id'  => $this->cursus->id,
					'cursist_id' => $this->klant_id,
				]
			)
		);
	}

	/**
	 * Geef de artikel naam.
	 *
	 * @return string
	 */
	public function geef_artikelnaam() : string {
		return $this->cursus->naam;
	}

	/**
	 * Bepaal of er een melding nodig is dat er later een restant bedrag betaald moet worden.
	 *
	 * @return string De melding.
	 */
	public function heeft_restant() : string {
		if ( ! $this->cursus->is_binnenkort() && 0 < $this->cursus->inschrijfkosten ) {
			return self::OPM_INSCHRIJVING;
		}
		return '';
	}

	/**
	 * Toont eventueel aantal medecursisten
	 *
	 * @return string Het aantal.
	 */
	public function toon_aantal() : string {
		$aantal = $this->aantal - count( $this->extra_cursisten );
		return ( 1 < $aantal ) ? " ($aantal)" : '';
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
	 * Verzenden van de inschrijving of indeling email.
	 *
	 * @since      4.0.87
	 *
	 * @param string $type    Inschrijving of indeling.
	 * @param string $factuur Een bij te sluiten factuur.
	 * @return bool succes of falen van verzending email.
	 */
	public function verzend_email( string $type, string $factuur = '' ) : bool {
		$emailer = new Email();
		$cursist = get_userdata( $this->klant_id );
		$slug    = "cursus$type";
		if ( 'inschrijving' === $type ) {
			$slug = $this->cursus->inschrijfslug;
		} elseif ( 'indeling' === $type ) {
			$slug = $this->cursus->indelingslug;
		}
		return $emailer->send(
			[
				'to'          => "$cursist->display_name <$cursist->user_email>",
				'subject'     => self::EMAIL_SUBJECT[ $type ],
				'slug'        => $slug,
				'attachments' => $factuur ?: [],
				'parameters'  =>
				[
					'voornaam'               => $cursist->first_name,
					'achternaam'             => $cursist->last_name,
					'cursus_naam'            => $this->cursus->naam,
					'cursus_docent'          => $this->cursus->docent_naam(),
					'cursus_start_datum'     => strftime( '%A %d-%m-%y', $this->cursus->start_datum ),
					'cursus_eind_datum'      => strftime( '%A %d-%m-%y', $this->cursus->eind_datum ),
					'cursus_start_tijd'      => strftime( '%H:%M', $this->cursus->start_tijd ),
					'cursus_eind_tijd'       => strftime( '%H:%M', $this->cursus->eind_tijd ),
					'cursus_technieken'      => implode( ', ', $this->technieken ),
					'cursus_code'            => $this->code,
					'cursus_restant_melding' => $this->heeft_restant(),
					'cursus_extra_cursisten' => $this->heeft_extra_cursisten(),
					'cursus_hoofd_cursist'   => $this->hoofd_cursist_id ? get_user_by( 'id', $this->hoofd_cursist_id )->display_name : '',
					'cursus_bedrag'          => number_format_i18n( $this->aantal * $this->cursus->bedrag(), 2 ),
					'cursus_restantbedrag'   => number_format_i18n( $this->restantbedrag(), 2 ),
					'cursus_aantal'          => $this->aantal,
					'cursus_opmerking'       => empty( $this->opmerking ) ? '' : "De volgende opmerking heb je doorgegeven: $this->opmerking",
					'cursus_link'            => $this->maak_betaal_link(),
					'cursus_ruimte_link'     => $this->maak_link(
						[
							'code'  => $this->code,
							'actie' => 'indelen_na_wachten',
						],
						'wachtlijst'
					),
					'cursus_uitschrijf_link' => $this->maak_link(
						[
							'code'  => $this->code,
							'actie' => 'stop_wachten',
						],
						'wachtlijst'
					),
				],
			]
		);
	}

	/**
	 * Sla de inschrijving op als user metadata in de database.
	 *
	 * @since 4.0.87
	 */
	public function save() {
		global $wpdb;
		$wpdb->replace(
			"{$wpdb->prefix}kleistad_inschrijvingen",
			[
				'cursus_id'        => $this->cursus->id,
				'cursist_id'       => $this->klant_id,
				'datum'            => date( 'Y-m-d H:i:s', $this->datum ),
				'technieken'       => wp_json_encode( $this->technieken ),
				'extra_cursisten'  => wp_json_encode( $this->extra_cursisten ),
				'hoofd_cursist_id' => $this->hoofd_cursist_id,
				'ingedeeld'        => intval( $this->ingedeeld ),
				'geannuleerd'      => intval( $this->geannuleerd ),
				'opmerking'        => $this->opmerking,
				'aantal'           => $this->aantal,
				'wacht_datum'      => date( 'Y-m-d H:i:s', $this->wacht_datum ),
				'restant_email'    => intval( $this->restant_email ),
				'herinner_email'   => intval( $this->herinner_email ),
				'maatwerkkosten'   => $this->maatwerkkosten,
			]
		);
	}

	/**
	 * Geef de status van het artikel als een tekst terug.
	 *
	 * @return string De status tekst.
	 */
	public function geef_statustekst() : string {
		return $this->geannuleerd ? 'geannuleerd' : ( ( $this->ingedeeld ? 'ingedeeld' : 'ingeschreven' ) );
	}

	/**
	 * De regels voor de factuur.
	 *
	 * @return Orderregels De regels of één regel.
	 */
	public function geef_factuurregels(): Orderregels {
		$orderregels = new Orderregels();
		if ( 0 < $this->maatwerkkosten ) {
			$orderregels->toevoegen( new Orderregel( "cursus: {$this->cursus->naam} (reeds gestart)", $this->aantal, $this->maatwerkkosten ) );
			return $orderregels;
		}
		if ( $this->cursus->is_binnenkort() ) { // Als de cursus binnenkort start dan is er geen onderscheid meer in de kosten, echter bij inschrijfgeld 1 ct dit afronden naar 0.
			$orderregels->toevoegen( new Orderregel( "cursus: {$this->cursus->naam}", $this->aantal, $this->cursus->inschrijfkosten + $this->cursus->cursuskosten ) );
			return $orderregels;
		}
		if ( 0 < $this->cursus->inschrijfkosten ) {
			$orderregels->toevoegen( new Orderregel( "inschrijfkosten cursus: {$this->cursus->naam}", $this->aantal, $this->cursus->inschrijfkosten ) );
		}
		$orderregels->toevoegen( new Orderregel( "cursus: {$this->cursus->naam}", $this->aantal, $this->cursus->cursuskosten ) );
		return $orderregels;
	}

	/**
	 * Geef de tekst en de link naar de aanmelden extra cursisten pagina
	 *
	 * @return string De melding.
	 */
	private function heeft_extra_cursisten() : string {
		if ( $this->aantal > 1 ) {
			$link   = $this->maak_link( [ 'code' => $this->code ], 'extra_cursisten' );
			$tekst  = sprintf(
				'Je hebt aangegeven dat er %s aan de cursus/workshop. Kleistad wil graag weten wie zodat we iedereen per email kunnen informeren over de zaken die de cursus/workshop aangaan. ',
				2 === $this->aantal ? 'een mededeelnemer is ' : $this->aantal - 1 . ' mededeelnemers zijn '
			);
			$tekst .= "Je kunt dit invoeren op de volgende $link.";
			return $tekst;
		}
		return '';
	}

	/**
	 * Bepaal het restantbedrag
	 *
	 * @return float
	 */
	private function restantbedrag(): float {
		$order = new Order( $this->geef_referentie() );
		return ( $order->id ) ? $order->te_betalen() : 0.0;
	}

}
