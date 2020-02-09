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
 * @property bool   ingedeeld
 * @property bool   geannuleerd
 * @property string opmerking
 * @property int    aantal
 * @property bool   restant_email
 * @property bool   herinner_email
 */
class Inschrijving extends Artikel {

	public const META_KEY          = 'kleistad_cursus';
	private const OPM_INSCHRIJVING = 'Een week voorafgaand de start datum van de cursus zal je een betaalinstructie ontvangen voor het restant bedrag.';
	private const EMAIL_SUBJECT    = [
		'inschrijving'    => 'Inschrijving cursus',
		'indeling'        => 'Indeling cursus',
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
	 * @access private
	 * @var object $cursus cursus object.
	 */
	private $cursus;

	/**
	 * De beginwaarden van een inschrijving
	 *
	 * @since 4.3.0
	 *
	 * @access private
	 * @var array $default_data de standaard waarden bij het aanmaken van een inschrijving.
	 */
	private $default_data = [
		'code'           => '',
		'datum'          => '',
		'technieken'     => [],
		'ingedeeld'      => 0,
		'geannuleerd'    => 0,
		'opmerking'      => '',
		'aantal'         => 1,
		'restant_email'  => 0,
		'herinner_email' => 0,
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
			return true;
		}
		return false;
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
	 * @param string $bericht      Het bericht bij succesvolle betaling.
	 * @return string|bool De redirect url ingeval van een ideal betaling of false als het niet lukt.
	 */
	public function ideal( $bericht ) {
		$deelnemers = ( 1 === $this->aantal ) ? '1 cursist' : $this->aantal . ' cursisten';
		if ( ! $this->ingedeeld && 0 < $this->cursus->inschrijfkosten ) {
			return $this->betalen->order(
				$this->klant_id,
				$this->referentie() . '-inschrijving',
				$this->aantal * $this->cursus->inschrijfkosten,
				'Kleistad cursus ' . $this->code . ' inschrijfkosten voor ' . $deelnemers,
				$bericht
			);
		} else {
			return $this->betalen->order(
				$this->klant_id,
				$this->referentie() . '-cursus',
				$this->aantal * $this->cursus->cursuskosten,
				'Kleistad cursus ' . $this->code . ' cursuskosten voor ' . $deelnemers,
				$bericht
			);
		}
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
		$emailer   = new \Kleistad\Email();
		$cursist   = get_userdata( $this->klant_id );
		$onderwerp = self::EMAIL_SUBJECT[ $type ];
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
				'subject'     => $onderwerp,
				'slug'        => $slug,
				'attachments' => $factuur,
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
					'cursus_kosten'          => number_format_i18n( $this->aantal * $this->cursus->cursuskosten, 2 ),
					'cursus_inschrijfkosten' => number_format_i18n( $this->aantal * $this->cursus->inschrijfkosten, 2 ),
					'cursus_aantal'          => $this->aantal,
					'cursus_opmerking'       => empty( $this->opmerking ) ? '' : "De volgende opmerking heb je doorgegeven: $this->opmerking",
					'cursus_link'            => $this->betaal_link(),
				],
			]
		);
	}

	/**
	 * Omdat een inschrijving een meta data object betreft kan het niet omgezet worden naar de laatste versie.
	 *
	 * @param array $data het te laden object.
	 */
	public function load( $data ) {
		$this->data = wp_parse_args( $data, $this->default_data );
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
		return ( $betaald >= ( $this->aantal * $this->cursus->inschrijfkosten - 0.01 ) );
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
		if ( ! $this->ingedeeld && $bedrag >= $this->cursus->inschrijfkosten ) {
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
		$meetdag = strtotime( '+7 days 00:00' );
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
			if ( $meetdag > $this->cursus->start_datum ) { // Als de cursus binnenkort start dan is er geen onderscheid meer in de kosten.
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
	 * Bepaal de inschrijfstatus a.d.h.v. het betaalde bedrag.
	 *
	 * @param float $bedrag Het betaaalde bedrag.
	 * @return bool Of de indelingsstatus gewijzigd is.
	 */
	private function betaalstatus( $bedrag ) {
		if ( ! $this->ingedeeld && ( $bedrag >= ( $this->cursus->inschrijfkosten - 0.01 ) ) ) {
			$this->ingedeeld = true;
			$this->save();
			return true;
		}
		return false;
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
				$this->ontvang_order( $order_id, $bedrag, $transactie_id );
				if ( $this->ingedeeld ) {
					/**
					 * Als de cursist al ingedeeld is volstaat een bedankje.
					 */
					if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting.
						$this->email( '_ideal_betaald' );
					}
				} else {
					/**
					 * De cursist krijgt de melding dat deze nu ingedeeld is.
					 */
					$this->email( 'indeling' );
				}
			} else {
				/**
				 * Er is nog geen order, dus dit betreft inschrijving vanuit het formulier.
				 */
				$this->email( 'indeling', $this->bestel_order( $bedrag, $this->cursus->start_datum, 'inschrijving' === $this->artikel_type ? self::OPM_INSCHRIJVING : '', $transactie_id ) );
			}
		}
	}

	/**
	 * Controleer of er betalingsverzoeken verzonden moeten worden.
	 *
	 * @since 6.1.0
	 */
	public static function dagelijks() {
		$inschrijvingen = self::all();
		$cursussen      = \Kleistad\Cursus::all();
		foreach ( $inschrijvingen as $cursist_id => $cursist_inschrijvingen ) {
			foreach ( $cursist_inschrijvingen as $cursus_id => $inschrijving ) {
				if (
					$inschrijving->restant_email ||
					$inschrijving->geannuleerd ||
					$cursussen[ $cursus_id ]->vervallen ||
					! $inschrijving->ingedeeld ||
					strtotime( '+7 days 0:00' ) < $cursussen[ $cursus_id ]->start_datum ||
					strtotime( 'today' ) > $cursussen[ $cursus_id ]->eind_datum
					) {
					continue;
				}
				$order = new \Kleistad\Order( \Kleistad\Order::zoek_order( $inschrijving->referentie() ) );
				if ( ! $order->gesloten ) {
					$inschrijving->artikel_type  = 'cursus';
					$inschrijving->restant_email = true;
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
						$arr[ $cursist->ID ][ $cursus_id ]->load( $inschrijving );
					}
				}
			}
		}
		return $arr;
	}
}
