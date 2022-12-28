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
 * @property string code
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
	 * De datum dat de inschrijving plaatsvindt.
	 *
	 * @var int De inschrijfdatum.
	 */
	public int $datum;

	/**
	 * Wachtlijst datum.
	 *
	 * @var int Wachtdatum, 0 als er niet gewacht wordt.
	 */
	public int $wacht_datum = 0;

	/**
	 * De technieken die gekozen zijn.
	 *
	 * @var array De gekozen technieken.
	 */
	public array $technieken = [];

	/**
	 * Opmerking.
	 *
	 * @var string De opmerking.
	 */
	public string $opmerking = '';

	/**
	 * Meervoudige inschrijving.
	 *
	 * @var int Aantal cursisten.
	 */
	public int $aantal = 1;

	/**
	 * De extra cursisten
	 *
	 * @var array Id's van de extra cursisten.
	 */
	public array $extra_cursisten = [];

	/**
	 * Als het een extra cursist is, de hoofdcursist.
	 *
	 * @var int Id van de hoofdcursist.
	 */
	public int $hoofd_cursist_id = 0;

	/**
	 * Indeling status.
	 *
	 * @var bool True als ingedeeld.
	 */
	public bool $ingedeeld = false;

	/**
	 * Of de inschrijving al bestond
	 *
	 * @var bool True als eerder was ingeschreven.
	 */
	public bool $ingeschreven = false;

	/**
	 * Annulerings status.
	 *
	 * @var bool True als geannuleerd.
	 */
	public bool $geannuleerd = false;

	/**
	 * Status of restant email verstuurd is.
	 *
	 * @var bool True als email verstuurd.
	 */
	public bool $restant_email = false;

	/**
	 * Status of aanmaning verstuurd is.
	 *
	 * @var bool True als aanmaning email verstuurd.
	 */
	public bool $herinner_email = false;

	/**
	 * Maatwerk bij te late inschrijving.
	 *
	 * @var float De maatwerkkosten
	 */
	public float $maatwerkkosten = 0.0;

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
		$this->code     = self::DEFINITIE['prefix'] . "$cursus_id-$klant_id";
		$this->datum    = time();
		$this->actie    = new InschrijvingActie( $this );
		$this->betaling = new InschrijvingBetaling( $this );
		$inschrijving   = $load ?? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}kleistad_inschrijvingen WHERE cursus_id = %d AND cursist_id = %d", $cursus_id, $klant_id ), ARRAY_A );
		if ( is_null( $inschrijving ) ) {
			return;
		}
		$this->datum            = strtotime( $inschrijving['datum'] );
		$this->technieken       = json_decode( $inschrijving['technieken'], true ) ?: [];
		$this->ingedeeld        = boolval( $inschrijving['ingedeeld'] );
		$this->geannuleerd      = boolval( $inschrijving['geannuleerd'] );
		$this->opmerking        = htmlspecialchars_decode( $inschrijving['opmerking'] );
		$this->aantal           = intval( $inschrijving['aantal'] );
		$this->restant_email    = boolval( $inschrijving['restant_email'] );
		$this->herinner_email   = boolval( $inschrijving['herinner_email'] );
		$this->wacht_datum      = strtotime( $inschrijving['wacht_datum'] );
		$this->extra_cursisten  = json_decode( $inschrijving['extra_cursisten'], true ) ?: [];
		$this->hoofd_cursist_id = intval( $inschrijving['hoofd_cursist_id'] );
		$this->maatwerkkosten   = floatval( $inschrijving['maatwerkkosten'] );
		$this->ingeschreven     = true;
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
	public function get_artikelnaam() : string {
		return $this->cursus->naam;
	}

	/**
	 * Bepaal of er een melding nodig is dat er later een restant bedrag betaald moet worden.
	 *
	 * @return string De melding.
	 */
	public function get_restant_melding() : string {
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
	public function get_referentie() : string {
		return "$this->code-" . date( 'Ymd', $this->datum );
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
					'cursus_docent'          => $this->cursus->get_docent_naam(),
					'cursus_start_datum'     => wp_date( 'l d-m-y', $this->cursus->start_datum ),
					'cursus_eind_datum'      => wp_date( 'l d-m-y', $this->cursus->eind_datum ),
					'cursus_start_tijd'      => date( 'H:i', $this->cursus->start_tijd ), // Geen timezone conversie.
					'cursus_eind_tijd'       => date( 'H:i', $this->cursus->eind_tijd ), // Geen timezone conversie.
					'cursus_technieken'      => implode( ', ', $this->technieken ),
					'cursus_code'            => $this->code,
					'cursus_restant_melding' => $this->get_restant_melding(),
					'cursus_extra_cursisten' => $this->heeft_extra_cursisten(),
					'cursus_hoofd_cursist'   => $this->hoofd_cursist_id ? get_user_by( 'id', $this->hoofd_cursist_id )->display_name : '',
					'cursus_bedrag'          => number_format_i18n( $this->aantal * $this->cursus->get_bedrag(), 2 ),
					'cursus_restantbedrag'   => number_format_i18n( $this->restantbedrag(), 2 ),
					'cursus_aantal'          => $this->aantal,
					'cursus_opmerking'       => empty( $this->opmerking ) ? '' : "De volgende opmerking heb je doorgegeven: $this->opmerking",
					'cursus_link'            => $this->get_betaal_link(),
					'cursus_ruimte_link'     => $this->get_link(
						[
							'code'  => $this->code,
							'actie' => 'indelen_na_wachten',
						],
						'wachtlijst'
					),
					'cursus_uitschrijf_link' => $this->get_link(
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
				'datum'            => wp_date( 'Y-m-d H:i:s', $this->datum ),
				'technieken'       => wp_json_encode( $this->technieken ),
				'extra_cursisten'  => wp_json_encode( $this->extra_cursisten ),
				'hoofd_cursist_id' => $this->hoofd_cursist_id,
				'ingedeeld'        => intval( $this->ingedeeld ),
				'geannuleerd'      => intval( $this->geannuleerd ),
				'opmerking'        => $this->opmerking,
				'aantal'           => $this->aantal,
				'wacht_datum'      => wp_date( 'Y-m-d H:i:s', $this->wacht_datum ),
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
	public function get_statustekst() : string {
		return $this->geannuleerd ? 'geannuleerd' : ( ( $this->ingedeeld ? 'ingedeeld' : 'ingeschreven' ) );
	}

	/**
	 * Geef de verval datum
	 *
	 * @return int
	 */
	public function get_verval_datum(): int {
		if ( ! $this->maatwerkkosten ) {
			return $this->cursus->start_datum;
		}
		return parent::get_verval_datum();
	}

	/**
	 * De regels voor de factuur.
	 *
	 * @return Orderregels De regels of één regel.
	 */
	public function get_factuurregels(): Orderregels {
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
			$link   = $this->get_link( [ 'code' => $this->code ], 'extra_cursisten' );
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
		$order = new Order( $this->get_referentie() );
		return ( $order->id ) ? $order->get_te_betalen() : 0.0;
	}

}
