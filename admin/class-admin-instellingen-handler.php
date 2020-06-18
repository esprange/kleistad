<?php
/**
 * De admin functies van de kleistad plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      6.4.2
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * De admin-specifieke functies van de plugin voor de Instellingen pagina.
 */
class Admin_Instellingen_Handler {

	/**
	 *  De plugin opties
	 *
	 * @since     4.0.87
	 * @access    private
	 * @var       array     $options  De plugin options.
	 */
	private $options;

	/**
	 *  De plugin setup
	 *
	 * @since     6.2.1
	 * @access    private
	 * @var       array     $setup  De plugin technische setup.
	 */
	private $setup;

	/**
	 * Initializeer het object.
	 *
	 * @since    4.0.87
	 * @param array $options De plugin options.
	 * @param array $setup   De plugin setup.
	 */
	public function __construct( $options, $setup ) {
		$this->options = $options;
		$this->setup   = $setup;
	}

	/**
	 * Toon de instellingen page van de plugin.
	 *
	 * @since    4.0.87
	 */
	public function display_settings_page() {
		$result = true;
		if ( ! is_null( filter_input( INPUT_POST, 'connect' ) ) ) {
			\Kleistad\Google::vraag_service_aan( admin_url( 'admin.php?page=kleistad&tab=setup' ) );
		} elseif ( ! is_null( filter_input( INPUT_GET, 'code' ) ) ) {
			$result = \Kleistad\Google::koppel_service();
		} elseif ( ! is_null( filter_input( INPUT_POST, 'dagelijks' ) ) ) {
			do_action( 'kleistad_daily_jobs' );
		} elseif ( ! is_null( filter_input( INPUT_POST, 'corona' ) ) ) {
			$this->corona();
		}
		$active_tab = filter_input( INPUT_GET, 'tab' ) ?: 'instellingen';
		?>
		<div class="wrap">
			<?php if ( is_wp_error( $result ) ) : ?>
			<div class="error">
				<p><?php echo esc_html( $result->get_error_message() ); ?></p>
			</div>
			<?php endif ?>
			<h2 class="nav-tab-wrapper">
			    <a href="?page=kleistad&tab=instellingen" class="nav-tab <?php echo 'instellingen' === $active_tab ? 'nav-tab-active' : ''; ?>">Functionele instellingen</a>
			    <a href="?page=kleistad&tab=setup" class="nav-tab <?php echo 'setup' === $active_tab ? 'nav-tab-active' : ''; ?>">Technische instellingen</a>
			    <a href="?page=kleistad&tab=shortcodes" class="nav-tab <?php echo 'shortcodes' === $active_tab ? 'nav-tab-active' : ''; ?>">Shortcodes</a>
			    <a href="?page=kleistad&tab=email-parameters" class="nav-tab <?php echo 'email-parameters' === $active_tab ? 'nav-tab-active' : ''; ?>">Email parameters</a>
			</h2>
			<?php require "partials/admin-$active_tab.php"; ?>
		</div>
		<?php
	}

	/**
	 * Valideer de ingevoerde instellingen
	 *
	 * @since    4.0.87
	 *
	 * @param array $input de ingevoerde instellingen.
	 * @return array  $input
	 */
	public function validate_settings( $input ) {
		foreach ( $input as &$element ) {
			if ( is_string( $element ) ) {
				$element = sanitize_text_field( $element );
			} else {
				if ( is_array( $element ) ) {
					$element = $this->validate_settings( $element );
				}
			}
		}
		return $input;
	}

	/**
	 * Lees het corona beschikbaarheid bestand en sla dit op.
	 *
	 * @return void
	 */
	private function corona() {
		if ( isset( $_FILES['corona_file'] ) ) {
			$vandaag         = strtotime( 'today' );
			$beschikbaarheid = get_option( 'kleistad_corona_beschikbaarheid', [] );
			$csv             = array_map( 'str_getcsv', file( $_FILES['corona_file']['tmp_name'] ) ?: [] );
			foreach ( $beschikbaarheid as $datum => $tijden ) {
				if ( $datum >= $vandaag ) {
					unset( $beschikbaarheid[ $datum ] );
				}
			}
			foreach ( $csv as $line ) {
				list( $s_datum, $start, $eind, $limiet_draaien, $limiet_handvormen, $limiet_boven ) = explode( ';', $line[0] );
				$datum = strtotime( $s_datum );
				$tijd  = "$start - $eind";
				if ( false === $datum || $datum < $vandaag ) {
					continue;
				}
				$beschikbaarheid[ $datum ][] =
					[
						'T' => $tijd,
						'D' => $limiet_draaien,
						'H' => $limiet_handvormen,
						'B' => $limiet_boven,
					];
			}
			update_option( 'kleistad_corona_beschikbaarheid', $beschikbaarheid );
		}
	}

}
