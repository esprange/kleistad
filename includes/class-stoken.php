<?php
/**
 * De definitie van de stoken class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Countable;
use Iterator;
use Exception;

/**
 * Kleistad Stoken class.
 *
 * @since 6.11.0
 */
class Stoken implements Countable, Iterator {

	/**
	 * De stoken
	 *
	 * @var array $stoken De stoken.
	 */
	private array $stoken = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * De constructor
	 *
	 * @param int $oven_id     Het id van de oven.
	 * @param int $vanaf_datum Vanaf datum dat de stoken gevuld moeten worden.
	 * @param int $tot_datum   Tot datum dat de stoken gevuld moeten worden.
	 */
	public function __construct( int $oven_id, int $vanaf_datum, int $tot_datum ) {
		global $wpdb;
		$data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}kleistad_reserveringen WHERE oven_id = %d AND datum BETWEEN %s AND %s",
				$oven_id,
				date( 'Y-m-d 00:00:00', $vanaf_datum ),
				date( 'Y-m-d 23:59:59', $tot_datum ),
			),
			ARRAY_A
		);
		foreach ( $data as $row ) {
			$this->stoken[] = new Stook( $oven_id, strtotime( $row['datum'] ), $row );
		}
	}

	/**
	 * Verwijder een stook.
	 *
	 * @param Stook $stookverwijderen Te vervangen stook.
	 */
	public function verwijderen( Stook $stookverwijderen ) {
		foreach ( $this->stoken as $key => $stook ) {
			if ( $stookverwijderen->datum === $stook->datum ) {
				$stookverwijderen->verwijder();
				$this->stoken[ $key ] = $stookverwijderen;
			}
		}
	}

	/**
	 * Geef het aantal stoken terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->stoken );
	}

	/**
	 * Geef de huidige stook terug.
	 *
	 * @return Stook De stook.
	 */
	public function current(): Stook {
		return $this->stoken[ $this->current_index ];
	}

	/**
	 * Geef de sleutel terug.
	 *
	 * @return int De sleutel.
	 */
	public function key(): int {
		return $this->current_index;
	}

	/**
	 * Ga naar de volgende in de lijst.
	 */
	public function next() {
		$this->current_index++;
	}

	/**
	 * Ga terug naar het begin.
	 */
	public function rewind() {
		$this->current_index = 0;
	}

	/**
	 * Bepaal of het element bestaat.
	 *
	 * @return bool Of het bestaat of niet.
	 */
	public function valid(): bool {
		return isset( $this->stoken[ $this->current_index ] );
	}


	/**
	 * Verwerk de stook. Afhankelijk van de status melden dat er een afboeking gaat plaats vinden of de werkelijke afboeking uitvoeren.
	 *
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
		} catch ( Exception $exceptie ) {
			$wpdb->query( 'ROLLBACK' );
			error_log( 'stooksaldo verwerking: ' . $exceptie->getMessage() ); // phpcs:ignore
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
