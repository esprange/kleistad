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
abstract class Kleistad_Shortcode {
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
		$text = ( ! is_null( $page ) ) ? apply_filters( 'the_content', $page->post_content ) : $slug;

		foreach ( $args as $key => $value ) {
			$text = str_replace( '[' . $key . ']', $value, $text );
		}
		$fields = [ 'cc', 'bcc' ];
		foreach ( $fields as $field ) {
			$gevonden = stripos( $text, '[' . $field . ':' );
			if ( ! ( false === $gevonden ) ) {
				$eind = stripos( $text, ']', $gevonden );
				$headers[] = ucfirst( substr( $text, $gevonden + 1, $eind - $gevonden - 1 ) );
				$text = substr( $text, 0, $gevonden ) . substr( $text, $eind + 1 );
			}
		}

		ob_start();
		?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="viewport" content="initial-scale=1.0"/>
		<meta name="format-detection" content="telephone=no"/>
		<title><?php echo esc_html( $subject ); ?></title>
	</head>
	<body>
		<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
			<tr>
				<td align="left" style="font-family:helvetica; font-size:13pt" >
					<?php echo preg_replace( '/\s+/', ' ', $text ); // WPCS: XSS ok. ?><br />
					<p>Met vriendelijke groet,</p>
					<p>Kleistad</p>
					<p><a href="mailto:<?php echo esc_attr( $emailadresses['info'] ); ?>" target="_top"><?php echo esc_html( $emailadresses['info'] ); ?></a></p>
				</td>                         
			</tr>
			<tr>
				<td align="center" style="font-family:calibri; font-size:9pt" >
					Deze e-mail is automatisch gegenereerd en kan niet beantwoord worden.
				</td>
			</tr>
		</table>
	</body>
</html>
		<?php
		$html = ob_get_contents();
		ob_clean();

		return wp_mail( $to, $subject, $html, $headers, $attachment );
	}
}
