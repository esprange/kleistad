<?php
/**
 * De admin functies van de kleistad plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

/**
 * De admin-specifieke functies van de plugin.
 */
class Kleistad_Admin {

	/**
	 * Het ID van de plugin.
	 *
	 * @since    4.0.87
	 * @access   private
	 * @var      string    $plugin_name    Het ID van de plugin.
	 */
	private $plugin_name;

	/**
	 * De versie van de plugin.
	 *
	 * @since    4.0.87
	 * @access   private
	 * @var      string    $version    De huidige versie.
	 */
	private $version;

	/**
	 *  De plugin opties
	 *
	 * @since     4.0.87
	 * @access    private
	 * @var       array     $options  De plugin options
	 */
	private $options;

	/**
	 * Initializeer het object.
	 *
	 * @since    4.0.87
	 * @param      string $plugin_name De naam van de plugin.
	 * @param      string $version     De versie van de plugin.
	 * @param      array  $options     De plugin options.
	 */
	public function __construct( $plugin_name, $version, $options ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->options     = $options;
	}

	/**
	 * Registreer de stylesheets van de admin functies.
	 *
	 * @since    4.0.87
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/kleistad-admin.css', [], $this->version, 'all' );
		wp_enqueue_style( 'jqueryui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', [], '1.12.1' );
	}

	/**
	 * Registreer de JavaScript voor de admin functies.
	 *
	 * @since    4.0.87
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/kleistad-admin.js', [ 'jquery', 'jquery-ui-datepicker' ], $this->version, false );
		wp_localize_jquery_ui_datepicker();
	}

	/**
	 * Definieer de admin panels
	 *
	 * @since    4.0.87
	 */
	public function add_plugin_admin_menu() {
		add_menu_page( 'Instellingen', 'Kleistad', 'manage_options', $this->plugin_name, [ $this, 'display_settings_page' ], plugins_url( '/images/kleistad_icon.png', __FILE__ ), ++$GLOBALS['_wp_last_object_menu'] );

		add_submenu_page( $this->plugin_name, 'Ovens', 'Ovens', 'manage_options', 'ovens', [ $this, 'ovens_page_handler' ] );
		add_submenu_page( 'ovens', 'Toevoegen oven', 'Toevoegen oven', 'manage_options', 'ovens_form', [ $this, 'ovens_form_page_handler' ] );

		add_submenu_page( $this->plugin_name, 'Regeling stookkosten', 'Regeling stookkosten', 'manage_options', 'regelingen', [ $this, 'regelingen_page_handler' ] );
		add_submenu_page( 'regelingen', 'Toevoegen regeling', 'Toevoegen regeling', 'manage_options', 'regelingen_form', [ $this, 'regelingen_form_page_handler' ] );

		add_submenu_page( $this->plugin_name, 'Stooksaldo beheer', 'Stooksaldo beheer', 'manage_options', 'stooksaldo', [ $this, 'stooksaldo_page_handler' ] );
		add_submenu_page( 'stooksaldo', 'Wijzigen stooksaldo', 'Wijzigen stooksaldo', 'manage_options', 'stooksaldo_form', [ $this, 'stooksaldo_form_page_handler' ] );

		add_submenu_page( $this->plugin_name, 'Abonnees', 'Abonnees', 'manage_options', 'abonnees', [ $this, 'abonnees_page_handler' ] );
		add_submenu_page( 'abonnees', 'Wijzigen abonnee', 'Wijzigen abonnee', 'manage_options', 'abonnees_form', [ $this, 'abonnees_form_page_handler' ] );

		add_submenu_page( $this->plugin_name, 'Cursisten', 'Cursisten', 'manage_options', 'cursisten', [ $this, 'cursisten_page_handler' ] );
		add_submenu_page( 'cursisten', 'Wijzigen cursist', 'Wijzigen cursist', 'manage_options', 'cursisten_form', [ $this, 'cursisten_form_page_handler' ] );

	}

	/**
	 * Voeg extra veld toe aan user profiles
	 *
	 * @since 4.0.87
	 * @param object $user unused.
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function use_profile_field( $user ) {
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}
		require 'partials/kleistad-admin-use-profile-field.php';
	}

	/**
	 * Bewaar dit custom field in user meta
	 *
	 * @since 4.0.87
	 * @param int $user_id unused.
	 */
	public function user_profile_field_save( $user_id ) {
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}
		$disabled_val = filter_input( INPUT_POST, 'kleistad_disable_user' );
		$disabled     = ! is_null( $disabled_val ) ? $disabled_val : 0;
		update_user_meta( $user_id, 'kleistad_disable_user', $disabled );
	}

	/**
	 * Controleer of de gebruiker geblokkeerd is
	 *
	 * @since 4.0.87
	 * @param array  $errors unused.
	 * @param int    $update unused.
	 * @param object $user   de gebruiker.
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function check_role( &$errors, $update, &$user ) {
		if ( ( intval( get_the_author_meta( 'kleistad_disable_user', $user->ID ) ) === 1 ) ) {
			$user->role = '';
		}
	}

	/**
	 * Toon de disabled waarde in de users lijst
	 *
	 * @since 4.0.87
	 * @param array $defaults default settings voor user.
	 * @return array
	 */
	public function manage_users_columns( $defaults ) {
		$defaults['kleistad_user_disabled'] = 'Gedeactiveerd';
		return $defaults;
	}

	/**
	 * Inhoud van de disabled users kolom
	 *
	 * @since 4.0.87
	 * @param mixed  $empty ongebruikt.
	 * @param string $column_name de kolom.
	 * @param int    $user_id het user_id.
	 * @return string
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function manage_users_column_content( $empty, $column_name, $user_id ) {

		if ( 'kleistad_user_disabled' === $column_name ) {
			if ( intval( get_the_author_meta( 'kleistad_disable_user', $user_id ) ) === 1 ) {
				return 'Gedeactiveerd';
			}
		}
		return '';
	}

	/**
	 * De breedte van de column
	 *
	 * @since 4.0.87
	 */
	public function manage_users_css() {
		echo '<style type="text/css">.column-kleistad_user_disabled { width: 80px; }</style>';
	}

	/**
	 * Registreer de exporter van privacy gevoelige data.
	 *
	 * @since 4.3.0
	 *
	 * @param array $exporters De exporters die WP aanroept bij het genereren van de zip file.
	 */
	public function register_exporter( $exporters ) {
		$exporters['kleistad'] = [
			'exporter_friendly_name' => 'plugin folder Kleistad',
			'callback'               => [ get_class(), 'exporter' ],
		];
		return $exporters;
	}

	/**
	 * Registreer de eraser van privacy gevoelige data.
	 *
	 * @since 4.3.0
	 *
	 * @param array $erasers De erasers die WP aanroept bij het verwijderen persoonlijke data.
	 */
	public function register_eraser( $erasers ) {
		$erasers['kleistad'] = [
			'eraser_friendly_name' => 'plugin folder Kleistad',
			'callback'             => [ get_class(), 'eraser' ],
		];
		return $erasers;
	}

	/**
	 * Exporteer persoonlijke data.
	 *
	 * @since 4.3.0
	 *
	 * @param string $email Het email adres van de te exporteren persoonlijke data.
	 * @param int    $page  De pagina die opgevraagd wordt.
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public static function exporter( $email, $page = 1 ) {
		$export_items = [];
		$gebruiker_id = email_exists( $email );
		if ( $gebruiker_id ) {
			$gebruiker    = get_userdata( $gebruiker_id );
			$export_items = array_merge(
				[
					[
						'group_id'    => 'contactinfo',
						'group_label' => 'Contact informatie',
						'item_id'     => 'contactinfo',
						'data'        => [
							[
								'name'  => 'Telefoonnummer',
								'value' => $gebruiker->telnr,
							],
							[
								'name'  => 'Straat',
								'value' => $gebruiker->straat,
							],
							[
								'name'  => 'Nummer',
								'value' => $gebruiker->huisnr,
							],
							[
								'name'  => 'Postcode',
								'value' => $gebruiker->pcode,
							],
							[
								'name'  => 'Plaats',
								'value' => $gebruiker->plaats,
							],
						],
					],
				],
				Kleistad_Inschrijving::export( $gebruiker_id ),
				Kleistad_Abonnement::export( $gebruiker_id ),
				Kleistad_Saldo::export( $gebruiker_id ),
				Kleistad_Reservering::export( $gebruiker_id )
			);
		}
		// Geef aan of er nog meer te exporteren valt.
		$done = true; // Criterium nog vast te stellen.
		return [
			'data' => $export_items,
			'done' => $done,
		];
	}

	/**
	 * Erase / verwijder persoonlijke data.
	 *
	 * @since 4.3.0
	 *
	 * @param string $email Het email adres van de te verwijderen persoonlijke data.
	 * @param int    $page  De pagina die opgevraagd wordt.
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public static function eraser( $email, $page = 1 ) {
		$count        = 0;
		$gebruiker_id = email_exists( $email );
		if ( $gebruiker_id ) {
			update_user_meta( $gebruiker_id, 'telnr', '******' );
			update_user_meta( $gebruiker_id, 'straat', '******' );
			update_user_meta( $gebruiker_id, 'huisnr', '******' );
			update_user_meta( $gebruiker_id, 'pcode', '******' );
			update_user_meta( $gebruiker_id, 'plaats', '******' );
			$count = 5 +
				Kleistad_Abonnement::erase( $gebruiker_id ) +
				Kleistad_Inschrijving::erase( $gebruiker_id ) +
				Kleistad_Reservering::erase( $gebruiker_id ) +
				Kleistad_Saldo::erase( $gebruiker_id );
		}
		return [
			'items_removed'  => $count,
			'items_retained' => false,
			'messages'       => [],
			'done'           => ( 0 < $count ),
		];
	}

	/**
	 * Auto update van de plugin via het administrator board.
	 *
	 * @since 4.3.8
	 *
	 * @param  object $transient Het object waarin WP de updates deelt.
	 * @return object De transient.
	 */
	public function check_update( $transient ) {
		if ( ! empty( $transient->checked ) ) {
			$obj = $this->get_remote( 'version' );
			if ( false !== $obj ) {
				if ( version_compare( $this->version, $obj->new_version, '<' ) ) {
					$transient->response[ $obj->plugin ] = $obj;
				} else {
					$transient->no_update[ $obj->plugin ] = $obj;
				}
			}
		}
		return $transient;
	}

	/**
	 * Haal informatie op, aangeroepen vanuit API plugin hook.
	 *
	 * @since 4.3.8
	 *
	 * @param  object $obj Wordt niet gebruikt.
	 * @param  string $action De gevraagde actie.
	 * @param  object $arg Argument door WP ingevuld.
	 * @return bool|object
	 */
	public function check_info( $obj, $action = '', $arg = null ) {
		if ( ( 'query_plugins' === $action || 'plugin_information' === $action ) &&
			isset( $arg->slug ) && $arg->slug === $this->plugin_name ) {
			$plugin_info  = get_site_transient( 'update_plugins' );
			$arg->version = $plugin_info->checked[ $this->plugin_name . '/' . $this->plugin_name . '.php' ];
			$info         = $this->get_remote( 'info' );
			if ( false !== $info ) {
				return $info;
			}
		}
		return $obj;
	}

	/**
	 * Haal de info bij de update server op.
	 *
	 * @since 4.3.8
	 *
	 * @param  string $action De gevraagde actie.
	 * @return bool|object remote info.
	 */
	public function get_remote( $action = '' ) {
		$params  = [
			'body' => [
				'action' => $action,
			],
		];
		$request = wp_remote_post( 'http://sprako.xs4all.nl/kleistad_plugin/update.php', $params );
		if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			// phpcs:ignore
			return @unserialize( $request['body'] );
		}
		return false;
	}

	/**
	 * Aangeroepen na update van de kleistad opties.
	 *
	 * @param array $oud Oude waarde.
	 * @param array $nieuw Nieuwe waarde.
	 * @since 5.0.0
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function opties_gewijzigd( $oud, $nieuw ) {
		if ( $oud['google_sleutel'] !== $nieuw['google_sleutel'] ||
			$oud['google_client_id'] !== $nieuw['google_client_id'] ) {
			delete_option( Kleistad_Event::ACCESS_TOKEN );
		}
	}

	/**
	 * Registreer de kleistad settings, uitgevoerd tijdens admin init.
	 *
	 * @since   4.0.87
	 */
	public function register_settings() {
		register_setting( 'kleistad-opties', 'kleistad-opties', [ $this, 'validate_settings' ] );
	}

	/**
	 * Toon de instellingen page van de plugin.
	 *
	 * @since    4.0.87
	 */
	public function display_settings_page() {
		add_meta_box( 'kleistad_instellingen_form_meta_box', 'Instellingen', [ $this, 'instellingen_form_meta_box_handler' ], 'instellingen', 'normal', 'default' );
		add_meta_box( 'kleistad_google_connect_meta_box', 'Connect Google Kalender', [ $this, 'google_connect_meta_box_handler' ], 'google_connect', 'normal', 'default' );
		add_meta_box( 'kleistad_shortcodes_meta_box', 'Gebruik van de plugin', [ $this, 'shortcodes_meta_box_handler' ], 'shortcodes', 'normal', 'default' );
		add_meta_box( 'kleistad_email_parameters_meta_box', 'E-Mail Parameters', [ $this, 'email_parameters_meta_box_handler' ], 'email_parameters', 'normal', 'default' );

		require 'partials/kleistad-admin-display-settings.php';
	}

	/**
	 * Toon de custom meta box met de instellingen
	 *
	 * @since    4.0.87
	 */
	public function instellingen_form_meta_box_handler() {
		require 'partials/kleistad-admin-instellingen-form-meta-box.php';
	}

	/**
	 * Toon het overzicht van de shortcodes in een meta box
	 *
	 * @since    4.0.87
	 */
	public function shortcodes_meta_box_handler() {
		require 'partials/kleistad-admin-shortcodes-meta-box.php';
	}

	/**
	 * Toon de emails en hun parameters in een meta box
	 *
	 * @since    4.0.87
	 */
	public function email_parameters_meta_box_handler() {
		require 'partials/kleistad-admin-email-parameters-meta-box.php';
	}

	/**
	 * Toon de emails en hun parameters in een meta box
	 *
	 * @since    5.0.0
	 * @suppress PhanUnusedVariable
	 */
	public function google_connect_meta_box_handler() {
		$result = true;
		if ( ! is_null( filter_input( INPUT_POST, 'connect' ) ) ) {
			Kleistad_Event::vraag_google_service_aan( admin_url( 'admin.php?page=kleistad&tab=google_connect' ) );
		}
		if ( ! is_null( filter_input( INPUT_GET, 'code' ) ) ) {
			$result = Kleistad_Event::koppel_google_service();
		}
		require 'partials/kleistad-admin-google-connect-meta-box.php';
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
	 * Ovens overzicht page handler
	 *
	 * @since    4.0.87
	 * @suppress PhanUnusedVariable
	 */
	public function ovens_page_handler() {
		$message = '';
		$table   = new Kleistad_Admin_Ovens();
		require 'partials/kleistad-admin-ovens-page.php';
	}

	/**
	 * Toon en verwerk oven gegevens
	 *
	 * @since    4.0.87
	 * @suppress PhanUnusedVariable
	 */
	public function ovens_form_page_handler() {
		$message = '';
		$notice  = '';
		$item    = [];
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_oven' ) ) {
			$item       = filter_input_array(
				INPUT_POST,
				[
					'id'              => FILTER_SANITIZE_NUMBER_INT,
					'naam'            => FILTER_SANITIZE_STRING,
					'kosten'          => [
						'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
						'flags'  => FILTER_FLAG_ALLOW_FRACTION,
					],
					'beschikbaarheid' => [
						'filter' => FILTER_SANITIZE_STRING,
						'flags'  => FILTER_FORCE_ARRAY,
					],
				]
			);
			$item_valid = $this->validate_oven( $item );
			if ( true === $item_valid ) {
				if ( $item['id'] > 0 ) {
					$oven = new Kleistad_Oven( $item['id'] );
				} else {
					$oven = new Kleistad_Oven();
				}
				$oven->naam            = $item['naam'];
				$oven->kosten          = $item['kosten'];
				$oven->beschikbaarheid = $item['beschikbaarheid'];
				$oven->save();
				$message = 'De gegevens zijn opgeslagen';
			} else {
				$notice = $item_valid;
			}
		} else {
			if ( isset( $_REQUEST['id'] ) ) {
				$oven = new Kleistad_Oven( $_REQUEST['id'] );
			} else {
				$oven = new Kleistad_Oven();
			}
			$item['id']              = $oven->id;
			$item['naam']            = $oven->naam;
			$item['kosten']          = $oven->kosten;
			$item['beschikbaarheid'] = $oven->beschikbaarheid;
		}
		add_meta_box( 'ovens_form_meta_box', 'Ovens', [ $this, 'ovens_form_meta_box_handler' ], 'oven', 'normal', 'default' );
		require 'partials/kleistad-admin-ovens-form-page.php';
	}

	/**
	 * Toon het oven formulier in een meta box
	 *
	 * @param array $item de oven.
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function ovens_form_meta_box_handler( $item ) {
		require 'partials/kleistad-admin-ovens-form-meta-box.php';
	}

	/**
	 * Valideer de oven
	 *
	 * @param array $item de oven.
	 * @return bool|string
	 */
	private function validate_oven( $item ) {
		$messages = [];

		if ( empty( $item['naam'] ) ) {
			$messages[] = 'Naam is verplicht';
		}
		if ( ! empty( $item['kosten'] ) && ! is_numeric( $item['kosten'] ) ) {
			$messages[] = 'Kosten format is fout';
		}
		if ( ! empty( $item['kosten'] ) && ! absint( intval( $item['kosten'] ) ) ) {
			$messages[] = 'Kosten kunnen niet kleiner zijn dan 0';
		}
		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br />', $messages );
	}

	/**
	 * Abonnees overzicht page handler
	 *
	 * @since    4.3.0
	 * @suppress PhanUnusedVariable
	 */
	public function abonnees_page_handler() {
		$message = '';
		$table   = new Kleistad_Admin_Abonnees();
		require 'partials/kleistad-admin-abonnees-page.php';
	}

	/**
	 * Toon en verwerk ingevoerde abonnee gegevens
	 *
	 * @since    4.3.0
	 * @suppress PhanUnusedVariable
	 */
	public function abonnees_form_page_handler() {
		$message = '';
		$notice  = '';
		$actie   = null;
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_abonnee' ) ) {
			$item = filter_input_array(
				INPUT_POST,
				[
					'id'              => FILTER_SANITIZE_NUMBER_INT,
					'naam'            => FILTER_SANITIZE_STRING,
					'code'            => FILTER_SANITIZE_STRING,
					'soort'           => FILTER_SANITIZE_STRING,
					'dag'             => FILTER_SANITIZE_STRING,
					'gestart'         => FILTER_SANITIZE_NUMBER_INT,
					'geannuleerd'     => FILTER_SANITIZE_NUMBER_INT,
					'gepauzeerd'      => FILTER_SANITIZE_NUMBER_INT,
					'inschrijf_datum' => FILTER_SANITIZE_STRING,
					'start_datum'     => FILTER_SANITIZE_STRING,
					'pauze_datum'     => FILTER_SANITIZE_STRING,
					'eind_datum'      => FILTER_SANITIZE_STRING,
					'herstart_datum'  => FILTER_SANITIZE_STRING,
					'mandaat'         => FILTER_SANITIZE_NUMBER_INT,
					'extras'          => [
						'filter' => FILTER_SANITIZE_STRING,
						'flags'  => FILTER_REQUIRE_ARRAY,
					],
					'actie'           => FILTER_SANITIZE_STRING,
					'submit'          => FILTER_SANITIZE_STRING,
				]
			);
			if ( ! is_array( $item['extras'] ) ) {
				$item['extras'] = [];
			}
			$actie      = $item['actie'];
			$submit     = strtolower( $item['submit'] );
			$item_valid = $this->validate_abonnee( $item, $actie, $submit );
			if ( true === $item_valid ) {
				$datum               = mktime( 0, 0, 0, intval( date( 'n' ) ) + 1, 1, intval( date( 'Y' ) ) );
				$abonnement          = new Kleistad_Abonnement( $item['id'] );
				$vandaag             = strtotime( 'today' );
				$item['mollie_info'] = $abonnement->info();
				switch ( $actie ) {
					case 'status':
						switch ( $submit ) {
							case 'pauzeren':
								$abonnement->pauzeren( strtotime( $item['pauze_datum'] ), strtotime( $item['herstart_datum'] ), true );
								$item['gepauzeerd'] = $vandaag >= $item['pauze_datum'];
								break;
							case 'herstarten':
								$abonnement->herstarten( strtotime( $item['herstart_datum'] ), true );
								$item['gepauzeerd'] = $vandaag < $item['herstart_datum'];
								break;
							case 'starten':
								$abonnement->start( strtotime( $item['start_datum'] ), 'stort', true );
								$item['gestart'] = $vandaag >= $item['start_datum'];
								break;
							case 'stoppen':
								$abonnement->annuleren( strtotime( $item['eind_datum'] ), true );
								$item['geannuleerd'] = $vandaag >= $item['eind_datum'];
								break;
						}
						break;
					case 'soort':
						if ( ( $abonnement->soort !== $item['soort'] ) || ( $abonnement->dag !== $item['dag'] ) ) {
							$abonnement->wijzigen( $vandaag, $item['soort'], $item['dag'], true );
						}
						break;
					case 'extras':
						if ( $abonnement->extras !== $item['extras'] ) {
							$abonnement->wijzigen( $vandaag, $item['extras'], '', true );
						}
						break;
					case 'mollie':
						if ( $item['mandaat'] ) {
							$abonnement->betaalwijze( $vandaag, 'stort', true );
							$item['mandaat'] = false;
						}
						break;
					default:
						break;
				}
				$message = 'De gegevens zijn opgeslagen';
			} else {
				$notice = $item_valid;
			}
		} else {
			if ( isset( $_REQUEST['id'] ) ) {
				$abonnee_id = $_REQUEST['id'];
				$actie      = $_REQUEST['actie'];
				$abonnement = new Kleistad_Abonnement( $abonnee_id );
				$abonnee    = get_userdata( $abonnee_id );
				$item       = [
					'id'              => $abonnee_id,
					'naam'            => $abonnee->display_name,
					'soort'           => $abonnement->soort,
					'dag'             => ( 'beperkt' === $abonnement->soort ? $abonnement->dag : '' ),
					'code'            => $abonnement->code,
					'extras'          => $abonnement->extras,
					'geannuleerd'     => $abonnement->geannuleerd,
					'gepauzeerd'      => $abonnement->gepauzeerd,
					'gestart'         => Kleistad_Roles::reserveer( $abonnee_id ),
					'inschrijf_datum' => ( $abonnement->datum ? strftime( '%d-%m-%Y', $abonnement->datum ) : '' ),
					'start_datum'     => ( $abonnement->start_datum ? strftime( '%d-%m-%Y', $abonnement->start_datum ) : '' ),
					'pauze_datum'     => ( $abonnement->pauze_datum ? strftime( '%d-%m-%Y', $abonnement->pauze_datum ) : '' ),
					'eind_datum'      => ( $abonnement->eind_datum ? strftime( '%d-%m-%Y', $abonnement->eind_datum ) : '' ),
					'herstart_datum'  => ( $abonnement->herstart_datum ? strftime( '%d-%m-%Y', $abonnement->herstart_datum ) : '' ),
					'mandaat'         => ( '' !== $abonnement->subscriptie_id ),
					'mollie_info'     => $abonnement->info(),
				];
			}
		}
		add_meta_box( 'abonnees_form_meta_box', 'Abonnees', [ $this, 'abonnees_form_meta_box_handler' ], 'abonnee', 'normal', 'default', [ $actie ] );
		require 'partials/kleistad-admin-abonnees-form-page.php';
	}

	/**
	 * Toon de abonnees form meta box
	 *
	 * @since    4.3.0
	 *
	 * @param array $item de abonnee.
	 * @param array $request de aanroep parameters.
	 * @suppress PhanUnusedPublicMethodParameter
	 * @suppress PhanUnusedVariable
	 */
	public function abonnees_form_meta_box_handler( $item, $request ) {
		$actie = $request['args'][0];
		require 'partials/kleistad-admin-abonnees-form-meta-box.php';
	}

	/**
	 * Valideer de abonnee
	 *
	 * @since    4.3.0
	 *
	 * @param array  $item de abonnee.
	 * @param string $actie de actie waar het om gaat.
	 * @param string $submit de subactie.
	 * @return bool|string
	 * @suppress PhanUnusedPrivateMethodParameter
	 */
	private function validate_abonnee( $item, $actie, $submit ) {
		$messages = [];

		if ( 'status' === $actie ) {
			switch ( $submit ) {
				case 'pauzeren':
					if ( false === strtotime( $item['pauze_datum'] ) ) {
						$messages[] = 'Pauze datum ontbreekt of is ongeldig';
					}
					// Bij pauzeren moet herstart_datum ook getest worden.
				case 'herstarten':
					if ( false === strtotime( $item['herstart_datum'] ) ) {
						$messages[] = 'Herstart datum ontbreekt of is ongeldig';
					}
					break;
				case 'starten':
					if ( false === strtotime( $item['start_datum'] ) ) {
						$messages[] = 'Start datum ontbreekt of is ongeldig';
					}
					break;
				case 'stoppen':
					if ( false === strtotime( $item['eind_datum'] ) ) {
						$messages[] = 'Eind datum ontbreekt of is ongeldig';
					}
					break;
				default:
					break;
			}
		}

		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br />', $messages );
	}

	/**
	 * Cursisten overzicht page handler
	 *
	 * @since    4.5.0
	 * @suppress PhanUnusedVariable
	 */
	public function cursisten_page_handler() {
		$message = '';
		$table   = new Kleistad_Admin_Cursisten();
		require 'partials/kleistad-admin-cursisten-page.php';
	}

	/**
	 * Toon en verwerk ingevoerde cursist gegevens
	 *
	 * @since    4.5.0
	 * @suppress PhanUnusedVariable
	 */
	public function cursisten_form_page_handler() {
		$message = '';
		$notice  = '';
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_cursist' ) ) {
			$item       = filter_input_array(
				INPUT_POST,
				[
					'id'          => FILTER_SANITIZE_STRING,
					'naam'        => FILTER_SANITIZE_STRING,
					'cursus_id'   => FILTER_SANITIZE_NUMBER_INT,
					'i_betaald'   => FILTER_SANITIZE_NUMBER_INT,
					'c_betaald'   => FILTER_SANITIZE_NUMBER_INT,
					'aantal'      => FILTER_SANITIZE_NUMBER_INT,
					'geannuleerd' => FILTER_SANITIZE_NUMBER_INT,
				]
			);
			$item_valid = $this->validate_cursist( $item );
			if ( true === $item_valid ) {
				$code                      = $item['id'];
				$parameters                = explode( '-', substr( $code, 1 ) );
				$cursus_id                 = intval( $parameters[0] );
				$cursist_id                = intval( $parameters[1] );
				$inschrijving              = new Kleistad_Inschrijving( $cursist_id, $cursus_id );
				$inschrijving->i_betaald   = ( 0 !== intval( $item['i_betaald'] ) );
				$inschrijving->c_betaald   = ( 0 !== intval( $item['c_betaald'] ) );
				$inschrijving->geannuleerd = ( 0 !== intval( $item['geannuleerd'] ) );
				$inschrijving->aantal      = $item['aantal'];
				if ( intval( $item['cursus_id'] ) !== $cursus_id ) {
					// cursus gewijzigd.
					$inschrijving->correct( $item['cursus_id'] );
				} else {
					// attributen inschrijving gewijzigd.
					$inschrijving->save();
				}
				$message = 'De gegevens zijn opgeslagen';
			} else {
				$notice = $item_valid;
			}
		} else {
			if ( isset( $_REQUEST['id'] ) ) {
				$code         = $_REQUEST['id'];
				$parameters   = explode( '-', substr( $code, 1 ) );
				$cursus_id    = intval( $parameters[0] );
				$cursist_id   = intval( $parameters[1] );
				$cursist      = get_userdata( $cursist_id );
				$inschrijving = new Kleistad_Inschrijving( $cursist_id, $cursus_id );
				$cursus       = new Kleistad_Cursus( $cursus_id );
				$item         = [
					'id'          => $code,
					'naam'        => $cursist->display_name,
					'aantal'      => $inschrijving->aantal,
					'i_betaald'   => $inschrijving->i_betaald,
					'c_betaald'   => $inschrijving->c_betaald,
					'geannuleerd' => $inschrijving->geannuleerd,
					'cursist_id'  => $cursist_id,
					'cursus_id'   => $cursus_id,
				];
			}
		}
		add_meta_box( 'cursisten_form_meta_box', 'Cursisten', [ $this, 'cursisten_form_meta_box_handler' ], 'cursist', 'normal', 'default' );
		require 'partials/kleistad-admin-cursisten-form-page.php';
	}

	/**
	 * Toon de cursisten form meta box
	 *
	 * @since    4.5.0
	 *
	 * @param array $item de cursist.
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function cursisten_form_meta_box_handler( $item ) {
		require 'partials/kleistad-admin-cursisten-form-meta-box.php';
	}

	/**
	 * Valideer de cursist
	 *
	 * @since    4.5.0
	 *
	 * @param array $item de cursist.
	 * @return bool|string
	 * @suppress PhanUnusedPrivateMethodParameter
	 */
	private function validate_cursist( $item ) {
		$messages = [];

		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br />', $messages );
	}

	/**
	 * Overzicht regelingen page handler
	 *
	 * @since    4.0.87
	 * @suppress PhanUnusedVariable
	 */
	public function regelingen_page_handler() {
		$message = '';
		$table   = new Kleistad_Admin_Regelingen();
		if ( 'delete' === $table->current_action() ) {
			$id = filter_input( INPUT_GET, 'id' );

			if ( ! is_null( $id ) ) {
				list($gebruiker_id, $oven_id) = sscanf( $id, '%d-%d' );
				$regelingen                   = new Kleistad_Regelingen();
				$regelingen->delete_and_save( $gebruiker_id, $oven_id );
			}
			$message = sprintf( 'Aantal verwijderd: %d', count( $id ) );
		}
		$table->prepare_items();

		require 'partials/kleistad-admin-regelingen-page.php';
	}

	/**
	 * Toon en verwerk regelingen
	 *
	 * @since    4.0.87
	 * @suppress PhanUnusedVariable
	 */
	public function regelingen_form_page_handler() {

		$message = '';
		$notice  = '';

		$default = [
			'id'             => '',
			'gebruiker_id'   => 0,
			'oven_id'        => 0,
			'oven_naam'      => '',
			'gebruiker_naam' => '',
			'kosten'         => 0,
		];

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_regeling' ) ) {
			$item       = wp_parse_args( $_REQUEST, $default );
			$item_valid = $this->validate_regeling( $item );
			if ( true === $item_valid ) {
				$regelingen = new Kleistad_Regelingen();
				$result     = $regelingen->set_and_save( $item['gebruiker_id'], $item['oven_id'], $item['kosten'] );
				if ( '' === $item['id'] ) {
					if ( $result ) {
						$message = 'De regeling is bewaard';
					} else {
						$notice = 'Er was een probleem met het opslaan van gegevens';
					}
				} else {
					if ( $result ) {
						$message = 'De regeling is gewijzigd';
					} else {
						$notice = 'Er was een probleem met het wijzigen van gegevens';
					}
				}
				$oven                   = new Kleistad_Oven( $item['oven_id'] );
				$gebruiker              = get_userdata( $item['gebruiker_id'] );
				$item['gebruiker_naam'] = $gebruiker->display_name;
				$item['oven_naam']      = $oven->naam;
			} else {
				$notice = $item_valid;
			}
		} else {
			$item = $default;
			if ( isset( $_REQUEST['id'] ) ) {
				list($gebruiker_id, $oven_id) = sscanf( $_REQUEST['id'], '%d-%d' );
				$regelingen                   = new Kleistad_Regelingen();
				$gebruiker_regeling           = $regelingen->get( $gebruiker_id, $oven_id );

				$gebruiker = get_userdata( $gebruiker_id );
				$oven      = new Kleistad_Oven( $oven_id );
				$item      = [
					'id'             => $_REQUEST['id'],
					'gebruiker_id'   => $gebruiker_id,
					'gebruiker_naam' => $gebruiker->display_name,
					'oven_id'        => $oven_id,
					'oven_naam'      => $oven->naam,
					'kosten'         => $gebruiker_regeling,
				];
			}
		}
		add_meta_box( 'regelingen_form_meta_box', 'Regelingen', [ $this, 'regelingen_form_meta_box_handler' ], 'regeling', 'normal', 'default' );

		require 'partials/kleistad-admin-regelingen-form-page.php';
	}

	/**
	 * Toon de regeling meta box
	 *
	 * @since    4.0.87
	 *
	 * @param array $item de regeling.
	 * @suppress PhanUnusedPublicMethodParameter, PhanUnusedVariable
	 */
	public function regelingen_form_meta_box_handler( $item ) {
		$gebruikers = get_users(
			[
				'fields'  => [ 'ID', 'display_name' ],
				'orderby' => [ 'display_name' ],
			]
		);
		$ovens      = Kleistad_Oven::all();

		require 'partials/kleistad-admin-regelingen-form-meta-box.php';
	}

	/**
	 * Valideer de regeling
	 *
	 * @since    4.0.87
	 *
	 * @param array $item the regeling.
	 * @return bool|string
	 */
	private function validate_regeling( $item ) {
		$messages = [];
		if ( ! empty( $item['gebruiker_id'] ) && ! is_numeric( $item['gebruiker_id'] ) ) {
			$messages[] = 'Geen gebruiker gekozen';
		}
		if ( ! empty( $item['oven_id'] ) && ! is_numeric( $item['oven_id'] ) ) {
			$messages[] = 'Geen oven gekozen';
		}
		if ( ! empty( $item['kosten'] ) && ! is_numeric( $item['kosten'] ) ) {
			$messages[] = 'Kosten format is fout';
		}
		if ( ! empty( $item['kosten'] ) && ! 0.0 <= (float) $item['kosten'] ) {
			$messages[] = 'Kosten kunnen niet kleiner zijn dan 0';
		}
		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br />', $messages );
	}

	/**
	 * Overzicht stooksaldo page handler
	 *
	 * @since    4.0.87
	 * @suppress PhanUnusedVariable
	 */
	public function stooksaldo_page_handler() {
		$message = '';
		$table   = new Kleistad_Admin_Stooksaldo();
		require 'partials/kleistad-admin-stooksaldo-page.php';
	}

	/**
	 * Toon en verwerk stooksaldo
	 *
	 * @since    4.0.87
	 * @suppress PhanUnusedPublicMethodParameter, PhanUnusedVariable
	 */
	public function stooksaldo_form_page_handler() {

		$message = '';
		$notice  = '';

		$default = [
			'id'    => 0,
			'saldo' => 0,
			'naam'  => '',
		];

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_stooksaldo' ) ) {
			$item       = wp_parse_args( $_REQUEST, $default );
			$item_valid = $this->validate_stooksaldo( $item );

			if ( true === $item_valid ) {
				$saldo         = new Kleistad_Saldo( $item['id'] );
				$saldo->bedrag = $item['saldo'];
				$beheerder     = wp_get_current_user();
				$saldo->save( 'correctie door ' . $beheerder->display_name );
			} else {
				$notice = $item_valid;
			}
		} else {
			$item = $default;
			if ( isset( $_REQUEST['id'] ) ) {
				$gebruiker = get_userdata( $_REQUEST['id'] );
				if ( ! $gebruiker ) {
					$item   = $default;
					$notice = 'De gebruiker is niet gevonden';
				} else {
					$saldo = new Kleistad_saldo( $_REQUEST['id'] );
					$item  = [
						'id'    => $_REQUEST['id'],
						'naam'  => $gebruiker->display_name,
						'saldo' => $saldo->bedrag,
					];
				}
			}
		}
		add_meta_box( 'stooksaldo_form_meta_box', 'Stooksaldo', [ $this, 'stooksaldo_form_meta_box_handler' ], 'stooksaldo', 'normal', 'default' );

		require 'partials/kleistad-admin-stooksaldo-form-page.php';
	}

	/**
	 * Toon de stooksaldo meta box
	 *
	 * @since    4.0.87
	 *
	 * @param array $item de stooksaldo.
	 * @suppress PhanUnusedPublicMethodParameter
	 */
	public function stooksaldo_form_meta_box_handler( $item ) {
		require 'partials/kleistad-admin-stooksaldo-form-meta-box.php';
	}

	/**
	 * Valideer de stooksaldo
	 *
	 * @since    4.0.87
	 *
	 * @param array $item de stooksaldo.
	 * @return bool|string
	 */
	private function validate_stooksaldo( $item ) {
		$messages = [];

		if ( ! empty( $item['saldo'] ) && ! is_numeric( $item['saldo'] ) ) {
			$messages[] = 'Kosten format is fout';
		}

		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br />', $messages );
	}

}
