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
 * @property bool   restant_email
 * @property bool   herinner_email
 */
class Inschrijving extends Artikel {

	public const META_KEY         = 'kleistad_cursus';
	public const OPM_INSCHRIJVING = 'Een week voorafgaand de start datum van de cursus zal je een betaalinstructie ontvangen voor het restant bedrag.';
	private const EMAIL_SUBJECT   = [
		'inschrijving'    => 'Inschrijving cursus',
		'indeling'        => 'Indeling cursus',
		'_extra'          => 'Welkom cursus',
		'_herinnering'    => 'Herinnering betaling cursus',
		'_ideal'          => 'Betaling cursus',
		'_ideal_betaald'  => 'Betaling cursus',
		'_lopend'         => 'Inschrijving lopende cursus',
		'_lopend_betalen' => 'Betaling bedrag voor reeds gestarte cursus',
		'_restant'        => 'Betaling restant bedrag cursus',
		'_wijziging'      => 'Wijziging inschrijving cursus',
	];

	/**
	 * De kosten van een lopende cursus
	 *
	 * @var float $lopende_cursus De kosten.
	 */
	public $lopende_cursus = 0;

	/**
	 * De cursus
	 *
	 * @since 4.0.87
	 *
	 * @access public
	 * @var object $cursus cursus object.
	 */
	public $cursus;

	/**
	 * De beginwaarden van een inschrijving
	 *
	 * @since 4.3.0
	 *
	 * @access private
	 * @var array $default_data de standaard waarden bij het aanmaken van een inschrijving.
	 */
	private $default_data = [
		'code'             => '',
		'datum'            => '',
		'technieken'       => [],
		'ingedeeld'        => 0,
		'geannuleerd'      => 0,
		'opmerking'        => '',
		'aantal'           => 1,
		'restant_email'    => 0,
		'herinner_email'   => 0,
		'extra_cursisten'  => [],
		'hoofd_cursist_id' => 0,
	];

	/**
	 * Constructor
	 *
	 * @since 4.0.87
	 *
	 * @param int $cursus_id id van de cursus.
	 * @param int $klant_id wp user id van de cursist.
	 */
	public function __construct( $cursus_id, $klant_id ) {
		$this->cursus                = new \Kleistad\Cursus( $cursus_id );
		$this->klant_id              = $klant_id;
		$this->betalen               = new \Kleistad\Betalen();
		$this->default_data['code']  = "C$cursus_id-$klant_id";
		$this->default_data['datum'] = date( 'Y-m-d' );

		$inschrijvingen = get_user_meta( $this->klant_id, self::META_KEY, true );
		if ( is_array( $inschrijvingen ) && ( isset( $inschrijvingen[ $cursus_id ] ) ) ) {
			$this->data = wp_parse_args( $inschrijvingen[ $cursus_id ], $this->default_data );
		} else {
			$this->data = $this->default_data;
		}
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
			case 'technieken':
			case 'extra_cursisten':
				return ( is_array( $this->data[ $attribuut ] ) ) ? $this->data[ $attribuut ] : [];
			case 'datum':
				return strtotime( $this->data[ $attribuut ] );
			case 'geannuleerd':
			case 'restant_email':
			case 'herinner_email':
				return boolval( $this->data[ $attribuut ] ?? false );
			default:
				return ( is_string( $this->data[ $attribuut ] ) ) ? htmlspecialchars_decode( $this->data[ $attribuut ] ) : $this->data[ $attribuut ];
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
			case 'technieken':
			case 'extra_cursisten':
				$this->data[ $attribuut ] = is_array( $waarde ) ? $waarde : [];
				break;
			case 'datum':
				$this->data[ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			default:
				$this->data[ $attribuut ] = is_string( $waarde ) ? trim( $waarde ) : ( is_bool( $waarde ) ? (int) $waarde : $waarde );
		}
	}

	/**
	 * Verwijder de inschrijving
	 */
	public function erase() {
		$inschrijvingen = get_user_meta( $this->klant_id, self::META_KEY, true );
		unset( $inschrijvingen[ $this->cursus->id ] );
		if ( empty( $inschrijvingen ) ) {
			delete_user_meta( $this->klant_id, self::META_KEY );
		} else {
			update_user_meta( $this->klant_id, self::META_KEY, $inschrijvingen );
		}
	}

	/**
	 * Zeg de gemaakte afspraak voor de cursus af.
	 *
	 * @since 6.1.0
	 *
	 * @return bool
	 */
	public function afzeggen() {
		if ( ! $this->geannuleerd ) {
			$this->geannuleerd = true;
			$this->save();
			foreach ( $this->extra_cursisten as $extra_cursist_id ) {
				$extra_inschrijving              = new \Kleistad\Inschrijving( $this->cursus->id, $extra_cursist_id );
				$extra_inschrijving->geannuleerd = true;
				$extra_inschrijving->save();
			}
			return true;
		}
		return false;
	}

	/**
	 * Stuur de herinnerings email.
	 *
	 * @return int Aantal emails verstuurd.
	 */
	public function herinnering() {
		if ( 0 === $this->aantal ) {
			return 0;
		}
		$order = new \Kleistad\Order( $this->referentie() );
		if ( $order->gesloten || $this->regeling_betaald( $order->betaald ) || $this->herinner_email ) {
			/**
			 * Als de cursist al betaald heeft of via deelbetaling de kosten voldoet en een eerste deel betaald heeft, geen actie.
			 * En uiteraard sturen maar éénmaal de standaard herinnering.
			 */
			return 0;
		}
		$this->artikel_type   = 'cursus';
		$this->herinner_email = true;
		$this->maak_link( $order->id );
		$this->email( '_herinnering' );
		$this->save();
		return 1;
	}

	/**
	 * Geef de artikel naam.
	 *
	 * @return string
	 */
	public function artikel_naam() {
		return $this->cursus->naam;
	}

	/**
	 * Betaal de inschrijving met iDeal.
	 *
	 * @since        4.2.0
	 *
	 * @param  string $bericht    Het bericht bij succesvolle betaling.
	 * @param  string $referentie De referentie van het artikel.
	 * @param  float  $openstaand Het bedrag dat openstaat.
	 * @return string|bool De redirect url ingeval van een ideal betaling of false als het niet lukt.
	 */
	public function ideal( $bericht, $referentie, $openstaand = null ) {
		$deelnemers = ( 1 === $this->aantal ) ? '1 cursist' : $this->aantal . ' cursisten';
		$vermelding = ( $openstaand || ! $this->heeft_restant() ) ? 'cursus' : 'inschrijf';
		return $this->betalen->order(
			$this->klant_id,
			$referentie,
			$openstaand ?? $this->aantal * $this->cursus->bedrag(),
			"Kleistad cursus {$this->code} {$vermelding}kosten voor $deelnemers",
			$bericht
		);
	}

	/**
	 * Bepaal of er een melding nodig is dat er later een restant bedrag betaald moet worden.
	 *
	 * @return string De melding.
	 */
	public function heeft_restant() {
		if ( ! $this->cursus->is_binnenkort() && 0 < $this->cursus->inschrijfkosten ) {
			return self::OPM_INSCHRIJVING;
		}
		return '';
	}

	/**
	 * Geef de tekst en de link naar de aanmelden extra cursisten pagina
	 *
	 * @return string De melding.
	 */
	public function heeft_extra_cursisten() {
		if ( $this->aantal > 1 ) {
			$url = add_query_arg(
				[
					'code' => $this->code,
					'hsh'  => $this->controle(),
				],
				home_url( '/kleistad-extra_cursisten' )
			);
			if ( 2 === $this->aantal ) {
				$tekst = 'Je hebt aangegeven dat er 1 mededeelnemer is aan de cursus/workshop. Kleistad wil graag weten wie dit is zodat we hem/haar per email kunnen informeren over de zaken die de cursus/workshop aangaan. ';
			} else {
				$tekst = 'Je hebt aangegeven dat er ' . ( $this->aantal - 1 ) . ' mededeelnemers zijn aan de cursus/workshop. Kleistad wil graag weten wie dit zijn zodat we iedereen per email kunnen informeren over de zaken die de cursus/workshop aangaan. ';
			}
			$tekst .= "Je kunt dit invoeren op de volgende <a href=\"$url\" >Kleistad pagina</a>.";
			return $tekst;
		}
		return '';
	}

	/**
	 * Toont eventueel aantal medecursisten
	 *
	 * @return string Het aantal.
	 */
	public function toon_aantal() {
		$aantal = $this->aantal - count( $this->extra_cursisten );
		return ( 1 < $aantal ) ? " ($aantal)" : '';
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
	 * Corrigeer de inschrijving naar nieuwe cursus.
	 *
	 * @since 4.5.0
	 *
	 * @param int $cursus_id nieuw cursus_id.
	 */
	public function correct( $cursus_id ) {
		$bestaande_inschrijvingen = get_user_meta( $this->klant_id, self::META_KEY, true );
		if ( is_array( $bestaande_inschrijvingen ) ) {
			$inschrijvingen = $bestaande_inschrijvingen;
			if ( ! array_key_exists( $cursus_id, $inschrijvingen ) ) {
				$order_id                     = \Kleistad\Order::zoek_order( $this->referentie() );
				$this->code                   = "C$cursus_id-$this->klant_id";
				$inschrijvingen[ $cursus_id ] = $this->data;
				unset( $inschrijvingen[ $this->cursus->id ] );
				update_user_meta( $this->klant_id, self::META_KEY, $inschrijvingen );
				$this->cursus = new \Kleistad\Cursus( $cursus_id );
				$factuur      = $this->wijzig_order( $order_id );
				if ( false === $factuur ) {
					return false;
				}
				$this->email( '_wijziging', $factuur );
				return true;
			}
		}
		return false; // zou niet mogen.
	}

	/**
	 * Verzenden van de inschrijving of indeling email.
	 *
	 * @since      4.0.87
	 *
	 * @param string $type    Inschrijving of indeling.
	 * @param string $factuur Een bij te sluiten factuur.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type, $factuur = '' ) {
		$emailer = new \Kleistad\Email();
		$cursist = get_userdata( $this->klant_id );
		if ( 'inschrijving' === $type ) {
			$slug = $this->cursus->inschrijfslug;
		} elseif ( 'indeling' === $type ) {
			$slug = $this->cursus->indelingslug;
		} else {
			$slug = "cursus$type";
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
					'cursus_link'            => $this->betaal_link,
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
		$bestaande_inschrijvingen = get_user_meta( $this->klant_id, self::META_KEY, true );
		if ( is_array( $bestaande_inschrijvingen ) ) {
			$inschrijvingen = $bestaande_inschrijvingen;
		} else {
			$inschrijvingen = [];
		}
		$inschrijvingen[ $this->cursus->id ] = $this->data;
		update_user_meta( $this->klant_id, self::META_KEY, $inschrijvingen );
	}

	/**
	 * Geef de status van het artikel als een tekst terug.
	 *
	 * @param  boolean $uitgebreid Uitgebreide tekst of korte tekst.
	 * @return string De status tekst.
	 */
	public function status( $uitgebreid = false ) {
		return $this->geannuleerd ? 'geannuleerd' : ( ( $this->ingedeeld ? 'ingedeeld' : 'ingeschreven' ) );
	}

	/**
	 * Controleer of het inschrijfgeld betaald is.
	 *
	 * @param float $betaald Het betaalde bedrag.
	 * @return bool
	 */
	public function inschrijving_betaald( $betaald ) {
		if ( 0 < $this->cursus->inschrijfkosten ) {
			return $betaald >= round( $this->aantal * $this->cursus->inschrijfkosten, 2 );
		} else {
			return $betaald >= round( $this->aantal * $this->cursus->cursuskosten, 2 );
		}
	}

	/**
	 * Controleer of er sprake is van een regeling betaald is.
	 *
	 * @param float $betaald Het betaalde bedrag.
	 * @return bool
	 */
	public function regeling_betaald( $betaald ) {
		return ( $betaald > ( $this->aantal * $this->cursus->inschrijfkosten + 1 ) );
	}

	/**
	 * Check of er een indeling moet plaatsvinden ivm betaling inschrijfgeld.
	 *
	 * @param float $bedrag Het totaal betaalde bedrag.
	 */
	protected function betaalactie( $bedrag ) {
		if ( ! $this->ingedeeld && $this->inschrijving_betaald( $bedrag ) ) {
			$this->ingedeeld = true;
			$this->save();
		}
	}

	/**
	 * De regels voor de factuur.
	 *
	 * @return array De regels.
	 */
	protected function factuurregels() {
		if ( 0 < $this->lopende_cursus ) {
			return [
				array_merge(
					self::split_bedrag( $this->lopende_cursus ),
					[
						'artikel' => "cursus: {$this->cursus->naam} (reeds gestart)",
						'aantal'  => $this->aantal,
					]
				),
			];
		} else {
			if ( $this->cursus->is_binnenkort() ) { // Als de cursus binnenkort start dan is er geen onderscheid meer in de kosten, echter bij inschrijfgeld 1 ct dit afronden naar 0.
				return [
					array_merge(
						self::split_bedrag( $this->cursus->inschrijfkosten + $this->cursus->cursuskosten ),
						[
							'artikel' => "cursus: {$this->cursus->naam}",
							'aantal'  => $this->aantal,
						]
					),
				];
			} else {
				$regels = [];
				if ( 0 < $this->cursus->inschrijfkosten ) {
					$regels[] = array_merge(
						self::split_bedrag( $this->cursus->inschrijfkosten ),
						[
							'artikel' => "inschrijfkosten cursus: {$this->cursus->naam}",
							'aantal'  => $this->aantal,
						]
					);
				}
				$regels[] = array_merge(
					self::split_bedrag( $this->cursus->cursuskosten ),
					[
						'artikel' => "cursus: {$this->cursus->naam}",
						'aantal'  => $this->aantal,
					]
				);
				return $regels;
			}
		}
	}

	/**
	 * Verwerk een betaling. Aangeroepen vanuit de betaal callback.
	 *
	 * @since        4.2.0
	 *
	 * @param int    $order_id      De order id, als deze bestaat.
	 * @param float  $bedrag        Het betaalde bedrag, wordt hier niet gebruikt.
	 * @param bool   $betaald       Of er werkelijk betaald is.
	 * @param string $type          Type betaling, ideal , directdebit of bank.
	 * @param string $transactie_id De betaling id.
	 */
	public function verwerk_betaling( $order_id, $bedrag, $betaald, $type, $transactie_id = '' ) {
		if ( $betaald ) {
			if ( $order_id ) {
				/**
				 * Er is al een order, dus er is betaling vanuit een mail link of er is al inschrijfgeld betaald.
				 */
				if ( $this->ingedeeld ) {
					/**
					 * Als de cursist al ingedeeld is volstaat een bedankje.
					 */
					$this->ontvang_order( $order_id, $bedrag, $transactie_id );
					if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting.
						$this->email( '_ideal_betaald' );
					}
				} else {
					/**
					 * De cursist krijgt de melding dat deze nu ingedeeld is.
					 */
					$this->ontvang_order( $order_id, $bedrag, $transactie_id );
					$this->email( 'indeling' );
				}
			} else {
				/**
				 * Er is nog geen order, dus dit betreft inschrijving vanuit het formulier.
				 */
				$this->email( 'indeling', $this->bestel_order( $bedrag, $this->cursus->start_datum, $this->heeft_restant(), $transactie_id ) );
			}
		}
	}

	/**
	 * Bepaal het restantbedrag
	 *
	 * @return float
	 */
	private function restantbedrag() {
		$order = new \Kleistad\Order( $this->referentie() );
		return $order->te_betalen();
	}

	/**
	 * Controleer of er betalingsverzoeken verzonden moeten worden.
	 *
	 * @since 6.1.0
	 */
	public static function dagelijks() {
		$inschrijvingen = self::all();
		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			foreach ( $cursist_inschrijvingen as $cursus_id => $inschrijving ) {
				if (
					0 === $inschrijving->aantal ||
					$inschrijving->restant_email ||
					$inschrijving->geannuleerd ||
					$inschrijving->cursus->vervallen ||
					! $inschrijving->ingedeeld ||
					! $inschrijving->cursus->is_binnenkort() ||
					strtotime( 'today' ) > $inschrijving->cursus->eind_datum
					) {
					continue;
				}
				$order = new \Kleistad\Order( $inschrijving->referentie() );
				if ( ! $order->gesloten ) {
					$inschrijving->artikel_type  = 'cursus';
					$inschrijving->restant_email = true;
					$inschrijving->maak_link( $order->id );
					$inschrijving->save();
					$inschrijving->email( '_restant' );
				}
			}
		}
	}

	/**
	 * Return inschrijvingen.
	 *
	 * @return array inschrijvingen.
	 */
	public static function all() {
		static $arr = null;
		if ( is_null( $arr ) ) {
			$arr       = [];
			$cursisten = get_users(
				[
					'meta_key' => self::META_KEY,
					'fields'   => [ 'ID' ],
				]
			);

			foreach ( $cursisten as $cursist ) {
				$inschrijvingen = get_user_meta( $cursist->ID, self::META_KEY, true );
				if ( is_array( $inschrijvingen ) ) {
					krsort( $inschrijvingen );
					foreach ( $inschrijvingen as $cursus_id => $inschrijving ) {
						$arr[ $cursist->ID ][ $cursus_id ] = new \Kleistad\Inschrijving( $cursus_id, $cursist->ID );
					}
				}
			}
		}
		return $arr;
	}
}
