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
 * @property bool   i_betaald
 * @property bool   c_betaald
 * @property bool   ingedeeld
 * @property bool   geannuleerd
 * @property string opmerking
 * @property int    aantal
 * @property bool   restant_email
 * @property bool   herinner_email
 */
class Inschrijving extends Artikel {

	const META_KEY         = 'kleistad_cursus';
	const OPM_INSCHRIJVING = 'Een week voorafgaand de start datum van de cursus zal je een betaalinstructie ontvangen voor het restant bedrag.';

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
		'i_betaald'      => 0,
		'c_betaald'      => 0,
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
			case 'i_betaald':
			case 'c_betaald':
			case 'geannuleerd':
			case 'restant_email':
			case 'herinner_email':
				return boolval( $this->data[ $attribuut ] ?? false );
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
			case 'i_betaald':
			case 'c_betaald':
			case 'geannuleerd':
			case 'restant_email':
			case 'herinner_email':
				$this->data[ $attribuut ] = (int) $waarde;
				break;
			default:
				$this->data[ $attribuut ] = $waarde;
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
	public function betalen( $bericht ) {
		$deelnemers = ( 1 === $this->aantal ) ? '1 cursist' : $this->aantal . ' cursisten';
		if ( ! $this->i_betaald && 0 < $this->cursus->inschrijfkosten ) {
			return $this->betalen->order(
				$this->klant_id,
				__CLASS__ . '-' . $this->code . '-inschrijving',
				$this->aantal * $this->cursus->inschrijfkosten,
				'Kleistad cursus ' . $this->code . ' inschrijfkosten voor ' . $deelnemers,
				$bericht
			);
		} else {
			return $this->betalen->order(
				$this->klant_id,
				__CLASS__ . '-' . $this->code . '-cursus',
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
				$this->data['code']           = "C$cursus_id-$this->klant_id";
				$inschrijvingen[ $cursus_id ] = $this->data;
				unset( $inschrijvingen[ $this->cursus->id ] );
				update_user_meta( $this->klant_id, self::META_KEY, $inschrijvingen );
				$this->email( '_wijziging', $this->wijzig_order( \Kleistad\Order::zoek_order( $this->code ) ) );
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
		$onderwerp = ucfirst( $type ) . ' cursus';
		$slug      = "cursus$type";

		switch ( $type ) {
			case 'inschrijving':
				$slug = $this->cursus->inschrijfslug;
				break;
			case 'indeling':
				$slug = $this->cursus->indelingslug;
				break;
			case '_lopend':
				$onderwerp = 'Inschrijving lopende cursus';
				break;
			case '_wijziging':
				$onderwerp = 'Wijziging inschrijving cursus';
				break;
			case '_restant':
				$onderwerp = 'Betaling restant bedrag cursus';
				break;
			case '_herinnering':
				$onderwerp = 'Herinnering betaling cursus';
				break;
			case '_ideal':
				$onderwerp = 'Betaling cursus';
				break;
			default:
				$slug = '';
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
					'cursus_opmerking'       => ( '' !== $this->opmerking ) ? 'De volgende opmerking heb je doorgegeven: ' . $this->opmerking : '',
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
	 * Check of er een indeling moet plaatsvinden ivm betaling inschrijfgeld.
	 *
	 * @param float $bedrag Het betaalde bedrag.
	 */
	protected function betaalactie( $bedrag ) {
		if ( ! $this->i_betaald && $bedrag >= $this->cursus->inschrijfkosten ) {
			$this->i_betaald = true;
		}
		if ( ! $this->c_betaald && 0.1 > abs( $bedrag - ( $this->cursus->cursuskosten + $this->cursus->inschrijfkosten ) ) ) {
			$this->c_betaald = true;
		}
		if ( ! $this->ingedeeld && ( $this->i_betaald || $this->c_betaald ) ) {
			$this->ingedeeld = true;
			if ( strtotime( 'today' ) < $this->cursus->start_datum ) {
				// Alleen email versturen als de cursus nog niet gestart is.
				$this->email( 'indeling' );
			}
		}
		$this->save();
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
			if ( $meetdag <= $this->cursus->start_datum ) { // Als de cursus binnenkort start dan is er geen onderscheid meer in de kosten.
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
	 * @param array $parameters De parameters 0: cursus-id, 1: gebruiker-id, 2: startdatum, 3: type betaling.
	 * @param float $bedrag     Het betaalde bedrag, wordt hier niet gebruikt.
	 * @param bool  $betaald    Of er werkelijk betaald is.
	 */
	public static function callback( $parameters, $bedrag, $betaald ) {
		if ( $betaald ) {
			$inschrijving = new static( intval( $parameters[0] ), intval( $parameters[1] ) );
			$artikel_type = is_numeric( $parameters[2] ) ? $parameters[3] : $parameters[2]; // Voor oude cursuscode.
			switch ( $artikel_type ) {
				case 'inschrijving':
					$inschrijving->i_betaald = true;
					$inschrijving->ingedeeld = true;
					$inschrijving->email( 'indeling', $inschrijving->bestel_order( $bedrag, $artikel_type, self::OPM_INSCHRIJVING ) );
					$inschrijving->save();
					break;

				case 'cursus':
					$inschrijving->i_betaald = true;
					$inschrijving->c_betaald = true;
					$inschrijving->ingedeeld = true;
					if ( 0 < $inschrijving->cursus->inschrijfkosten ) {
						$inschrijving->ontvang_order( \Kleistad\Order::zoek_order( $inschrijving->code ), $bedrag );
						$inschrijving->email( '_ideal' );
					} else {
						$inschrijving->email( 'indeling', $inschrijving->bestel_order( $bedrag, $artikel_type, '' ) );
					}
					$inschrijving->save();
					break;

				default:
					break;
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
					$inschrijving->c_betaald ||
					$cursussen[ $cursus_id ]->vervallen ||
					! $inschrijving->ingedeeld ||
					strtotime( '+7 days' ) < $cursussen[ $cursus_id ]->start_datum ||
					strtotime( 'today' ) > $cursussen[ $cursus_id ]->eind_datum
					) {
					continue;
				}
				$inschrijving->artikel_type  = 'cursus';
				$inschrijving->restant_email = true;
				$inschrijving->save();
				$inschrijving->email( '_restant' );
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
