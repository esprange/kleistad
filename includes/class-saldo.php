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
 * @property float bedrag
 */
class Saldo {

	const META_KEY = 'stooksaldo';

	/**
	 * De attributen van het saldo
	 *
	 * @since      4.0.87
	 *
	 * @var array De attributen van het saldo.
	 */
	private $data;

	/**
	 * De gebruiker identificatie
	 *
	 * @since      4.0.87
	 *
	 * @var int Het gebruiker_id.
	 */
	private $gebruiker_id;

	/**
	 * Email object
	 *
	 * @var object Het emailer object
	 */
	private $emailer;

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

	/**
	 * Private functie om de huidige saldo stand op te vragen
	 *
	 * @since      4.0.87
	 *
	 * @return float De huidige saldo stand.
	 */
	private function huidig_saldo() {
		$huidig_saldo = get_user_meta( $this->gebruiker_id, self::META_KEY, true );
		return ( '' === $huidig_saldo ) ? 0.0 : (float) $huidig_saldo;
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
	 * De constructor
	 *
	 * @since      4.0.87
	 *
	 * @param int $gebruiker_id De gebruiker waarvoor het saldo wordt gemaakt.
	 */
	public function __construct( $gebruiker_id ) {
		$this->emailer        = new \Kleistad\Email();
		$this->gebruiker_id   = $gebruiker_id;
		$this->data['bedrag'] = $this->huidig_saldo();
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
		$this->data[ $attribuut ] = $waarde;
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
		return $this->data[ $attribuut ];
	}

	/**
	 * Bewaar het aangepaste saldo
	 *
	 * @since      4.0.87
	 *
	 * @param string $reden De reden waarom het saldo wordt aangepast.
	 * @return bool True als saldo is aangepast.
	 */
	public function save( $reden ) {
		$huidig_saldo = $this->huidig_saldo();
		if ( update_user_meta( $this->gebruiker_id, self::META_KEY, $this->bedrag ) ) {
			$gebruiker = get_userdata( $this->gebruiker_id );
			self::write_log( $gebruiker->display_name . ' nu: € ' . number_format_i18n( $huidig_saldo, 2 ) . ' naar: € ' . number_format_i18n( $this->bedrag, 2 ) . ' vanwege ' . $reden );
			return true;
		}
		return false;
	}

	/**
	 * Betaal de bijstorting saldo met iDeal.
	 *
	 * @since      4.2.0
	 *
	 * @param string $bericht Het bericht bij succesvolle betaling.
	 * @param float  $bedrag  Het te betalen bedrag.
	 */
	public function betalen( $bericht, $bedrag ) {
		$betaling = new \Kleistad\Betalen();
		$code     = "S$this->gebruiker_id-" . strftime( '%y%m%d' );
		$betaling->order(
			$this->gebruiker_id,
			__CLASS__ . '-' . $code,
			$bedrag,
			'Kleistad stooksaldo ' . $code,
			$bericht
		);
	}

	/**
	 * Verzenden van de saldo verhoging email.
	 *
	 * @since      4.0.87
	 *
	 * @param string $type   direct betaald of melding van storting.
	 * @param float  $bedrag het saldo dat toegevoegd is.
	 * @return boolean succes of falen van verzending email.
	 */
	public function email( $type, $bedrag ) {
		$gebruiker = get_userdata( $this->gebruiker_id );
		return $this->emailer->send(
			[
				'to'         => "$gebruiker->display_name <$gebruiker->user_email>",
				'subject'    => 'Bijstorting stooksaldo',
				'slug'       => 'kleistad_email_saldo_wijziging' . $type,
				'parameters' => [
					'voornaam'   => $gebruiker->first_name,
					'achternaam' => $gebruiker->last_name,
					'bedrag'     => number_format_i18n( $bedrag, 2 ),
					'saldo'      => number_format_i18n( $this->bedrag, 2 ),
				],
			]
		);
	}

	/**
	 * Verwerk een betaling. Wordt aangeroepen vanuit de betaal callback
	 *
	 * @since      4.2.0
	 *
	 * @param array $parameters De parameters 0: gebruiker-id, 1: of het een herstart betreft.
	 * @param float $bedrag     Het bedrag dat betaald is.
	 * @param bool  $betaald    Of er werkelijk betaald is.
	 */
	public static function callback( $parameters, $bedrag, $betaald = true ) {
		if ( $betaald ) {
			$saldo         = new static( intval( $parameters[0] ) );
			$saldo->bedrag = $saldo->bedrag + $bedrag;
			$saldo->save( 'betaling per iDeal' );
			$saldo->email( '_ideal', $bedrag );
		}
	}

	/**
	 * Verwerk de reservering. Afhankelijk van de status melden dat er een afboeking gaat plaats vinden of de werkelijke afboeking uitvoeren.
	 * Verplaatst vanuit class reservering.
	 *
	 * @since 4.5.1
	 * @throws \Exception Als het saldo of de reservering niet opgeslagen kan worden.
	 */
	public static function meld_en_verwerk() {
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
						if ( $saldo->save( 'stook op ' . date( 'd-m-Y', $reservering->datum ) . ' door ' . $stoker->display_name ) ) {
							$emailer->send(
								[
									'to'         => "$medestoker->display_name <$medestoker->user_email>",
									'subject'    => 'Kleistad kosten zijn verwerkt op het stooksaldo',
									'slug'       => 'kleistad_email_stookkosten_verwerkt',
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
							'slug'       => 'kleistad_email_stookmelding',
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

}
