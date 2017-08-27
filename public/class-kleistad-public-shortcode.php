<?php

/**
 * The abstract class for shortcodes.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

abstract class Kleistad_Public_Shortcode {
	/**
	 *
	 * @var string  plugin_name
	 */

	protected $plugin_name;
	/**
	 *
	 * @var array shortcode parameters
	 */

	protected $atts;
	/**
	 *
	 * @var array plugin options
	 */
	protected $options;

	/**
	 * the constructor
	 *
	 * @since   4.0.0
	 * @param string $plugin_name plugin naam
	 * @param array  $atts shortcode parameters
	 */
	public function __construct( $plugin_name, $atts ) {
		$this->plugin_name = $plugin_name;
		$this->atts = $atts;
		$this->options = get_option( 'kleistad-opties' );
		wp_localize_jquery_ui_datepicker();
	}

	/**
	 * abstract definition of prepare function
	 *
	 * @since   4.0.0
	 */
	abstract public function prepare( $data);

	/**
	 * validate function, only used in case of forms
	 *
	 * @since   4.0.0
	 */
	public function validate() {
	}

	/**
	 * save function, only used in case of forms
	 *
	 * @since   4.0.0
	 * @param compacted array $data
	 */
	public function save( $data ) {
	}

	/**
	 * filter function, to be used only by compose_email
	 *    *
	 *
	 * @since   4.0.0
	 * @param type $old unused
	 * @return string
	 */
	public static function wp_mail_from_callback( $old ) {
		return 'no-reply@' . substr( strrchr( get_option( 'admin_email' ), '@' ), 1 );
	}

	/**
	 * filter function, to be used only by compose_email
	 *    *
	 *
	 * @since   4.0.0
	 * @param type $old unused
	 * @return string
	 */
	public static function wp_mail_from_name_callback( $old ) {
		return 'Kleistad';
	}

	/**
	 * helper functie, haalt email tekst vanuit pagina en vervangt alle placeholders en verzendt de mail
	 *
	 * @since   4.0.0
	 * @param string $to
	 * @param string $subject
	 * @param string $slug (pagina titel, als die niet bestaat wordt verondersteld dat de slug de bericht tekst bevat)
	 * @param array  $args
	 * @param string $attachment
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
			if ( ! ($gevonden === false) ) {
				$eind = stripos( $text, ']', $gevonden );
				$headers[] = ucfirst( substr( $text, $gevonden + 1, $eind - $gevonden - 1 ) );
				$text = substr( $text, 0, $gevonden ) . substr( $text, $eind + 1 );
			}
		}

		ob_start();
		require_once 'partials/kleistad-public-email.php';
		$html = ob_get_contents();
		ob_clean();

		return wp_mail( $to, $subject, $html, $headers, $attachment );
	}
}
