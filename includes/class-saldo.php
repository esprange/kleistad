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

/**
 * Klasse voor het beheren van de stook saldo.
 *
 * @property float  bedrag
 * @property string reden
 * @property array  storting
 * @property float  prijs
 */
class Saldo extends Artikel {

	const META_KEY = 'kleistad_stooksaldo';

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
		'reden'    => '',
	];

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
		$this->betalen  = new \Kleistad\Betalen();
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
			default:
				$this->data['storting'][ $laatste_storting ][ $attribuut ] = $waarde;
		}
	}

	/**
	 * Geef de artikel naam.
	 *
	 * @return string
	 */
	public function artikel_naam() {
		return 'stooksaldo';
	}

	/**
	 * Betaal de bijstorting saldo met iDeal.
	 *
	 * @since      4.2.0
	 *
	 * @param  string $bericht Het bericht bij succesvolle betaling.
	 * @param  string $referentie De referentie van het artikel.
	 * @param  float  $openstaand Het bedrag dat openstaat.
	 * @return string|bool De redirect url ingeval van een ideal betaling of false als het niet lukt.
	 */
	public function ideal( $bericht, $referentie, $openstaand = 0 ) {
		return $this->betalen->order(
			$this->klant_id,
			$referentie,
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
	public function referentie() {
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
	public function email( $type, $factuur = '' ) {
		$emailer   = new \Kleistad\Email();
		$gebruiker = get_userdata( $this->klant_id );
		return $emailer->send(
			[
				'to'          => "$gebruiker->display_name <$gebruiker->user_email>",
				'subject'     => 'Bijstorting stooksaldo',
				'slug'        => 'saldo' . $type,
				'attachments' => $factuur,
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
			'reden' => '',
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
		if ( update_user_meta( $this->klant_id, self::META_KEY, $this->data ) && $huidig_saldo !== $this->bedrag ) {
			self::write_log( get_userdata( $this->klant_id )->display_name . ' nu: € ' . number_format_i18n( $huidig_saldo, 2 ) . ' naar: € ' . number_format_i18n( $this->bedrag, 2 ) . ' vanwege ' . $this->reden );
			return true;
		}
		return false;
	}

	/**
	 * Geef de status van het artikel als een tekst terug.
	 *
	 * @param  boolean $uitgebreid Uitgebreide tekst of korte tekst.
	 * @return string De status tekst.
	 */
	public function status( $uitgebreid = false ) {
		return 0 < $this->bedrag ? 'saldo' : '';
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
			if ( $order_id ) {
				/**
				 * Er bestaat al een order dus dit is een betaling o.b.v. een email link of per bank.
				 */
				$this->ontvang_order( $order_id, $bedrag, $transactie_id );
				if ( 'ideal' === $type && 0 < $bedrag ) { // Als bedrag < 0 dan was het een terugstorting.
					$this->email( '_ideal_betaald' );
				}
			} else {
				/**
				 * Een betaling vanuit het formulier
				 */
				$this->email( '_ideal', $this->bestel_order( $bedrag, strtotime( '+7 days  0:00' ), '', $transactie_id ) );
			}
		}
	}

	/**
	 * Verhoog het saldobedrag met het betaalde bedrag.
	 *
	 * @param float $bedrag Het betaalde bedrag.
	 */
	protected function betaalactie( $bedrag ) {
		if ( 0 < $bedrag ) {
			$this->reden   = 'betaling per bank';
			$this->bedrag += $bedrag;
			$this->save();
		}
	}

	/**
	 * De factuur regels.
	 *
	 * @return array
	 */
	protected function factuurregels() {
		return [
			array_merge(
				self::split_bedrag( $this->prijs ),
				[
					'artikel' => 'stooksaldo',
					'aantal'  => 1,
				]
			),
		];
	}

	/**
	 * Verwerk de reserveringen. Afhankelijk van de status melden dat er een afboeking gaat plaats vinden of de werkelijke afboeking uitvoeren.
	 * Verplaatst vanuit class reservering.
	 *
	 * @since 4.5.1
	 * @throws \Exception Als het saldo of de reservering niet opgeslagen kan worden.
	 */
	public static function dagelijks() {
		global $wpdb;
		$emailer       = new \Kleistad\Email();
		$reserveringen = \Kleistad\Reservering::all( true );
		$ovens         = \Kleistad\Oven::all();
		$options       = \Kleistad\Kleistad::get_options();

		foreach ( $reserveringen as $reservering ) {
			if ( $reservering->datum <= strtotime( '- ' . $options['termijn'] . ' days 00:00' ) ) {
				try {
					/**
					 * Het onderstaande wordt als een transactie uitgevoerd omdat zowel het saldo als de reservering in de database gemuteerd worden.
					 */
					if ( \Kleistad\Reservering::ONDERHOUD === $reservering->soortstook ) {
						$reservering->verwerkt = true;
						$reservering->save();
						continue;
					}
					$wpdb->query( 'START TRANSACTION' );
					$stoker = get_userdata( $reservering->gebruiker_id );
					foreach ( $reservering->verdeling as $stookdeel ) {
						if ( 0 === intval( $stookdeel['id'] ) ) {
							continue; // Volgende verdeling.
						}
						$medestoker         = get_userdata( $stookdeel['id'] );
						$bedrag             = $ovens[ $reservering->oven_id ]->stookkosten( $stookdeel['id'], $stookdeel['perc'] );
						$stookdeel['prijs'] = $bedrag;
						$reservering->prijs( $stookdeel['id'], $bedrag );
						if ( $bedrag < 0.01 ) {
							continue; // Volgende verdeling.
						}
						$saldo         = new \Kleistad\Saldo( $stookdeel['id'] );
						$saldo->bedrag = $saldo->bedrag - $bedrag;
						$saldo->reden  = 'stook op ' . date( 'd-m-Y', $reservering->datum ) . ' door ' . $stoker->display_name;
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
										'bedrag'     => number_format_i18n( $bedrag, 2 ),
										'saldo'      => number_format_i18n( $saldo->bedrag, 2 ),
										'stookdeel'  => $stookdeel['perc'],
										'stookdatum' => date( 'd-m-Y', $reservering->datum ),
										'stookoven'  => $ovens[ $reservering->oven_id ]->naam,
									],
								]
							);
						} else {
							throw new \Exception( 'stooksaldo van gebruiker ' . $medestoker->display_name . ' kon niet aangepast worden met kosten ' . $bedrag );
						}
					}
					$reservering->verwerkt = true;
					$result                = $reservering->save();
					if ( 0 === $result ) {
						throw new \Exception( 'reservering met id ' . $reservering->id . ' kon niet aangepast worden' );
					}
					$wpdb->query( 'COMMIT' );
				} catch ( \Exception $e ) {
					$wpdb->query( 'ROLLBACK' );
					error_log( 'stooksaldo verwerking: ' . $e->getMessage() ); // phpcs:ignore
				}
			} elseif ( ! $reservering->gemeld && $reservering->datum < strtotime( 'today' ) ) {
				if ( \Kleistad\Reservering::ONDERHOUD !== $reservering->soortstook ) {
					$bedrag     = $ovens[ $reservering->oven_id ]->stookkosten( $reservering->gebruiker_id, 100 );
					$stoker     = get_userdata( $reservering->gebruiker_id );
					$stookdelen = $reservering->verdeling;
					$tabel      = '<table><tr><td><strong>Naam</strong></td><td style=\"text-align:right;\"><strong>Percentage</strong></td></tr>';
					foreach ( $stookdelen as $stookdeel ) {
						if ( 0 === intval( $stookdeel['id'] ) ) {
							continue; // Volgende verdeling.
						}
						$medestoker = get_userdata( $stookdeel['id'] );
						$tabel     .= "<tr><td>{$medestoker->first_name} {$medestoker->last_name}</td><td style=\"text-align:right;\" >{$stookdeel['perc']} %</td></tr>";
					}
					$tabel .= '<tr><td colspan="2" style="text-align:center;" >verdeling op ' . current_time( 'd-m-Y H:i' ) . '</td></table>';

					$emailer->send(
						[
							'to'         => "$stoker->display_name <$stoker->user_email>",
							'subject'    => 'Kleistad oven gebruik op ' . date( 'd-m-Y', $reservering->datum ),
							'slug'       => 'stookmelding',
							'parameters' => [
								'voornaam'         => $stoker->first_name,
								'achternaam'       => $stoker->last_name,
								'bedrag'           => number_format_i18n( $bedrag, 2 ),
								'datum_verwerking' => date( 'd-m-Y', strtotime( '+' . $options['termijn'] . ' day', $reservering->datum ) ), // datum verwerking.
								'datum_deadline'   => date( 'd-m-Y', strtotime( '+' . $options['termijn'] - 1 . ' day', $reservering->datum ) ), // datum deadline.
								'verdeling'        => $tabel,
								'stookoven'        => $ovens[ $reservering->oven_id ]->naam,
							],
						]
					);
				}
				$reservering->gemeld = true;
				$reservering->save();
			}
		}
	}

	/**
	 * Functie om algemene teksten toe te voegen aan de log
	 *
	 * @since      4.0.87
	 *
	 * @param string $reden De te loggen tekst.
	 */
	public static function log( $reden ) {
		self::write_log( $reden );
	}

	/**
	 * Private functie welke de update van stooksaldo.log doet.
	 *
	 * @since      4.0.87
	 *
	 * @param string $tekst Toe te voegen tekst aan log.
	 */
	private static function write_log( $tekst ) {
		$upload_dir = wp_upload_dir();
		file_put_contents( $upload_dir['basedir'] . '/stooksaldo.log', date( 'c' ) . " : $tekst\n", FILE_APPEND); // phpcs:ignore
	}

}
