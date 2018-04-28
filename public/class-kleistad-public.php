<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * Include the classes
 */
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-entity.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-oven.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-cursus.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-abonnement.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-roles.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-gebruiker.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-shortcode.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-betalen.php';

/**
 * The public-facing functionality of the plugin.
 */
class Kleistad_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    4.0.87
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    4.0.87
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The url for Ajax callbacks.
	 *
	 * @var string url voor Ajax callbacks
	 */
	private static $url;

	/**
	 * Array containing all plugin settings
	 *
	 * @var array kleistad plugin settings
	 */
	private $options;

	/**
	 * Geeft de basis url terug voor de endpoints.
	 *
	 * @return string url voor endpoints
	 */
	public static function base_url() {
		return rest_url( self::$url );
	}

	/**
	 * Helper functie, haalt email tekst vanuit pagina en vervangt alle placeholders en verzendt de mail
	 *
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

	/**
	 * Filter functie wijzigt afzender naar noreply adres
	 *
	 * @param type $old unused.
	 * @return string
	 */
	public function mail_from( $old ) {
		return 'noreply@' . substr( strrchr( get_option( 'admin_email' ), '@' ), 1 );
	}

	/**
	 * Filter functie wijzigt afzender naam naar Kleistad
	 *
	 * @param type $old unused.
	 * @return string
	 */
	public function mail_from_name( $old ) {
		return 'Kleistad';
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.87
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		self::$url = 'kleistad/v' . $version;
		$this->options = get_option( 'kleistad-opties' );
		date_default_timezone_set( 'Europe/Amsterdam' );

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    4.0.87
	 */
	public function register_styles() {
		wp_register_style( 'jqueryui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
		wp_register_style( 'datatables', '//cdn.datatables.net/1.10.15/css/jquery.dataTables.css' );
		wp_register_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/kleistad-public.css', [ 'jqueryui-css', 'datatables', 'dashicons' ], $this->version, 'all' );
	}

	/**
	 * Register the JavaScripts for the public-facing side of the site.
	 *
	 * @since    4.0.87
	 */
	public function register_scripts() {
		wp_register_script( 'datatables', '//cdn.datatables.net/1.10.15/js/jquery.dataTables.js', [ 'jquery' ] );
		wp_register_script( $this->plugin_name . 'cursus_inschrijving', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-cursus_inschrijving.js', [ 'jquery', 'jquery-ui-selectmenu', 'jquery-ui-spinner' ], $this->version, false );
		wp_register_script( $this->plugin_name . 'abonnee_inschrijving', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-abonnee_inschrijving.js', [ 'jquery', 'jquery-ui-datepicker', 'jquery-ui-selectmenu' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'cursus_beheer', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-cursus_beheer.js', [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-tabs', 'jquery-ui-datepicker', 'jquery-ui-spinner', 'datatables' ], $this->version, false );
		wp_register_script( $this->plugin_name . 'recept_beheer', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-recept_beheer.js', [ 'jquery', 'jquery-ui-dialog', 'datatables' ], $this->version, false );
		wp_register_script( $this->plugin_name . 'recept', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-recept.js', [ 'jquery' ], $this->version, false );
		wp_register_script( $this->plugin_name . 'saldo', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-saldo.js', [ 'jquery', 'jquery-ui-datepicker', 'jquery-ui-selectmenu' ], $this->version, false );
		wp_register_script( $this->plugin_name . 'saldo_overzicht', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-saldo_overzicht.js', [ 'jquery', 'datatables' ], $this->version, false );
		wp_register_script( $this->plugin_name . 'stookbestand', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-stookbestand.js', [ 'jquery', 'jquery-ui-datepicker' ], $this->version, false );
		wp_register_script( $this->plugin_name . 'registratie_overzicht', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-registratie_overzicht.js', [ 'jquery', 'jquery-ui-dialog', 'datatables' ], $this->version, false );
		wp_register_script( $this->plugin_name . 'rapport', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-rapport.js', [ 'jquery', 'jquery-ui-dialog', 'datatables' ], $this->version, false );
		wp_register_script( $this->plugin_name . 'betalingen', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-betalingen.js', [ 'jquery', 'jquery-ui-dialog', 'datatables' ], $this->version, false );
		wp_register_script( $this->plugin_name . 'reservering', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-reservering.js', [ 'jquery', 'jquery-ui-dialog' ], $this->version, false );
		wp_localize_script(
			$this->plugin_name . 'reservering', 'kleistadData', [
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'base_url' => self::base_url(),
				'success_message' => 'de reservering is geslaagd!',
				'error_message' => 'het was niet mogelijk om de reservering uit te voeren',
			]
		);
		wp_localize_script(
			$this->plugin_name . 'recept', 'kleistadData', [
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'base_url' => self::base_url(),
				'success_message' => 'de recepten konden worden opgevraagd!',
				'error_message' => 'het was niet mogelijk om de recepten uit de database op te vragen',
			]
		);
	}

	/**
	 * Register the AJAX endpoints
	 *
	 * @since   4.0.87
	 */
	public function register_endpoints() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-kleistad-public-reservering.php';
		register_rest_route(
			self::$url, '/reserveer', [
				'methods' => 'POST',
				'callback' => [ 'kleistad_public_reservering', 'callback_muteer' ],
				'args' => [
					'dag' => [
						'required' => true,
					],
					'maand' => [
						'required' => true,
					],
					'jaar' => [
						'required' => true,
					],
					'oven_id' => [
						'required' => true,
					],
					'temperatuur' => [
						'required' => false,
					],
					'soortstook' => [
						'required' => false,
					],
					'programma' => [
						'required' => false,
					],
					'verdeling' => [
						'required' => false,
					],
					'opmerking' => [
						'required' => false,
					],
					'gebruiker_id' => [
						'required' => true,
					],
				],
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			]
		);
		register_rest_route(
			self::$url, '/show', [
				'methods' => 'POST',
				'callback' => [ 'kleistad_public_reservering', 'callback_show' ],
				'args' => [
					'maand' => [
						'required' => true,
					],
					'jaar' => [
						'required' => true,
					],
					'oven_id' => [
						'required' => true,
					],
				],
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			]
		);

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-kleistad-public-recept.php';
		register_rest_route(
			self::$url, '/recept', [
				'methods' => 'POST',
				'callback' => [ 'kleistad_public_recept', 'callback_recept' ],
				'args' => [
					'zoek' => [
						'required' => false,
					],
				],
				'permission_callback' => function() {
						return true;
				},
			]
		);

		register_rest_route(
			self::$url, '/betaling', [
				'methods' => 'POST',
				'callback' => [ 'kleistad_betalen', 'callback_betaling_verwerkt' ],
				'args' => [
					'id' => [
						'required' => true,
					],
				],
				'permission_callback' => function() {
						return true;
				},
			]
		);

	}

	/**
	 * Create the ceramics recept post type
	 *
	 * @since 4.1.0
	 */
	public function create_recept_type() {
		ob_start();
		register_post_type(
			'kleistad_recept', [
				'labels' => [
					'name' => 'Keramiek recepten',
					'singular_name' => 'Keramiek recept',
					'add_new' => 'Toevoegen',
					'add_new_item' => 'Recept toevoegen',
					'edit' => 'Wijzigen',
					'edit_item' => 'Recept wijzigen',
					'view' => 'Inzien',
					'view_item' => 'Recept inzien',
					'search_items' => 'Recept zoeken',
					'not_found' => 'Niet gevonden',
					'not_found_in_trash' => 'Niet in prullenbak gevonden',
				],
				'public' => true,
				'supports' => [
					'title',
					'comments',
					'thumbnail',
				],
				'rewrite' => [
					'slug' => 'recepten',
				],
				'show_ui' => true,
				'show_in_admin_bar' => false,
			]
		);
		register_taxonomy(
			'kleistad_recept_cat', 'kleistad_recept', [
				'hierarchical' => true,
				'labels' => [
					'name' => 'Recept categoriën',
					'singular_name' => 'Recept categorie',
					'search_items' => 'Zoek recept categorie',
					'all_items' => 'Alle recept categoriën',
					'edit_item' => 'Wijzig recept categorie',
					'update_item' => 'Sla recept categorie op',
					'add_new_item' => 'Voeg recept categorie toe',
					'new_item_name' => 'Nieuwe recept recept categorie',
					'menu_name' => 'Recept categoriën',
				],
				'query_var' => true,
				'show_ui' => true,
				'show_admin_column' => true,
			]
		);
		register_taxonomy_for_object_type( 'kleistad_recept_cat', 'kleistad_recept' );
	}

	/**
	 * Used by filter single_template, directs WP to template file.
	 *
	 * @param string $single_template the template path.
	 * @return string
	 */
	public function recept_template( $single_template ) {
		global $post;

		if ( 'kleistad_recept' === $post->post_type ) {
			$single_template = dirname( __FILE__ ) . '/partials/kleistad-public-single-recept.php';
		}
		return $single_template;
	}

	/**
	 * Used by filter comments_template, directs WP to template file.
	 *
	 * @param string $comments_template the template path.
	 * @return string
	 */
	public function comments_template( $comments_template ) {
		global $post;

		if ( 'kleistad_recept' === $post->post_type ) {
			$comments_template = dirname( __FILE__ ) . '/partials/kleistad-public-comments-recept.php';
		}
		return $comments_template;
	}

	/**
	 * Used by filter comment form default fields, om niet te vragen naar een website url.
	 *
	 * @param array $fields De commentaar velden.
	 * @return array
	 */
	public function comment_fields( $fields ) {
		if ( isset( $fields['url'] ) ) {
			unset( $fields['url'] );
		}
		return $fields;
	}

	/**
	 * After login check to see if user account is disabled
	 *
	 * @since 4.0.87
	 * @param string $user_login unused.
	 * @param object $user wp user object.
	 */
	public function user_login( $user_login, $user = null ) {

		if ( ! $user ) {
			$user = get_user_by( 'login', $user_login );
		}
		if ( ! $user ) {
			// not logged in - definitely not disabled.
			return;
		}
		// Get user meta.
		$disabled = get_user_meta( $user->ID, 'kleistad_disable_user', true );

		// Is the use logging in disabled?
		if ( '1' === $disabled ) {
			// Clear cookies, a.k.a log user out.
			wp_clear_auth_cookie();

			// Build login URL and then redirect.
			$login_url = add_query_arg( 'disabled', '1', site_url( 'wp-login.php', 'login' ) );
			wp_redirect( $login_url );
			exit;
		}
	}

	/**
	 * Show a notice to users who try to login and are disabled
	 *
	 * @since 4.0.87
	 * @param string $message the message shown to the user.
	 * @return string
	 */
	public function user_login_message( $message ) {

		// Show the error message if it seems to be a disabled user.
		$disabled = filter_input( INPUT_GET, 'disabled' );
		if ( ! is_null( $disabled ) && 1 === $disabled ) {
			$message = '<div id="login_error">' . apply_filters( 'kleistad_disable_users_notice', 'Inloggen op dit account niet toegestaan' ) . '</div>';
		}
		return $message;
	}

	/**
	 * Shortcode form handler functie, toont formulier, valideert input, bewaart gegevens en toont resultaat
	 *
	 * @since 4.0.87
	 * @param array  $atts      the params of the shortcode.
	 * @param string $content   wordt niet gebruikt.
	 * @param string $tag       wordt gebruikt als selector voor de diverse functie aanroepen.
	 * @return string           html resultaat.
	 */
	public function shortcode_handler( $atts, $content = '', $tag ) {

		$html = '';
		$data = null;
		$form = substr( $tag, strlen( 'kleistad-' ) );
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-kleistad-public-' . str_replace( '_', '-', $form ) . '.php';

		wp_enqueue_style( $this->plugin_name );
		if ( wp_style_is( $this->plugin_name . $form, 'registered' ) ) {
			wp_enqueue_style( $this->plugin_name . $form );
		}
		if ( wp_script_is( $this->plugin_name . $form, 'registered' ) ) {
			wp_enqueue_script( $this->plugin_name . $form );
		}

		$form_class = 'Kleistad_Public_' . str_replace( ' ', '_', ucwords( str_replace( '_', ' ', $form ) ) );
		$form_object = new $form_class( $this->plugin_name, $atts );

		$betaald = filter_input( INPUT_GET, 'betaald' );
		if ( ! is_null( $betaald ) ) {
			$gebruiker_id = filter_input( INPUT_GET, 'betaald' );
			$betaling = new Kleistad_Betalen();
			$result = $betaling->controleer( $gebruiker_id );
			if ( ! is_wp_error( $result ) ) {
				$html .= '<div class="kleistad_succes"><p>' . $result . '</p></div>';
			} else {
				$html .= '<div class="kleistad_fout"><p>' . $result->get_error_message() . '</p></div>';
			}
		}

		if ( ! is_null( filter_input( INPUT_POST, 'kleistad_submit_' . $form ) ) ) {
			if ( wp_verify_nonce( filter_input( INPUT_POST, '_wpnonce' ), 'kleistad_' . $form ) ) {
				$result = $form_object->validate( $data );
				if ( ! is_wp_error( $result ) ) {
					$result = $form_object->save( $data );
				}
				if ( ! is_wp_error( $result ) ) {
					$html .= '<div class="kleistad_succes"><p>' . $result . '</p></div>';
					$data = null;
				} else {
					foreach ( $result->get_error_messages() as $error ) {
						$html .= '<div class="kleistad_fout"><p>' . $error . '</p></div>';
					}
				}
			} else {
				$html .= '<div class="kleistad_fout"><p>security fout</p></div>';
			}
		}
		$result = $form_object->prepare( $data );
		if ( is_wp_error( $result ) ) {
			$html .= '<div class="kleistad_fout"><p>' . $result->get_error_message() . '</p></div>';
			return $html;
		}
		ob_start();
		require plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/kleistad-public-' . str_replace( '_', '-', $form ) . '.php';
		$html .= ob_get_contents();
		ob_clean();
		return $html;
	}

	/**
	 *
	 * Update ovenkosten batch job
	 *
	 * @since 4.0.87
	 */
	public function update_ovenkosten() {
		Kleistad_Saldo::log( 'verwerking stookkosten gestart.' );

		$regelingen = new Kleistad_Regelingen();

		$oven_store = new Kleistad_Ovens();
		$ovens = $oven_store->get();

		$reservering_store = new Kleistad_Reserveringen();
		$reserveringen = $reservering_store->get();

		/*
		* saldering transacties uitvoeren
		*/
		foreach ( $reserveringen as &$reservering ) {
			if ( ! $reservering->verwerkt && $reservering->datum <= strtotime( '- ' . $this->options['termijn'] . ' days 00:00' ) ) {
				$gebruiker = get_userdata( $reservering->gebruiker_id );
				$verdeling = $reservering->verdeling;
				foreach ( $verdeling as &$stookdeel ) {
					if ( 0 === intval( $stookdeel['id'] ) ) {
						continue;
					}
					$medestoker = get_userdata( $stookdeel['id'] );
					$regeling = $regelingen->get( $stookdeel['id'], $reservering->oven_id );
					$kosten = ( is_null( $regeling ) ) ? $ovens[ $reservering->oven_id ]->kosten : $regeling;
					$prijs = round( $stookdeel['perc'] / 100 * $kosten, 2 );
					$stookdeel['prijs'] = $prijs;

					$saldo = new Kleistad_Saldo( $stookdeel['id'] );
					$saldo->bedrag = $saldo->bedrag - $prijs;
					if ( $saldo->save( 'stook op ' . date( 'd-m-Y', $reservering->datum ) . ' door ' . $gebruiker->display_name ) ) {

						$to = "$medestoker->first_name $medestoker->last_name <$medestoker->user_email>";
						self::compose_email(
							$to, 'Kleistad kosten zijn verwerkt op het stooksaldo', 'kleistad_email_stookkosten_verwerkt', [
								'voornaam' => $medestoker->first_name,
								'achternaam' => $medestoker->last_name,
								'stoker' => $gebruiker->display_name,
								'bedrag' => number_format( $prijs, 2, ',', '' ),
								'saldo' => number_format( $nieuw_saldo, 2, ',', '' ),
								'stookdeel' => $stookdeel['perc'],
								'stookdatum' => date( 'd-m-Y', $reservering->datum ),
								'stookoven' => $ovens[ $reservering->oven_id ]->naam,
							]
						);
					}
				}
				$reservering->verdeling = $verdeling;
				$reservering->verwerkt = true;
				$reservering->save();
			}
		}

		/*
		* de notificaties uitsturen voor stook die nog niet verwerkt is.
		*/
		foreach ( $reserveringen as &$reservering ) {
			if ( ! $reservering->verwerkt && ! $reservering->gemeld && $reservering->datum < strtotime( 'today' ) ) {

				$regeling = $regelingen->get( $reservering->gebruiker_id, $reservering->oven_id );

				$gebruiker = get_userdata( $reservering->gebruiker_id );
				$to = "$gebruiker->first_name $gebruiker->last_name <$gebruiker->user_email>";
				self::compose_email(
					$to, 'Kleistad oven gebruik op ' . date( 'd-m-Y', $reservering->datum ), 'kleistad_email_stookmelding', [
						'voornaam' => $gebruiker->first_name,
						'achternaam' => $gebruiker->last_name,
						'bedrag' => number_format( ( is_null( $regeling ) ) ? $ovens[ $reservering->oven_id ]->kosten : $regeling, 2, ',', '' ),
						'datum_verwerking' => date( 'd-m-Y', strtotime( '+' . $this->options['termijn'] . ' day', $reservering->datum ) ), // datum verwerking.
						'datum_deadline' => date( 'd-m-Y', strtotime( '+' . $this->options['termijn'] - 1 . ' day', $reservering->datum ) ), // datum deadline.
						'stookoven' => $ovens[ $reservering->oven_id ]->naam,
					]
				);
				$reservering->gemeld = true;
				$reservering->save();
			}
		}

		Kleistad_Saldo::log( 'verwerking stookkosten gereed.' );
	}

	/**
	 * Verwijder gebruiker, geactiveerd als er een gebruiker verwijderd wordt.
	 *
	 * @since 4.0.87
	 * @param int $gebruiker_id gebruiker id.
	 */
	public function verwijder_gebruiker( $gebruiker_id ) {
		Kleistad_reservering::verwijder( $gebruiker_id );
	}

}
