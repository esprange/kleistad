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
 * @property string reden
 * @property float  bedrag
 * @property float  storting
 * @property int    volgnr
 */
class Saldo extends Artikel {

	const META_KEY = 'stooksaldo';

	/**
	 * De constructor
	 *
	 * @since      4.0.87
	 *
	 * @param int $klant_id De gebruiker waarvoor het saldo wordt gemaakt.
	 */
	public function __construct( $klant_id ) {
		$this->klant_id = $klant_id;
		$default_data   = [
			'bedrag'    => 0.0,
			'reden'     => '',
			'storting'  => 0.0,
			'ontvangst' => 0.0,
			'volgnr'    => 1,
		];
		$huidig_saldo   = get_user_meta( $this->klant_id, self::META_KEY, true );
		if ( is_array( $huidig_saldo ) ) {
			$this->data = wp_parse_args( $huidig_saldo, $default_data );
		} else {
			$this->data   = $default_data;
			$this->bedrag = (float) $huidig_saldo ?: 0.0;
		}
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
		switch ( $attribuut ) {
			case 'code':
				return "S$this->klant_id-" . strftime( '%y%m%d' ) . "-$this->volgnr";
			case 'bedrag':
			case 'ontvangst':
			case 'storting':
				return (float) $this->data[ $attribuut ];
			default:
				return $this->data[ $attribuut ];
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
		$this->data[ $attribuut ] = $waarde;
	}

	/**
	 * Betaal de bijstorting saldo met iDeal.
	 *
	 * @since      4.2.0
	 *
	 * @param string $bericht Het bericht bij succesvolle betaling.
	 * @return string De redirect url ingeval van een ideal betaling.
	 */
	public function betalen( $bericht ) {
		$betalen = new \Kleistad\Betalen();
		return $betalen->order(
			$this->klant_id,
			__CLASS__ . '-' . $this->code,
			$this->storting,
			'Kleistad stooksaldo ' . $this->code,
			$bericht
		);
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
				'slug'        => 'kleistad_email_saldo_wijziging' . $type,
				'attachments' => $factuur,
				'parameters'  => [
					'voornaam'   => $gebruiker->first_name,
					'achternaam' => $gebruiker->last_name,
					'bedrag'     => number_format_i18n( $this->ontvangst, 2 ),
					'storting'   => number_format_i18n( $this->storting, 2 ),
					'saldo'      => number_format_i18n( $this->bedrag, 2 ),
					'saldo_link' => $this->betaal_link(),
				],
			]
		);
	}

	/**
	 * Bewaar het aangepaste saldo
	 *
	 * @since      4.0.87
	 *
	 * @return bool True als saldo is aangepast.
	 */
	public function save() {
		$huidig_saldo = $this->huidig_saldo();
		if ( update_user_meta( $this->klant_id, self::META_KEY, $this->data ) ) {
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
	 * De factuur regels.
	 *
	 * @return array
	 */
	protected function factuurregels() {
		return [
			[
				'artikel' => 'stooksaldo',
				'aantal'  => 1,
				'prijs'   => $this->storting,
			],
		];
	}

	/**
	 * Private functie om de huidige saldo stand op te vragen
	 *
	 * @since      4.0.87
	 *
	 * @return float De huidige saldo stand.
	 */
	private function huidig_saldo() {
		$huidig_saldo = get_user_meta( $this->klant_id, self::META_KEY, true );
		return ( '' === $huidig_saldo ) ? 0.0 : (float) $huidig_saldo;
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
	public static function callback( $parameters, $bedrag, $betaald ) {
		if ( $betaald ) {
			$saldo            = new static( intval( $parameters[0] ) );
			$saldo->bedrag   += $bedrag;
			$saldo->ontvangst = $bedrag;
			$saldo->reden     = 'betaling per iDeal';
			$saldo->save();
			$saldo->email( '_ideal', $saldo->bestel_order( $bedrag ) );
		}
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
					$stoker = get_userdata( $reservering->klant_id );
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
					$bedrag     = $ovens[ $reservering->oven_id ]->stookkosten( $reservering->klant_id, 100 );
					$stoker     = get_userdata( $reservering->klant_id );
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
