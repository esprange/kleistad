<?php
/**
 * The abstract class for shortcodes.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * The abstract class for shortcodes
 */
abstract class Kleistad_Public_Shortcode {
	/**
	 * The plugin name.
	 *
	 * @var string  plugin_name
	 */
	protected $plugin_name;

	/**
	 * The parameters used to call the shortcode.
	 *
	 * @var array shortcode parameters
	 */
	protected $atts;

	/**
	 * The plugin options.
	 *
	 * @var array plugin options
	 */
	protected $options;

	/**
	 * The constructor
	 *
	 * @since   4.0.87
	 * @param string $plugin_name plugin naam.
	 * @param array  $atts shortcode parameters.
	 */
	public function __construct( $plugin_name, $atts ) {
		$this->plugin_name = $plugin_name;
		$this->atts = $atts;
		$this->options = get_option( 'kleistad-opties' );
		wp_localize_jquery_ui_datepicker();
	}

	/**
	 * Abstract definition of prepare function
	 *
	 * @since   4.0.87
	 *
	 * @param array $data the data to prepare.
	 */
	abstract public function prepare( &$data);

	/**
	 * Validate function, only used in case of forms
	 *
	 * @since   4.0.87
	 * @param array $data the data validated.
	 */
	public function validate( &$data ) {
		return true;
	}

	/**
	 * Save function, only used in case of forms
	 *
	 * @since   4.0.87
	 * @param array $data the data to store.
	 */
	public function save( $data ) {
		return true;
	}

	/**
	 * Filter function, to be used only by compose_email
	 *    *
	 *
	 * @since   4.0.87
	 * @param type $old unused.
	 * @return string
	 */
	public static function wp_mail_from_callback( $old ) {
		return 'no-reply@' . substr( strrchr( get_option( 'admin_email' ), '@' ), 1 );
	}

	/**
	 * Filter function, to be used only by compose_email
	 *    *
	 *
	 * @since   4.0.87
	 * @param type $old unused.
	 * @return string
	 */
	public static function wp_mail_from_name_callback( $old ) {
		return 'Kleistad';
	}

	/**
	 * Helper functie, haalt email tekst vanuit pagina en vervangt alle placeholders en verzendt de mail
	 *
	 * @since   4.0.87
	 * @param string $to bestemming.
	 * @param string $subject onderwerp.
	 * @param string $slug (pagina titel, als die niet bestaat wordt verondersteld dat de slug de bericht tekst bevat).
	 * @param array  $args de argumenten die in de slug pagina vervangen moeten worden.
	 * @param string $attachment een eventuele bijlage.
	 */
	public static function compose_email( $to, $subject, $slug, $args = [], $attachment = [] ) {
		$domein = substr( strrchr( get_option( 'admin_email' ), '@' ), 1 );
		$emailadresses = [
			'info' => 'info@' . $domein,
			'from' => 'no-reply@' . $domein,
			'copy' => 'stook@' . $domein,
		];

		if ( ! has_filter( 'wp_mail_from', [ static::class, 'wp_mail_from_callback' ] ) ) {
			add_filter( 'wp_mail_from', [ static::class, 'wp_mail_from_callback' ] );
		}
		if ( ! has_filter( 'wp_mail_from_name', [ static::class, 'wp_mail_from_name_callback' ] ) ) {
			add_filter( 'wp_mail_from_name', [ static::class, 'wp_mail_from_name_callback' ] );
		}
		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		$headers[] = "From: Kleistad <{$emailadresses['from']}>";

		$page = get_page_by_title( $slug, OBJECT );
		$text = ( ! is_null( $page )) ? apply_filters( 'the_content', $page->post_content ) : $slug;

		foreach ( $args as $key => $value ) {
			$text = str_replace( '[' . $key . ']', $value, $text );
		}
		$fields = [ 'cc', 'bcc' ];
		foreach ( $fields as $field ) {
			$gevonden = stripos( $text, '[' . $field . ':' );
			if ( ! (false === $gevonden) ) {
				$eind = stripos( $text, ']', $gevonden );
				$headers[] = ucfirst( substr( $text, $gevonden + 1, $eind - $gevonden - 1 ) );
				$text = substr( $text, 0, $gevonden ) . substr( $text, $eind + 1 );
			}
		}

		ob_start();
		require 'partials/kleistad-public-email.php';
		$html = ob_get_contents();
		ob_clean();

		return wp_mail( $to, $subject, $html, $headers, $attachment );
	}
}
