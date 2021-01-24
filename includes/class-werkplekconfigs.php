<?php
/**
 * De definitie van de werkplek configuratie verzameling class
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

/**
 * Kleistad WerkplekConfig class.
 *
 * @since 6.11.0
 */
class WerkplekConfigs implements Countable, Iterator {

	public const META_KEY = 'kleistad_werkplek_configs';

	/**
	 * De werkplekdata
	 *
	 * @var array $configs De configuraties.
	 */
	private array $configs = [];

	/**
	 * Intere index
	 *
	 * @var int $current_index De index.
	 */
	private int $current_index = 0;

	/**
	 * Constructor, laad de configuraties
	 *
	 * @since 6.11.0
	 */
	public function __construct() {
		foreach ( ( get_option( self::META_KEY, false ) ?: [] ) as $config ) {
			$werkplekconfig              = new WerkplekConfig();
			$werkplekconfig->start_datum = $config['start_datum'];
			$werkplekconfig->eind_datum  = $config['eind_datum'];
			$werkplekconfig->config      = $config['config'];
			$werkplekconfig->meesters    = $config['meesters'];
			$this->configs[]             = $werkplekconfig;
		}
	}

	/**
	 * Voeg een configuratie toe
	 *
	 * @param WerkplekConfig $configtoetevoegen de toe te voegen configuratie.
	 */
	public function toevoegen( WerkplekConfig $configtoetevoegen ) {
		/**
		 * De eerste configuratie toevoegen.
		 */
		if ( 0 === count( $this->configs ) ) {
			$this->configs[] = $configtoetevoegen;
			$this->save();
			return;
		}
		/**
		 * Zoek de configuratie, als periode al bestaat, deze vervangen.
		 */
		$config = $this->find( $configtoetevoegen->start_datum, $configtoetevoegen->eind_datum );
		if ( is_object( $config ) ) {
			$this->configs[ $this->current_index ] = $configtoetevoegen;
			$this->save();
			return;
		}
		/**
		 * Als er geen einddatum vermeld is, dat aan het eind toevoegen.
		 */
		if ( 0 === $configtoetevoegen->eind_datum ) {
			$this->toevoegen_aan_eind( $configtoetevoegen );
			$this->save();
			return;
		}
		/**
		 * Als er wel een einddatum vermeld is, tussenvoegen.
		 */
		$this->toevoegen_in_midden( $configtoetevoegen );
		$this->save();
	}

	/**
	 * Een toe te voegen config zonder eind_datum
	 *
	 * @param WerkplekConfig $configtoetevoegen De toe te voegen config.
	 */
	private function toevoegen_aan_eind( WerkplekConfig $configtoetevoegen ) {
		$index = count( $this->configs );
		while ( $index ) {  // Loop door het array in reverse order.
			-- $index;
			/**
			 *    [________>
			 *       [_____>
			 * wordt
			 *    [_][_____> en klaar
			 */
			if ( $configtoetevoegen->start_datum > $this->configs[ $index ]->start_datum ) {
				$this->configs[ $index ]->eind_datum = strtotime( 'yesterday', $configtoetevoegen->start_datum );
				break;
			}
			/**
			 *         [___>
			 *       [_____>
			 * wordt
			 *       [_____> en ga verder
			 */
			unset( $this->configs[ $index ] );
		}
		$this->configs[] = $configtoetevoegen;
	}

	/**
	 * Een toe te voegen config met eind_datum
	 *
	 * @param WerkplekConfig $configtoetevoegen De toe te voegen config.
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	private function toevoegen_in_midden( WerkplekConfig $configtoetevoegen ) {
		foreach ( $this->configs as $index => &$config ) {
			/**
			 * Eind periode splitsen als in eindperiode of binnen één periode.
			 */
			if ( 0 === $config->eind_datum ||
				( $configtoetevoegen->start_datum > $config->start_datum && $configtoetevoegen->eind_datum < $config->eind_datum ) ) {
				$clone              = clone ( $config );
				$clone->start_datum = strtotime( 'tomorrow', $configtoetevoegen->eind_datum );
				$config->eind_datum = strtotime( 'yesterday', $configtoetevoegen->start_datum );
				$this->configs[]    = $configtoetevoegen;
				$this->configs[]    = $clone;
				return;
			}
			/**
			 * Nieuwe periode overlapt de huidige volledig, dan verwijderen.
			 */
			if ( $configtoetevoegen->start_datum <= $config->start_datum && $configtoetevoegen->eind_datum >= $config->eind_datum ) {
				unset( $this->configs[ $index ] );
				continue;
			}
			/**
			 * Skip als nieuwe periode na huidige periode.
			 */
			if ( $configtoetevoegen->start_datum > $config->eind_datum ) {
				continue;
			}
			/**
			 * Nieuwe periode overlapt huidige periode aan eind.
			 */
			if ( $configtoetevoegen->eind_datum >= $config->eind_datum ) {
				$config->eind_datum                       = strtotime( 'yesterday', $configtoetevoegen->start_datum );
				$this->configs[ $index + 1 ]->start_datum = strtotime( 'tomorrow', $configtoetevoegen->eind_datum );
				$this->configs[]                          = $configtoetevoegen;
				return;
			}
			/**
			 * Nieuwe periode overlapt huidige periode aan begin.
			 */
			if ( $configtoetevoegen->eind_datum > $config->start_datum ) {
				$config->start_datum = strtotime( 'tomorrow', $configtoetevoegen->eind_datum );
				$this->configs[]     = $configtoetevoegen;
				return;
			}
		}
	}

	/**
	 * Verwijder een configuratie
	 *
	 * @param WerkplekConfig $configteverwijderen De te verwijderen config.
	 */
	public function verwijder( WerkplekConfig $configteverwijderen ) {
		$index = count( $this->configs );
		while ( $index ) {
			--$index;
			if ( $this->configs[ $index ]->start_datum === $configteverwijderen->start_datum &&
				$this->configs[ $index ]->eind_datum === $configteverwijderen->eind_datum ) {
				/**
				 *     [_____][__x_]>
				 * wordt
				 *     [___________]>
				 */
				if ( $index ) {
					$this->configs[ $index - 1 ]->eind_datum = $configteverwijderen->eind_datum;
				}
				/**
				 *    [__x__]>
				 * wordt
				 *    >
				 */
				unset( $this->configs[ $index ] );
			}
		}
		$this->save();
	}

	/**
	 * Sla de configs op in de database.
	 *
	 * @since 6.11.0
	 */
	private function save() {
		usort(
			$this->configs,
			function( $links, $rechts ) : int {
				if ( 0 === $links->eind_datum ) {
					return 1;
				}
				if ( 0 === $rechts->eind_datum ) {
					return -1;
				}
				return $links->start_datum <=> $rechts->start_datum;
			}
		);
		$configs = [];
		foreach ( $this->configs as $config ) {
			$configs[] = [
				'start_datum' => $config->start_datum,
				'eind_datum'  => $config->eind_datum,
				'config'      => $config->config,
				'meesters'    => $config->meesters,
			];
		}
		update_option( self::META_KEY, $configs, true );
	}

	/**
	 * Vind de config obv datums
	 *
	 * @param int $datum       De start datum of de datums waarvoor een configuratie gezocht wordt .
	 * @param int $eind_datum  De eind datum of null.
	 * @return WerkplekConfig|bool
	 */
	public function find( int $datum, ?int $eind_datum = null ) {
		foreach ( $this->configs as $index => $config ) {
			$datum_in_periode = is_null( $eind_datum ) && $datum >= $config->start_datum && ( $datum <= $config->eind_datum || 0 === $config->eind_datum );
			$periode_gelijk   = ! is_null( $eind_datum ) && $datum === $config->start_datum && $eind_datum === $config->eind_datum;
			if ( $datum_in_periode ) {
				$config->adhoc_meesters( $datum ); // Wijzig de standaard beheerders zonodig door een of meer ad hoc beheerders.
				return $config;
			}
			if ( $periode_gelijk ) {
				$this->current_index = $index;
				return $config;
			}
		}
		return false;
	}

	/**
	 * Geef het aantal configuraties terug.
	 *
	 * @return int Het aantal.
	 */
	public function count(): int {
		return count( $this->configs );
	}

	/**
	 * Geef de huidige werkplek configuratie terug.
	 *
	 * @return WerkplekConfig De configuratie.
	 */
	public function current(): WerkplekConfig {
		return $this->configs[ $this->current_index ];
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
		return isset( $this->configs[ $this->current_index ] );
	}

}
