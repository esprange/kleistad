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
		$result        = true;
		$display       = new Admin_Instellingen_Display( $this->options, $this->setup );
		$googleconnect = new Googleconnect();
		if ( ! is_null( filter_input( INPUT_POST, 'connect' ) ) ) {
			$googleconnect->vraag_service_aan( admin_url( 'admin.php?page=kleistad&tab=setup' ) );
		} elseif ( ! is_null( filter_input( INPUT_GET, 'code' ) ) ) {
			$result = $googleconnect->koppel_service();
		} elseif ( ! is_null( filter_input( INPUT_POST, 'dagelijks' ) ) ) {
			do_action( 'kleistad_daily_jobs' );
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
			    <a href="?page=kleistad&tab=email_parameters" class="nav-tab <?php echo 'email_parameters' === $active_tab ? 'nav-tab-active' : ''; ?>">Email parameters</a>
			</h2>
			<?php $display->$active_tab(); ?>
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
				continue;
			}
			if ( is_array( $element ) ) {
				$element = $this->validate_settings( $element );
			}
		}
		return $input;
	}

}
