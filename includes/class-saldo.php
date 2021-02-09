<?php
/**
 * De definitie van de (stook) saldo class
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Exception;

/**
 * Klasse voor het beheren van de stook saldo.
 *
 * @property float  bedrag
 * @property array  storting
 * @property float  prijs
 */
class Saldo extends Artikel {

	public const DEFINITIE = [
		'prefix' => 'S',
		'naam'   => 'stooksaldo',
		'pcount' => 1,
	];
	public const META_KEY  = 'kleistad_stooksaldo';

	/**
	 * De beginwaarden van een dagdelenkaart.
	 *
	 * @access private
	 * @var array $default_data de standaard waarden bij het aanmaken van een dagdelenkaart.
	 */
	private $default_data = [
		'storting' => [
			[],
		],
		'bedrag'   => 0.0,
	];

	/**
	 * De reden waarvoor het saldo gewijzigd wordt. Is alleen voor de logging bedoeld.
	 *
	 * @access public
	 * @var string $reden De reden.
	 */
	public $reden;

	/**
	 * Het volgnummer van de dagdelenkaart.
	 *
	 * @access private
	 * @var int $volgnr Het volgnummer.
	 */
	private $volgnr;

	/**
	 * De constructor
	 *
	 * @since      4.0.87
	 *
	 * @param int $klant_id De gebruiker waarvoor het saldo wordt gemaakt.
	 */
	public function __construct( $klant_id ) {
		$this->klant_id = $klant_id;
		$this->betalen  = new Betalen();
		$saldo          = get_user_meta( $this->klant_id, self::META_KEY, true ) ?: $this->default_data;
		$this->data     = wp_parse_args( $saldo, $this->default_data );
		$this->volgnr   = count( $this->storting );
	}

	/**
	 * Getter magic functie
	 *
	 * @since      4.0.87
	 *
	 * @param string $attribuut Het attribuut waarvan de waarde wordt opgevraagd.
	 * @return mixed De waarde.
	 */
	public function __get( $attribuut ) {
		$laatste_storting = array_key_last( $this->data['storting'] );
		switch ( $attribuut ) {
			case 'storting':
				return $this->data[ $attribuut ];
			case 'bedrag':
				return (float) $this->data[ $attribuut ];
			case 'datum':
				return strtotime( $this->data['storting'][ $laatste_storting ][ $attribuut ] );
			case 'prijs':
				return (float) $this->data['storting'][ $laatste_storting ][ $attribuut ];
			case 'reden':
				return $this->reden;
			default:
				return $this->data['storting'][ $laatste_storting ][ $attribuut ];
		}
	}

	/**
	 * Setter magic functie
	 *
	 * @since      4.0.87
	 *
	 * @param string $attribuut Het attribuut waarvan de waarde wordt aangepast.
	 * @param mixed  $waarde De nieuwe waarde.
	 */
	public function __set( $attribuut, $waarde ) {
		$laatste_storting = array_key_last( $this->data['storting'] );
		switch ( $attribuut ) {
			case 'bedrag':
			case 'storting':
				$this->data[ $attribuut ] = round( $waarde, 2 );
				break;
			case 'datum':
				$this->data['storting'][ $laatste_storting ][ $attribuut ] = date( 'Y-m-d', $waarde );
				break;
			case 'reden':
				$this->reden = $waarde;
				break;
			default:
				$this->data['storting'][ $laatste_storting ][ $attribuut ] = $waarde;
		}
	}

	/**
	 * Betaal de bijstorting saldo met iDeal.
	 *
	 * @since      4.2.0
	 *
	 * @param  string $bericht Het bericht bij succesvolle betaling.
	 * @param  float  $openstaand Het bedrag dat openstaat.
	 * @return string|bool De redirect url ingeval van een ideal betaling of false als het niet lukt.
	 */
	public function doe_idealbetaling( string $bericht, float $openstaand = null ) {
		return $this->betalen->order(
			$this->klant_id,
			$this->geef_referentie(),
			$openstaand ?? $this->prijs,
			'Kleistad stooksaldo ' . $this->code,
			$bericht
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
	 * Verzenden van de saldo verhoging email.
	 *
	 * @since      4.0.87
	 *
	 * @param string $type    Direct betaald of melding van storting.
	 * @param string $factuur Een bij te sluiten factuur.
	 * @return boolean succes of falen van verzending email.
	 */
	public function verzend_email( $type, $factuur = '' ) {
		$emailer   = new Email();
		$gebruiker = get_userdata( $this->klant_id );
		return $emailer->send(
			[
				'to'          => "$gebruiker->display_name <$gebruiker->user_email>",
				'subject'     => 'Bijstorting stooksaldo',
				'slug'        => 'saldo' . $type,
				'attachments' => $factuur ?: [],
				'parameters'  => [
					'voornaam'   => $gebruiker->first_name,
					'achternaam' => $gebruiker->last_name,
					'bedrag'     => number_format_i18n( $this->prijs, 2 ),
					'saldo'      => number_format_i18n( $this->bedrag, 2 ),
					'saldo_link' => $this->betaal_link,
				],
			]
		);
	}

	/**
	 * Voeg een nieuw saldo toe.
	 *
	 * @param float $prijs Het toe te voegen saldo na betaling.
	 */
	public function nieuw( $prijs ) {
		$this->volgnr++;
		$datum                                   = strftime( '%y%m%d', strtotime( 'today' ) );
		$this->data['storting'][ $this->volgnr ] = [
			'code'  => "S$this->klant_id-$datum-$this->volgnr",
			'datum' => date( 'Y-m-d', strtotime( 'today' ) ),
			'prijs' => $prijs,
		];
		$this->save();
	}

	/**
	 * Bewaar het aangepaste saldo
	 *
	 * @since      4.0.87
	 *
	 * @return bool True als saldo is aangepast.
	 */
	public function save() {
		$saldo        = get_user_meta( $this->klant_id, self::META_KEY, true );
		$huidig_saldo = $saldo ? (float) $saldo['bedrag'] : 0.0;
		if ( update_user_meta( $this->klant_id, self::META_KEY, $this->data ) ) {
			if ( $huidig_saldo !== $this->bedrag ) {
				$tekst = get_userdata( $this->klant_id )->display_name . ' nu: ' . number_format_i18n( $huidig_saldo, 2 ) . ' naar: ' . number_format_i18n( $this->bedrag, 2 ) . ' vanwege ' . $this->reden;
				file_put_contents(  // phpcs:ignore
					wp_upload_dir()['basedir'] . '/stooksaldo.log',
					date( 'c' ) . " : $tekst\n",
					FILE_APPEND
				);
			}
			return true;
		}
		return false;
	}

	/**
	 * Geef de status van het artikel als een tekst terug.
	 *
	 * @return string De status tekst.
	 */
	public function geef_statustekst() : string {
		return 0 < $this->bedrag ? 'saldo' : '';
	}

	/**
	 * Verwijder het saldo
	 */
	public function erase() {
		delete_user_meta( $this->klant_id, self::META_KEY );
	}

	/**
	 * Verwerk een betaling. Wordt aangeroepen vanuit de betaal callback
	 *
	 * @since      4.2.0
	 *
	 * @param int    $order_id      De order_id, als die al bestaat.
	 * @param float  $bedrag        Het bedrag dat betaald is.
	 * @param bool   $betaald       Of er werkelijk betaald is.
	 * @param string $type          Type betaling, ideal , directdebit of bank.
	 * @param string $transactie_id De betaling id.
	 */
	public function verwerk_betaling( $order_id, $bedrag, $betaald, $type, $transactie_id = '' ) {
		if ( $betaald ) {
			$this->bedrag = round( $this->bedrag + $bedrag, 2 );
			$this->reden  = $bedrag > 0 ? 'storting' : 'stornering';
			$this->save();

			if ( $order_id ) {
				/**
				 * Er bestaat al een order dus dit is een betaling o.b.v. een email link of per bank.
				 */
				$this->ontvang_order( $order_id, $bedrag, $transactie_id );
				if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting.
					$this->verzend_email( '_ideal_betaald' );
				}
				return;
			}
			/**
			 * Een betaling vanuit het formulier
			 */
			$this->verzend_email( '_ideal', $this->bestel_order( $bedrag, strtotime( '+7 days  0:00' ), '', $transactie_id ) );
		}
	}

	/**
	 * De factuur regels.
	 *
	 * @return Orderregel
	 */
	protected function geef_factuurregels() {
		return new Orderregel( 'stooksaldo', 1, $this->prijs );
	}

	/**
	 * Verwerk de reserveringen. Afhankelijk van de status melden dat er een afboeking gaat plaats vinden of de werkelijke afboeking uitvoeren.
	 * Verplaatst vanuit class reservering.
	 *
	 * @since 4.5.1
	 * @throws Exception Als het saldo of de reservering niet opgeslagen kan worden.
	 */
	public static function doe_dagelijks() {
		$ovens         = new Ovens();
		$options       = opties();
		$verwerk_datum = strtotime( '- ' . $options['termijn'] . ' days 00:00' );
		foreach ( $ovens as $oven ) {
			$stoken = new Stoken( $oven->id, strtotime( '- 1 week' ), strtotime( 'today' ) );
			foreach ( $stoken as $stook ) {
				if ( ! $stook->is_gereserveerd() ) {
					continue;
				}
				if ( ! $stook->verwerkt && $stook->datum <= $verwerk_datum ) {
					self::verwerk_stook( $oven, $stook );
					continue;
				}
				if ( ! $stook->gemeld && $stook->datum < strtotime( 'today' ) ) {
					self::meld_stook( $oven, $stook );
				}
			}
		}
	}

	/**
	 * Verwerk een stook
	 *
	 * @param Oven  $oven          Een oven object.
	 * @param Stook $stook         Een stook object.
	 * @global object $wpdb        WordPress database.
	 * @throws Exception    Exceptie als transactie mislukt.
	 */
	private static function verwerk_stook( Oven $oven, Stook $stook ) {
		global $wpdb;
		$emailer = new Email();
		try {
			$stoker = get_userdata( $stook->hoofdstoker );
			$wpdb->query( 'START TRANSACTION' );
			if ( Stook::ONDERHOUD !== $stook->soort ) {
				foreach ( $stook->stookdelen as $stookdeel ) {
					$stookdeel->prijs = $oven->stookkosten( $stookdeel->medestoker, $stookdeel->percentage, $stook->temperatuur );
					$medestoker       = get_userdata( $stookdeel->medestoker );
					$saldo            = new Saldo( $stookdeel->medestoker );
					$saldo->bedrag    = $saldo->bedrag - $stookdeel->prijs;
					$saldo->reden     = 'stook op ' . date( 'd-m-Y', $stook->datum ) . ' door ' . $stoker->display_name;
					if ( $saldo->save() ) {
						$emailer->send(
							[
								'to'         => "$medestoker->display_name <$medestoker->user_email>",
								'subject'    => 'Kleistad kosten zijn verwerkt op het stooksaldo',
								'slug'       => 'stookkosten_verwerkt',
								'parameters' => [
									'voornaam'   => $medestoker->first_name,
									'achternaam' => $medestoker->last_name,
									'stoker'     => $stoker->display_name,
									'bedrag'     => number_format_i18n( $stookdeel->prijs, 2 ),
									'saldo'      => number_format_i18n( $saldo->bedrag, 2 ),
									'stookdeel'  => $stookdeel->percentage,
									'stookdatum' => date( 'd-m-Y', $stook->datum ),
									'stookoven'  => $oven->naam,
								],
							]
						);
						continue;
					}
					throw new Exception( "stooksaldo van gebruiker {$medestoker->display_name} kon niet aangepast worden met kosten {$stookdeel->prijs}" );
				}
			}
			$wpdb->query( 'COMMIT' );
			$stook->verwerkt = true;
			$stook->save();
		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			error_log( 'stooksaldo verwerking: ' . $e->getMessage() ); // phpcs:ignore
		}
	}

	/**
	 * Meld een stook
	 *
	 * @param Oven  $oven   Een oven object.
	 * @param Stook $stook  Een stook object.
	 */
	private static function meld_stook( Oven $oven, Stook $stook ) {
		$emailer = new Email();
		$options = opties();
		if ( Stook::ONDERHOUD !== $stook->soort ) {
			$stoker = get_userdata( $stook->hoofdstoker );
			$tabel  = '<table><tr><td><strong>Naam</strong></td><td style=\"text-align:right;\"><strong>Percentage</strong></td></tr>';
			foreach ( $stook->stookdelen as $stookdeel ) {
				if ( 0 === $stookdeel->medestoker ) {
					continue; // Volgende verdeling.
				}
				$medestoker = get_userdata( $stookdeel->medestoker );
				$tabel     .= "<tr><td>{$medestoker->first_name} {$medestoker->last_name}</td><td style=\"text-align:right;\" >{$stookdeel->percentage} %</td></tr>";
			}
			$tabel .= '<tr><td colspan="2" style="text-align:center;" >verdeling op ' . current_time( 'd-m-Y H:i' ) . '</td></table>';
			$emailer->send(
				[
					'to'         => "$stoker->display_name <$stoker->user_email>",
					'subject'    => 'Kleistad oven gebruik op ' . date( 'd-m-Y', $stook->datum ),
					'slug'       => 'stookmelding',
					'parameters' => [
						'voornaam'         => $stoker->first_name,
						'achternaam'       => $stoker->last_name,
						'bedrag'           => number_format_i18n( $oven->stookkosten( $stook->hoofdstoker, 100, $stook->temperatuur ), 2 ),
						'datum_verwerking' => date( 'd-m-Y', strtotime( '+' . $options['termijn'] . ' day', $stook->datum ) ), // datum verwerking.
						'datum_deadline'   => date( 'd-m-Y', strtotime( '+' . ( $options['termijn'] - 1 ) . ' day', $stook->datum ) ), // datum deadline.
						'verdeling'        => $tabel,
						'stookoven'        => $oven->naam,
					],
				]
			);
			$stook->gemeld = true;
			$stook->save();
		}
	}

}
