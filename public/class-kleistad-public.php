<?php
/**
 * Definitie van de publieke class van de plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */

/**
 * De kleistad class voor de publieke pagina's.
 */
class Kleistad_Public {

	/**
	 * Het ID van de plugin.
	 *
	 * @since    4.0.87
	 *
	 * @access   private
	 * @var      string    $plugin_name    De ID van de plugin.
	 */
	private $plugin_name;

	/**
	 * De versie van de plugin.
	 *
	 * @since    4.0.87
	 *
	 * @access   private
	 * @var      string    $version    De huidige versie van deze plugin.
	 */
	private $version;

	/**
	 * De url voor Ajax callbacks.
	 *
	 * @var string url voor Ajax callbacks
	 */
	private static $url;

	/**
	 * De kleistad plugin opties.
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
	 * @param string       $to bestemming.
	 * @param string       $subject onderwerp.
	 * @param string       $slug (pagina titel, als die niet bestaat wordt verondersteld dat de slug de bericht tekst bevat).
	 * @param array        $args de argumenten die in de slug pagina vervangen moeten worden.
	 * @param string|array $attachment een eventuele bijlage.
	 * @suppress PhanUnusedVariable
	 */
	public static function compose_email( $to, $subject, $slug, $args = [], $attachment = [] ) {
		$domein        = substr( strrchr( get_option( 'admin_email' ), '@' ), 1 );
		$emailadresses = [
			'info' => 'info@' . $domein,
			'from' => 'no-reply@' . $domein,
			'copy' => 'stook@' . $domein,
		];

		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			"From: Kleistad <{$emailadresses['from']}>",
			'bcc: kleistad@sprako.nl',
		];

		$page = get_page_by_title( $slug, OBJECT );
		if ( ! is_null( $page ) ) {
			$text = wpautop( $page->post_content );
			// Controleer of er includes zijn d.m.v. [pagina:yxz].
			do {
				$gevonden = stripos( $text, '[pagina:' );
				if ( ! ( false === $gevonden ) ) {
					$eind         = stripos( $text, ']', $gevonden );
					$include_slug = substr( $text, $gevonden + 8, $eind - $gevonden - 8 );
					$include_page = get_page_by_title( $include_slug, OBJECT );
					$include_text = ( ! is_null( $include_page ) ) ? wpautop( $include_page->post_content ) : $include_slug;
					$text         = substr_replace( $text, $include_text, $gevonden, $eind - $gevonden + 1 );
				}
			} while ( ! ( false === $gevonden ) );

			// Vervang alle parameters.
			foreach ( $args as $key => $value ) {
				$text = str_replace( '[' . $key . ']', $value, $text );
			}
			$fields = [ 'cc', 'bcc' ];

			// Vervang eventuele [cc:x] of [bcc:x] velden en stop die in de header.
			foreach ( $fields as $field ) {
				$gevonden = stripos( $text, '[' . $field . ':' );
				if ( ! ( false === $gevonden ) ) {
					$eind      = stripos( $text, ']', $gevonden );
					$headers[] = ucfirst( substr( $text, $gevonden + 1, $eind - $gevonden - 1 ) );
					$text      = substr( $text, 0, $gevonden ) . substr( $text, $eind + 1 );
				}
			}
		} else {
			// Pagina niet gevonden. Maak de test versie aan.
			$text = '<p>' . $slug . '</p><table>';
			foreach ( $args as $key => $arg ) {
				$text .= '<tr><th align="left" >' . $key . '</th><td align="left" >' . $arg . '</td></tr>';
			}
			$text .= '</table>';
		}

		// Maak de email aan.
		ob_start();
		require 'partials/kleistad-public-email.php';
		$html = ob_get_contents();
		ob_clean();

		$status = wp_mail( $to, $subject, $html, $headers, $attachment );
		if ( ! $status ) {
			error_log( "$subject $slug " . print_r( $headers, true ), 3, 'kleistad@sprako.nl' ); // phpcs:ignore
		}
		return $status;
	}

	/**
	 * Filter functie wijzigt afzender naar noreply adres
	 *
	 * @since    4.0.87
	 *
	 * @param  string $old unused.
	 * @return string
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function mail_from( $old ) {
		return 'no-reply@' . substr( strrchr( get_option( 'admin_email' ), '@' ), 1 );
	}

	/**
	 * Filter functie wijzigt afzender naam naar Kleistad
	 *
	 * @since    4.0.87
	 *
	 * @param  string $old unused.
	 * @return string
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function mail_from_name( $old ) {
		return 'Kleistad';
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.87
	 *
	 * @param      string $plugin_name   The name of the plugin.
	 * @param      string $version       The version of this plugin.
	 * @param      array  $options       De plugin options.
	 */
	public function __construct( $plugin_name, $version, $options ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->options     = $options;
		self::$url         = 'kleistad/v' . $version;
	}

	/**
	 * Registreer de stylesheets voor de publieke functies van de plugin.
	 *
	 * @since    4.0.87
	 */
	public function register_styles() {
		wp_register_style( 'jqueryui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', [], '1.12.1' );
		wp_register_style( 'datatables', '//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css', [], '1.10.19' );
		wp_register_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/kleistad-public.css', [ 'jqueryui-css', 'datatables', 'dashicons' ], $this->version, 'all' );
	}

	/**
	 * Registreer de JavaScripts voor de publieke functies van de plugin.
	 *
	 * @since    4.0.87
	 */
	public function register_scripts() {
		wp_register_script( 'datatables', '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js', [ 'jquery' ], '1.10.19' );
		wp_register_script( $this->plugin_name . 'cursus_inschrijving', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-cursus_inschrijving.js', [ 'jquery', 'jquery-ui-selectmenu', 'jquery-ui-spinner' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'abonnee_inschrijving', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-abonnee_inschrijving.js', [ 'jquery', 'jquery-ui-datepicker', 'jquery-ui-selectmenu' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'dagdelenkaart', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-dagdelenkaart.js', [ 'jquery', 'jquery-ui-datepicker' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'cursus_beheer', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-cursus_beheer.js', [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-tabs', 'jquery-ui-datepicker', 'jquery-ui-spinner', 'datatables' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'recept_beheer', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-recept_beheer.js', [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-autocomplete', 'datatables' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'recept', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-recept.js', [ 'jquery' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'saldo', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-saldo.js', [ 'jquery' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'saldo_overzicht', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-saldo_overzicht.js', [ 'jquery', 'datatables' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'stookbestand', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-stookbestand.js', [ 'jquery', 'jquery-ui-datepicker' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'registratie_overzicht', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-registratie_overzicht.js', [ 'jquery', 'jquery-ui-dialog', 'datatables' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'abonnee_wijziging', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-abonnee_wijziging.js', [ 'jquery', 'jquery-ui-dialog', 'jquery-ui-spinner' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'rapport', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-rapport.js', [ 'jquery', 'jquery-ui-dialog', 'datatables' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'betalingen', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-betalingen.js', [ 'jquery', 'jquery-ui-dialog', 'datatables' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'betaling', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-betaling.js', [ 'jquery' ], $this->version, true );
		wp_register_script( $this->plugin_name . 'reservering', plugin_dir_url( __FILE__ ) . 'js/kleistad-public-reservering.js', [ 'jquery', 'jquery-ui-dialog' ], $this->version, true );
		wp_localize_script(
			$this->plugin_name . 'reservering', 'kleistadData', [
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'base_url'        => self::base_url(),
				'success_message' => 'de reservering is geslaagd!',
				'error_message'   => 'het was niet mogelijk om de reservering uit te voeren',
			]
		);
		wp_localize_script(
			$this->plugin_name . 'recept', 'kleistadData', [
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'base_url'        => self::base_url(),
				'success_message' => 'de recepten konden worden opgevraagd!',
				'error_message'   => 'het was niet mogelijk om de recepten uit de database op te vragen',
			]
		);
	}

	/**
	 * Registreer de AJAX endpoints
	 *
	 * @since   4.0.87
	 */
	public function register_endpoints() {
		register_rest_route(
			self::$url, '/reserveer', [
				'methods'             => 'POST',
				'callback'            => [ 'kleistad_public_reservering', 'callback_muteer' ],
				'args'                => [
					'dag'          => [
						'required' => true,
					],
					'maand'        => [
						'required' => true,
					],
					'jaar'         => [
						'required' => true,
					],
					'oven_id'      => [
						'required' => true,
					],
					'temperatuur'  => [
						'required' => false,
					],
					'soortstook'   => [
						'required' => false,
					],
					'programma'    => [
						'required' => false,
					],
					'verdeling'    => [
						'required' => false,
					],
					'opmerking'    => [
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
				'methods'             => 'POST',
				'callback'            => [ 'kleistad_public_reservering', 'callback_show' ],
				'args'                => [
					'maand'   => [
						'required' => true,
					],
					'jaar'    => [
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

		register_rest_route(
			self::$url, '/recept', [
				'methods'             => 'POST',
				'callback'            => [ 'kleistad_public_recept', 'callback_recept' ],
				'args'                => [
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
				'methods'             => 'POST',
				'callback'            => [ 'kleistad_betalen', 'callback_betaling_verwerkt' ],
				'args'                => [
					'id' => [
						'required' => true,
					],
				],
				'permission_callback' => function() {
						return true;
				},
			]
		);

		register_rest_route(
			self::$url, '/herhaalbetaling', [
				'methods'             => 'POST',
				'callback'            => [ 'kleistad_betalen', 'callback_herhaalbetaling_verwerkt' ],
				'args'                => [
					'id' => [
						'required' => true,
					],
				],
				'permission_callback' => function() {
						return true;
				},
			]
		);

		register_rest_route(
			self::$url, '/ondemandbetaling', [
				'methods'             => 'POST',
				'callback'            => [ 'kleistad_betalen', 'callback_ondemandbetaling_verwerkt' ],
				'args'                => [
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
	 * Maak de ceramics recept post type en taxonomy
	 *
	 * @since 4.1.0
	 */
	public function create_recept_type() {
		ob_start();
		register_post_type(
			'kleistad_recept', [
				'labels'            => [
					'name'               => 'Keramiek recepten',
					'singular_name'      => 'Keramiek recept',
					'add_new'            => 'Toevoegen',
					'add_new_item'       => 'Recept toevoegen',
					'edit'               => 'Wijzigen',
					'edit_item'          => 'Recept wijzigen',
					'view'               => 'Inzien',
					'view_item'          => 'Recept inzien',
					'search_items'       => 'Recept zoeken',
					'not_found'          => 'Niet gevonden',
					'not_found_in_trash' => 'Niet in prullenbak gevonden',
				],
				'public'            => true,
				'supports'          => [
					'title',
					'comments',
					'thumbnail',
				],
				'rewrite'           => [
					'slug' => 'recepten',
				],
				'show_ui'           => true,
				'show_in_admin_bar' => false,
			]
		);
		register_taxonomy(
			'kleistad_recept_cat', 'kleistad_recept', [
				'hierarchical'      => true,
				'labels'            => [
					'name'          => 'Recept categoriën',
					'singular_name' => 'Recept categorie',
					'search_items'  => 'Zoek recept categorie',
					'all_items'     => 'Alle recept categoriën',
					'edit_item'     => 'Wijzig recept categorie',
					'update_item'   => 'Sla recept categorie op',
					'add_new_item'  => 'Voeg recept categorie toe',
					'new_item_name' => 'Nieuwe recept recept categorie',
					'menu_name'     => 'Recept categoriën',
				],
				'query_var'         => true,
				'show_ui'           => true,
				'show_admin_column' => true,
			]
		);
		register_taxonomy_for_object_type( 'kleistad_recept_cat', 'kleistad_recept' );
	}

	/**
	 * Wordt aangeroepen door filter single_template, zorgt dat WP de juiste template file toont.
	 *
	 * @since 4.1.0
	 *
	 * @param string $single_template het template path.
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
	 * Wordt aangeroepen door filter comments_template, zorgt dat WP de juiste template file toont.
	 *
	 * @since 4.1.0
	 *
	 * @param string $comments_template het template path.
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
	 * Wordt aangeroepen door filter comment form default fields, om niet te vragen naar een website url.
	 *
	 * @since 4.1.0
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
	 * Kijk bij de login of een account geblokkeerd is
	 *
	 * @since 4.0.87
	 * @param string $user_login niet gebruikte parameter.
	 * @param object $user wp user object.
	 */
	public function user_login( $user_login, $user = null ) {

		if ( ! $user ) {
			$user = get_user_by( 'login', $user_login );
		}
		if ( ! $user ) {
			return;
		}
		$disabled = get_user_meta( $user->ID, 'kleistad_disable_user', true );

		if ( '1' === $disabled ) {
			wp_clear_auth_cookie();

			$login_url = add_query_arg( 'disabled', '1', site_url( 'wp-login.php', 'login' ) );
			wp_redirect( $login_url );
			exit;
		}
	}

	/**
	 * Toon een melding aan geblokkeerde gebruikers bij het inloggen.
	 *
	 * @since 4.0.87
	 *
	 * @param string $message the message shown to the user.
	 * @return string
	 */
	public function user_login_message( $message ) {

		$disabled = filter_input( INPUT_GET, 'disabled' );
		if ( ! is_null( $disabled ) && 1 === $disabled ) {
			$message = '<div id="login_error">' . apply_filters( 'kleistad_disable_users_notice', 'Inloggen op dit account niet toegestaan' ) . '</div>';
		}
		return $message;
	}

	/**
	 * Verberg de toolbar voor iedereen die geen edit toegang op pagina's heeft.
	 *
	 * @since 4.0.87
	 */
	public function verberg_toolbar() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			if ( is_admin_bar_showing() ) {
				show_admin_bar( false );
			}
		}
	}

	/**
	 * Toont login en loguit menu opties.
	 *
	 * @since 4.0.87
	 *
	 * @staticvar boolean $is_active Bewaart de activeringsstatus, als true dan niets doen.
	 * @param string   $items De menu opties.
	 * @param stdClass $args  De argumenten van het filter.
	 * @return string
	 */
	public function loginuit_menu( $items, $args ) {
		static $is_active = false;

		if ( is_admin() || 'primary' !== $args->theme_location || $is_active ) {
			return $items;
		}

		$redirect = ( is_home() ) ? false : get_permalink();
		if ( is_user_logged_in() ) {
			$link = '<a href="' . wp_logout_url( home_url() ) . '" title="Uitloggen">Uitloggen</a>';
		} else {
			$link = '<a href="' . wp_login_url( $redirect ) . '" title="Inloggen">Inloggen</a>';
		}
		$is_active = true;
		$items    .= '<li id="log-in-out-link" class="menu-item menu-type-link">' . $link . '</li>';
		return $items;
	}

	/**
	 * Shortcode form handler functie, toont formulier, valideert input, bewaart gegevens en toont resultaat
	 *
	 * @since 4.0.87
	 *
	 * @param array  $atts      de meegegeven params van de shortcode.
	 * @param string $content   wordt niet gebruikt.
	 * @param string $tag       wordt gebruikt als selector voor de diverse functie aanroepen.
	 * @return string           html resultaat.
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function shortcode_handler( $atts, $content, $tag ) {
		$form        = substr( $tag, strlen( 'kleistad-' ) );
		$form_class  = 'Kleistad_Public_' . str_replace( ' ', '_', ucwords( str_replace( '_', ' ', $form ) ) );
		$form_object = new $form_class( $this->plugin_name, $form, $atts, $this->options );
		wp_enqueue_style( $this->plugin_name );
		if ( wp_style_is( $this->plugin_name . $form, 'registered' ) ) {
			wp_enqueue_style( $this->plugin_name . $form );
		}
		if ( wp_script_is( $this->plugin_name . $form, 'registered' ) ) {
			wp_enqueue_script( $this->plugin_name . $form );
		}
		return $form_object->run();
	}

	/**
	 * Update abonnement batch job.
	 *
	 * @since 4.0.87
	 *
	 * @param int    $id    De id van de abonnee.
	 * @param string $actie De uit te voeren actie.
	 * @param int    $datum Datum waarop het moet worden uitgevoerd.
	 */
	public function update_abonnement( $id, $actie, $datum ) {
		$abonnement = new Kleistad_Abonnement( $id );
		$abonnement->event( $actie, $datum );
	}

	/**
	 *
	 * Update ovenkosten batch job
	 *
	 * @since 4.0.87
	 */
	public function update_ovenkosten() {
		$reserveringen = Kleistad_Reservering::all( true );
		foreach ( $reserveringen as $reservering ) {
			$reservering->meld_en_verwerk();
		}
	}

	/**
	 * Verwijder gebruiker, geactiveerd als er een gebruiker verwijderd wordt.
	 *
	 * @since 4.0.87
	 *
	 * @param int $gebruiker_id gebruiker id.
	 */
	public function verwijder_gebruiker( $gebruiker_id ) {
		Kleistad_reservering::verwijder( $gebruiker_id );
	}

}
