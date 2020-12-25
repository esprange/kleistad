<?php
/**
 * De definitie van de stook class.
 *
 * @link       https://www.kleistad.nl
 * @since      6.11.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Exception;

/**
 * Kleistad Stook class.
 *
 * @since 6.11.0
 */
class Stook {

	/**
	 * Soorten stook
	 */
	const ONDERHOUD = 'Onderhoud';
	const GLAZUUR   = 'Glazuur';
	const BISCUIT   = 'B]iscuit';
	const OVERIG    = 'Overig';

	/**
	 * Opklimmende status
	 */
	const ONGEBRUIKT    = 'ongebruikt';
	const RESERVEERBAAR = 'reserveerbaar';
	const VERWIJDERBAAR = 'verwijderbaar';
	const ALLEENLEZEN   = 'alleenlezen';
	const WIJZIGBAAR    = 'wijzigbaar';
	const DEFINITIEF    = 'definitief';

	/**
	 * De oven
	 *
	 * @var int $oven_id Het id van de oven.
	 */
	public int $oven_id;

	/**
	 * De stook datum
	 *
	 * @var int $datum De datum van de stook.
	 */
	public int $datum;

	/**
	 * De hoofdstoker
	 *
	 * @var int $hoofdstoker Het WP user_id van de hoofdstoker.
	 */
	public int $hoofdstoker;

	/**
	 * De temperatuur van de stook
	 *
	 * @var int $temperatuur De temperatuur.
	 */
	public int $temperatuur = 0;

	/**
	 * Het gebruikte programma nummer van de stook
	 *
	 * @var int $programma Het programma.
	 */
	public int $programma = 0;

	/**
	 * Het soort stook
	 *
	 * @var string $soort De soort stook
	 */
	public string $soort = '';

	/**
	 * Of de stook gemeld is aan de hoofdstoker
	 *
	 * @var bool $gemeld Of de melding gedaan is.
	 */
	public bool $gemeld = false;

	/**
	 * Of de stook verwerkt is in de stooksaldi
	 *
	 * @var bool $verwerkt Of de verwerking gedaan is.
	 */
	public bool $verwerkt = false;

	/**
	 * De stookdelen van de stook
	 *
	 * @var array $stookdelen De stookdelen.
	 */
	public array $stookdelen = [];

	/**
	 * De interne sleutel van de stook
	 *
	 * @var int $stook_id Het database id.
	 */
	private int $stook_id = 0;

	/**
	 * Constructor
	 *
	 * @global object $wpdb wp database.
	 * @param int $oven_id Het oven id.
	 * @param int $datum   De datum van de stook.
	 */
	public function __construct( int $oven_id, int $datum ) {
		global $wpdb;

		$this->oven_id = $oven_id;
		$this->datum   = $datum;
		$resultaat     = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}kleistad_reserveringen WHERE oven_id = %d AND datum BETWEEN %s AND %s",
				$oven_id,
				date( 'Y-m-d 00:00:00', $datum ),
				date( 'Y-m-d 23:59:59', $datum ),
			),
			ARRAY_A
		);
		if ( $resultaat ) {
			$this->temperatuur = intval( $resultaat['temperatuur'] );
			$this->soort       = $resultaat['soortstook'];
			$this->programma   = intval( $resultaat['programma'] );
			$this->gemeld      = boolval( $resultaat['gemeld'] );
			$this->verwerkt    = boolval( $resultaat['verwerkt'] );
			$this->stook_id    = intval( $resultaat['id'] );
			$this->hoofdstoker = intval( $resultaat['gebruiker_id'] );
			foreach ( json_decode( $resultaat['verdeling'], true ) as $stookdeel ) {
				$this->stookdelen[] = new Stookdeel( $stookdeel['id'], intval( $stookdeel['perc'] ), intval( $stookdeel['prijs'] ?? 0 ) );
			}
			return;
		}
		$this->hoofdstoker  = get_current_user_id();
		$this->stookdelen[] = new Stookdeel( $this->hoofdstoker, 100, 0 );
	}

	/**
	 * Bewaar de stook
	 *
	 * @global object $wpdb WP database.
	 * @throws Exception    Bewaren gaat niet.
	 */
	public function save() : void {
		global $wpdb;

		$stookdelen = [];
		foreach ( $this->stookdelen as $stookdeel ) {
			$stookdelen[] = [
				'id'    => $stookdeel->medestoker,
				'perc'  => $stookdeel->percentage,
				'prijs' => number_format( $stookdeel->prijs, 2, '.', '' ),
			];
		}

		$data =
			[
				'oven_id'      => $this->oven_id,
				'temperatuur'  => $this->temperatuur,
				'soortstook'   => $this->soort,
				'programma'    => $this->programma,
				'gebruiker_id' => $this->hoofdstoker,
				'verdeling'    => wp_json_encode( $stookdelen ) ?: '[]',
				'datum'        => date( 'Y-m-d', $this->datum ),
				'gemeld'       => intval( $this->gemeld ),
				'verwerkt'     => intval( $this->verwerkt ),
			];
		if ( $this->stook_id ) {
			if ( false === $wpdb->replace(
				"{$wpdb->prefix}kleistad_reserveringen",
				array_merge( $data, [ 'id' => $this->stook_id ] )
			) ) {
				throw new Exception( 'Database actie kon niet voltooid worden' );
			}
			return;
		}
		if ( false === $wpdb->insert( "{$wpdb->prefix}kleistad_reserveringen", $data ) ) {
			throw new Exception( 'Database actie kon niet voltooid worden' );
		}
		$this->stook_id = $wpdb->insert_id;
	}

	/**
	 * Verwijder de reservering.
	 *
	 * @global object $wpdb WP database.
	 * @throws Exception    Verwijdering gaat niet.
	 */
	public function verwijder() {
		global $wpdb;
		if ( false === $wpdb->delete(
			"{$wpdb->prefix}kleistad_reserveringen",
			[ 'id' => $this->stook_id ]
		) ) {
			throw new Exception( 'Database actie kon niet voltooid worden' );
		}
		$this->stook_id = null;
	}

	/**
	 * Bepaal of de stook gereserveerd is.
	 *
	 * @return bool De reservering status.
	 */
	public function is_gereserveerd() : bool {
		return boolval( $this->stook_id );
	}

	/**
	 * Geef de status terug van de reservering.
	 */
	public function geef_statustekst() {
		if ( ! boolval( $this->stook_id ) ) {
			if ( $this->datum >= strtotime( 'today' ) || is_super_admin() ) {
				return self::RESERVEERBAAR;
			}
			return self::ONGEBRUIKT;
		}
		if ( ! $this->verwerkt ) {
			if ( get_current_user_id() === $this->stookdelen[0]->medestoker || current_user_can( OVERRIDE ) ) {
				if ( $this->datum >= strtotime( 'tomorrow' ) ) {
					return self::VERWIJDERBAAR;
				}
				return self::WIJZIGBAAR;
			}
			return self::ALLEENLEZEN;
		}
		return self::DEFINITIEF;
	}
}
