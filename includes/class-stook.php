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
	const BISCUIT   = 'Biscuit';
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
	 * De stook datum
	 *
	 * @var int $datum De datum van de stook.
	 */
	public int $datum;

	/**
	 * De hoofdstoker
	 *
	 * @var int $hoofdstoker_id Het WP user_id van de hoofdstoker.
	 */
	public int $hoofdstoker_id;

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
	 * @var int|null $stook_id Het database id.
	 */
	private ?int $stook_id = 0;

	/**
	 * De over waarvoor gestookt wordt.
	 *
	 * @var Oven $oven De oven.
	 */
	private Oven $oven;

	/**
	 * Constructor
	 *
	 * @global object $wpdb wp database.
	 * @param Oven       $oven  De oven.
	 * @param int        $datum De datum van de stook.
	 * @param array|null $load  (optioneel) data waarmee het object geladen kan worden (ivm performance).
	 */
	public function __construct( Oven $oven, int $datum, ?array $load = null ) {
		global $wpdb;

		$this->oven  = $oven;
		$this->datum = $datum;
		$resultaat   = $load ?? $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}kleistad_reserveringen WHERE oven_id = %d AND datum BETWEEN %s AND %s",
				$oven->id,
				date( 'Y-m-d 00:00:00', $datum ),
				date( 'Y-m-d 23:59:59', $datum ),
			),
			ARRAY_A
		);
		if ( $resultaat ) {
			$this->temperatuur    = intval( $resultaat['temperatuur'] );
			$this->soort          = $resultaat['soortstook'];
			$this->programma      = intval( $resultaat['programma'] );
			$this->gemeld         = boolval( $resultaat['gemeld'] );
			$this->verwerkt       = boolval( $resultaat['verwerkt'] );
			$this->stook_id       = intval( $resultaat['id'] );
			$this->hoofdstoker_id = intval( $resultaat['gebruiker_id'] );
			foreach ( json_decode( $resultaat['verdeling'], true ) as $stookdeel ) {
				$this->stookdelen[] = new Stookdeel( $stookdeel['id'], intval( $stookdeel['perc'] ), floatval( $stookdeel['prijs'] ?? 0 ) );
			}
			return;
		}
		$this->hoofdstoker_id = get_current_user_id();
		$this->stookdelen[]   = new Stookdeel( $this->hoofdstoker_id, 100, 0 );
	}

	/**
	 * Bewaar de stook
	 *
	 * @global object $wpdb WP database.
	 * @throws Kleistad_Exception Bewaren gaat niet.
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
				'oven_id'      => $this->oven->id,
				'temperatuur'  => $this->temperatuur,
				'soortstook'   => $this->soort,
				'programma'    => $this->programma,
				'gebruiker_id' => $this->hoofdstoker_id,
				'verdeling'    => wp_json_encode( $stookdelen ) ?: '[]',
				'datum'        => date( 'Y-m-d', $this->datum ),
				'gemeld'       => intval( $this->gemeld ),
				'verwerkt'     => intval( $this->verwerkt ),
			];
		if ( 0 < $this->stook_id ) {
			if ( false === $wpdb->replace(
				"{$wpdb->prefix}kleistad_reserveringen",
				array_merge( $data, [ 'id' => $this->stook_id ] )
			) ) {
				throw new Kleistad_Exception( 'Database actie kon niet voltooid worden' );
			}
			return;
		}
		if ( false === $wpdb->insert( "{$wpdb->prefix}kleistad_reserveringen", $data ) ) {
			throw new Kleistad_Exception( 'Database actie kon niet voltooid worden' );
		}
		$this->stook_id = $wpdb->insert_id;
	}

	/**
	 * Verwijder de reservering.
	 *
	 * @global object $wpdb WP database.
	 *
	 * @return bool
	 */
	public function verwijder() : bool {
		global $wpdb;
		if ( $wpdb->delete(
			"{$wpdb->prefix}kleistad_reserveringen",
			[ 'id' => $this->stook_id ]
		) ) {
			$this->stook_id = null;
			return true;
		}
		return false;
	}

	/**
	 * Wijzig of voeg een verdeling toe.
	 *
	 * @param int    $temperatuur De stooktemperatuur.
	 * @param string $soortstook  De soortstook.
	 * @param int    $programma   Het programma.
	 * @param array  $verdeling   De verdeling.
	 *
	 * @return bool
	 */
	public function wijzig( int $temperatuur, string $soortstook, int $programma, array $verdeling ) : bool {
		$this->temperatuur    = $temperatuur;
		$this->soort          = $soortstook;
		$this->programma      = $programma;
		$this->hoofdstoker_id = $verdeling[0]['id'];
		$this->stookdelen     = [];
		foreach ( $verdeling as $stookdeel ) {
			$this->stookdelen[] = new Stookdeel( $stookdeel['id'], $stookdeel['perc'], $stookdeel['prijs'] = 0 );
		}
		try {
			$this->save();
			return true;
		} catch ( Kleistad_Exception ) {
			return false;
		}
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
	 *
	 * @return string De status tekst.
	 */
	public function get_statustekst() : string {
		if ( ! boolval( $this->stook_id ) ) {
			if ( $this->datum >= strtotime( 'today' ) || is_super_admin() ) {
				return self::RESERVEERBAAR;
			}
			return self::ONGEBRUIKT;
		}
		if ( ! $this->verwerkt ) {
			if ( get_current_user_id() === $this->stookdelen[0]->medestoker || current_user_can( OVERRIDE ) ) {
				if ( $this->datum >= strtotime( 'today' ) ) {
					return self::VERWIJDERBAAR;
				}
				return self::WIJZIGBAAR;
			}
			return self::ALLEENLEZEN;
		}
		return self::DEFINITIEF;
	}

	/**
	 * Verwerk een stook
	 *
	 * @global object $wpdb WordPress database.
	 */
	public function verwerk() {
		global $wpdb;
		$emailer = new Email();
		try {
			$stoker = get_userdata( $this->hoofdstoker_id );
			$wpdb->query( 'START TRANSACTION' );
			if ( self::ONDERHOUD !== $this->soort ) {
				foreach ( $this->stookdelen as $stookdeel ) {
					$stookdeel->prijs = $this->oven->get_stookkosten( $stookdeel->medestoker, $stookdeel->percentage, $this->temperatuur );
					$medestoker       = get_userdata( $stookdeel->medestoker );
					$saldo            = new Saldo( $stookdeel->medestoker );
					$saldo->bedrag   -= $stookdeel->prijs;
					$saldo->reden     = 'stook op ' . date( 'd-m-Y', $this->datum ) . ' door ' . $stoker->display_name;
					if ( false === $saldo->save() ) {
						fout( __CLASS__, "saldo van gebruiker $medestoker->display_name kon niet aangepast worden met kosten $stookdeel->prijs" );
						continue;
					}
					if ( 0 < $stookdeel->prijs ) {
						$emailer->send(
							[
								'to'         => "$medestoker->display_name <$medestoker->user_email>",
								'subject'    => 'Kleistad kosten zijn verwerkt op het saldo',
								'slug'       => 'stookkosten_verwerkt',
								'parameters' => [
									'voornaam'   => $medestoker->first_name,
									'achternaam' => $medestoker->last_name,
									'stoker'     => $stoker->display_name,
									'bedrag'     => number_format_i18n( $stookdeel->prijs, 2 ),
									'saldo'      => number_format_i18n( $saldo->bedrag, 2 ),
									'stookdeel'  => $stookdeel->percentage,
									'stookdatum' => date( 'd-m-Y', $this->datum ),
									'stookoven'  => $this->oven->naam,
								],
							]
						);
					}
				}
			}
			$wpdb->query( 'COMMIT' );
			$this->verwerkt = true;
			$this->save();
		} catch ( Kleistad_Exception $exceptie ) {
			$wpdb->query( 'ROLLBACK' );
			fout( __CLASS__, $exceptie->getMessage() );
		}
	}

	/**
	 * Meld een stook
	 */
	public function meld() {
		$emailer = new Email();
		if ( self::ONDERHOUD !== $this->soort ) {
			try {
				$stoker = get_userdata( $this->hoofdstoker_id );
				$tabel  = '<table><tr><td><strong>Naam</strong></td><td style=\"text-align:right;\"><strong>Percentage</strong></td></tr>';
				foreach ( $this->stookdelen as $stookdeel ) {
					if ( 0 === $stookdeel->medestoker ) {
						continue; // Volgende verdeling.
					}
					$medestoker = get_userdata( $stookdeel->medestoker );
					$tabel     .= "<tr><td>$medestoker->first_name $medestoker->last_name </td><td style=\"text-align:right;\" >$stookdeel->percentage %</td></tr>";
				}
				$tabel .= '<tr><td colspan="2" style="text-align:center;" >verdeling op ' . current_time( 'd-m-Y H:i' ) . '</td></table>';
				$emailer->send(
					[
						'to'         => "$stoker->display_name <$stoker->user_email>",
						'subject'    => 'Kleistad oven gebruik op ' . date( 'd-m-Y', $this->datum ),
						'slug'       => 'stookmelding',
						'parameters' => [
							'voornaam'         => $stoker->first_name,
							'achternaam'       => $stoker->last_name,
							'bedrag'           => number_format_i18n( $this->oven->get_stookkosten( $this->hoofdstoker_id, 100, $this->temperatuur ), 2 ),
							'datum_verwerking' => date( 'd-m-Y', strtotime( '+' . opties()['termijn'] . ' day', $this->datum ) ), // datum verwerking.
							'datum_deadline'   => date( 'd-m-Y', strtotime( '+' . ( opties()['termijn'] - 1 ) . ' day', $this->datum ) ), // datum deadline.
							'verdeling'        => $tabel,
							'stookoven'        => $this->oven->naam,
						],
					]
				);
				$this->gemeld = true;
				$this->save();
			} catch ( Kleistad_Exception $exceptie ) {
				fout( __CLASS__, $exceptie->getMessage() );
			}
		}
	}

}
