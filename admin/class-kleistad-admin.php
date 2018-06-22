<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.87
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

/**
 * The admin-specific functionality of the plugin.
 */
class Kleistad_Admin {

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
	 *  The plugin options
	 *
	 * @since     4.0.87
	 * @access    private
	 * @var       array     $options  the plugin options
	 */
	private $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.87
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->options     = get_option( 'kleistad-opties' );
		date_default_timezone_set( 'Europe/Amsterdam' );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    4.0.87
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/kleistad-admin.css', [], $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    4.0.87
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/kleistad-admin.js', [ 'jquery' ], $this->version, false );
	}

	/**
	 * Define the admin panels
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

	}

	/**
	 * Add the field to user profiles
	 *
	 * @since 4.0.87
	 * @param object $user unused.
	 */
	public function use_profile_field( $user ) {
		// Only show this option to users who can delete other users.
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}
		require 'partials/kleistad-admin-use-profile-field.php';
	}

	/**
	 * Saves the custom field to user meta
	 *
	 * @since 4.0.87
	 * @param int $user_id unused.
	 */
	public function user_profile_field_save( $user_id ) {
		// Only worry about saving this field if the user has access.
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}
		$disabled_val = filter_input( INPUT_POST, 'kleistad_disable_user' );
		$disabled     = ! is_null( $disabled_val ) ? $disabled_val : 0;
		update_user_meta( $user_id, 'kleistad_disable_user', $disabled );
	}

	/**
	 * Saves the custom field to user meta
	 *
	 * @since 4.0.87
	 * @param array  $errors unused.
	 * @param int    $update unused.
	 * @param object $user unused.
	 */
	public function check_role( &$errors, $update, &$user ) {
		if ( ( get_the_author_meta( 'kleistad_disable_user', $user->ID ) === 1 ) ) {
			$user->role = '';
		}
	}

	/**
	 * Add custom disabled column to users list
	 *
	 * @since 4.0.87
	 * @param array $defaults default settings for user.
	 * @return array
	 */
	public function manage_users_columns( $defaults ) {
		$defaults['kleistad_user_disabled'] = 'Gedeactiveerd';
		return $defaults;
	}

	/**
	 * Set content of disabled users column
	 *
	 * @since 4.0.87
	 * @param empty  $empty unused.
	 * @param string $column_name the column involved.
	 * @param int    $user_id the user_id.
	 * @return string
	 */
	public function manage_users_column_content( $empty, $column_name, $user_id ) {

		if ( 'kleistad_user_disabled' === $column_name ) {
			if ( get_the_author_meta( 'kleistad_disable_user', $user_id ) === 1 ) {
				return 'Gedeactiveerd';
			}
		}
	}

	/**
	 * Specifiy the width of our custom column
	 *
	 * @since 4.0.87
	 */
	public function manage_users_css() {
		echo '<style type="text/css">.column-kleistad_user_disabled { width: 80px; }</style>';
	}

	/**
	 * Registreer de exporter van privacy gevoelige data.
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
	 * @param string $email Het email adres van de te exporteren persoonlijke data.
	 * @param int    $page  De pagina die opgevraagd wordt.
	 */
	public static function exporter( $email, $page = 1 ) {
		$export_items = [];
		$gebruiker_id = email_exists( $email );
		if ( $gebruiker_id ) {
			$export_items = array_merge(
				Kleistad_Gebruiker::export( $gebruiker_id ),
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
	 * @param string $email Het email adres van de te verwijderen persoonlijke data.
	 * @param int    $page  De pagina die opgevraagd wordt.
	 */
	public static function eraser( $email, $page = 1 ) {
		$count        = 0;
		$gebruiker_id = email_exists( $email );
		if ( $gebruiker_id ) {
			$count =
				Kleistad_Abonnement::erase( $gebruiker_id ) +
				Kleistad_Inschrijving::erase( $gebruiker_id ) +
				Kleistad_Reservering::erase( $gebruiker_id ) +
				Kleistad_Saldo::erase( $gebruiker_id ) +
				Kleistad_Gebruiker::erase( $gebruiker_id );
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
	 * @param  object $transient Het object waarin WP de updates deelt.
	 * @return object De transient.
	 */
	public function check_update( $transient ) {
		if ( ! empty( $transient->checked ) ) {
			$obj = $this->get_remote( 'version' );
			if ( version_compare( $this->version, $obj->new_version, '<' ) ) {
				$transient->response[ $obj->plugin ] = $obj;
			} else {
				$transient->no_update[ $obj->plugin ] = $obj;
			}
		}
		return $transient;
	}

	/**
	 * Haal informatie op, aangeroepen vanuit API plugin hook.
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
			return $this->get_remote( 'info' );
		}
		return $obj;
	}

	/**
	 * Haal de info bij de update server op.
	 *
	 * @param  string $action De gevraagde actie.
	 * @return string remote info.
	 */
	public function get_remote( $action = '' ) {
		$params  = [
			'body' => [
				'action' => $action,
			],
		];
		$request = wp_remote_post( 'http://localhost/kleistad_update/Kleistad-update-server/update.php', $params );
		if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
			return @unserialize( $request['body'] ); // phpcs:ignore.
		}
		return false;
	}

	/**
	 * Register the settings
	 *
	 * @since   4.0.87
	 */
	public function register_settings() {
		register_setting( 'kleistad-opties', 'kleistad-opties', [ $this, 'validate_settings' ] );
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    4.0.87
	 */
	public function display_settings_page() {
		add_meta_box( 'kleistad_instellingen_form_meta_box', 'Instellingen', [ $this, 'instellingen_form_meta_box_handler' ], 'instellingen', 'normal', 'default' );
		add_meta_box( 'kleistad_shortcodes_meta_box', 'Gebruik van de plugin', [ $this, 'shortcodes_meta_box_handler' ], 'shortcodes', 'normal', 'default' );
		add_meta_box( 'kleistad_email_parameters_meta_box', 'E-Mail Parameters', [ $this, 'email_parameters_meta_box_handler' ], 'email_parameters', 'normal', 'default' );

		require 'partials/kleistad-admin-display-settings.php';
	}

	/**
	 * This function renders our custom meta box
	 */
	public function instellingen_form_meta_box_handler() {
		require 'partials/kleistad-admin-instellingen-form-meta-box.php';
	}

	/**
	 * This function renders our custom meta box
	 */
	public function shortcodes_meta_box_handler() {
		require 'partials/kleistad-admin-shortcodes-meta-box.php';
	}

	/**
	 * This function renders our custom meta box
	 */
	public function email_parameters_meta_box_handler() {
		require 'partials/kleistad-admin-email-parameters-meta-box.php';
	}


	/**
	 * Validate the settings entered
	 *
	 * @param array $input the settings entered.
	 * @return array  $input
	 */
	public function validate_settings( $input ) {
		foreach ( $input as &$element ) {
			$element = sanitize_text_field( $element );
		}
		return $input;
	}

	/**
	 * List page handler
	 *
	 * @since    4.0.87
	 */
	public function ovens_page_handler() {
		$table = new Kleistad_Admin_Ovens();
		$table->prepare_items();

		$message = '';
		require 'partials/kleistad-admin-ovens-page.php';
	}

	/**
	 * Form page handler checks is there some data posted and tries to save it
	 * Also it renders basic wrapper in which we are callin meta box render
	 *
	 * @since    4.0.87
	 */
	public function ovens_form_page_handler() {
		$message = '';
		$notice  = '';
		// here we are verifying does this request is post back and have correct nonce.
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_oven' ) ) {
			$item = filter_input_array(
				INPUT_POST, [
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
			// validate data, and if all ok save item to database.
			// if id is zero insert otherwise update.
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
				// if $item_valid not true it contains error message(s).
				$notice = $item_valid;
			}
		} else {
			// if this is not post back we load item to edit or give new one to create.
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
		// here we adding our custom meta box.
		add_meta_box( 'ovens_form_meta_box', 'Ovens', [ $this, 'ovens_form_meta_box_handler' ], 'oven', 'normal', 'default' );
		require 'partials/kleistad-admin-ovens-form-page.php';
	}

	/**
	 * This function renders our custom meta box
	 *
	 * @param array $item the oven involved.
	 */
	public function ovens_form_meta_box_handler( $item ) {
		require 'partials/kleistad-admin-ovens-form-meta-box.php';
	}

	/**
	 * Simple function that validates data and retrieve bool on success
	 * and error message(s) on error
	 *
	 * @param array $item the oven involved.
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
	 * List page handler
	 *
	 * @since    4.3.0
	 */
	public function abonnees_page_handler() {
		$table = new Kleistad_Admin_Abonnees();
		$table->prepare_items();

		$message = '';
		require 'partials/kleistad-admin-abonnees-page.php';
	}

	/**
	 * Form page handler checks is there some data posted and tries to save it
	 * Also it renders basic wrapper in which we are callin meta box render
	 *
	 * @since    4.3.0
	 */
	public function abonnees_form_page_handler() {
		$message = '';
		$notice  = '';
		// here we are verifying does this request is post back and have correct nonce.
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_abonnee' ) ) {
			$item = filter_input_array(
				INPUT_POST, [
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
					'incasso_datum'   => FILTER_SANITIZE_STRING,
					'mandaat'         => FILTER_SANITIZE_NUMBER_INT,
				]
			);
			// validate data, and if all ok save item to database.
			// if id is zero insert otherwise update.
			$item_valid = $this->validate_abonnee( $item );
			if ( true === $item_valid ) {
				$datum = mktime( 0, 0, 0, date( 'n' ) + 1, 1, date( 'Y' ) );

				$abonnement = new Kleistad_Abonnement( $item['id'] );
				if ( ( 1 === intval( $item['gepauzeerd'] ) ) !== $abonnement->gepauzeerd ) {
					if ( $abonnement->gepauzeerd ) {
						$abonnement->gepauzeerd = false;
						$abonnement->save();
					} else {
						$abonnement->pauzeren( time(), $datum, true );
					}
				} elseif ( ( 1 === intval( $item['geannuleerd'] ) ) !== $abonnement->geannuleerd ) {
					if ( $abonnement->geannuleerd ) {
						$abonnement->geannuleerd = false;
						$abonnement->save();
					} else {
						$abonnement->annuleren( time(), true );
					}
				} elseif ( ( 1 === intval( $item['gestart'] ) ) !== Kleistad_Roles::reserveer( $item['id'] ) ) {
					$abonnement->start( $abonnement->start_datum, 'stort', true );
				} elseif ( $abonnement->soort !== $item['soort'] ) {
					$abonnement->wijzigen( time(), $item['soort'], $item['dag'], true );
				} elseif ( $abonnement->dag !== $item['dag'] ) {
					$abonnement->dag = $item['dag'];
					$abonnement->save();
				}
				$message = 'De gegevens zijn opgeslagen';
			} else {
				// if $item_valid not true it contains error message(s).
				$notice = $item_valid;
			}
		} else {
			// if this is not post back we load item to edit.
			if ( isset( $_REQUEST['id'] ) ) {
				$abonnee_id = $_REQUEST['id'];
				$abonnement = new Kleistad_Abonnement( $abonnee_id );
				$abonnee    = get_userdata( $abonnee_id );
				$item       = [
					'id'              => $abonnee_id,
					'naam'            => $abonnee->display_name,
					'soort'           => $abonnement->soort,
					'dag'             => ( 'beperkt' === $abonnement->soort ? $abonnement->dag : '' ),
					'code'            => $abonnement->code,
					'geannuleerd'     => $abonnement->geannuleerd,
					'gepauzeerd'      => $abonnement->gepauzeerd,
					'gestart'         => Kleistad_Roles::reserveer( $abonnee_id ),
					'inschrijf_datum' => ( $abonnement->datum ? strftime( '%d-%m-%y', $abonnement->datum ) : '' ),
					'start_datum'     => ( $abonnement->start_datum ? strftime( '%d-%m-%y', $abonnement->start_datum ) : '' ),
					'pauze_datum'     => ( $abonnement->pauze_datum ? strftime( '%d-%m-%y', $abonnement->pauze_datum ) : '' ),
					'eind_datum'      => ( $abonnement->eind_datum ? strftime( '%d-%m-%y', $abonnement->eind_datum ) : '' ),
					'herstart_datum'  => ( $abonnement->herstart_datum ? strftime( '%d-%m-%y', $abonnement->herstart_datum ) : '' ),
					'incasso_datum'   => ( $abonnement->incasso_datum ? strftime( '%d-%m-%y', $abonnement->incasso_datum ) : '' ),
					'mandaat'         => ( '' !== $abonnement->subscriptie_id ),
				];
			}
		}
		// here we adding our custom meta box.
		add_meta_box( 'abonnees_form_meta_box', 'Abonnees', [ $this, 'abonnees_form_meta_box_handler' ], 'abonnee', 'normal', 'default' );
		require 'partials/kleistad-admin-abonnees-form-page.php';
	}

	/**
	 * This function renders our custom meta box
	 *
	 * @param array $item the abonnee involved.
	 */
	public function abonnees_form_meta_box_handler( $item ) {
		require 'partials/kleistad-admin-abonnees-form-meta-box.php';
	}

	/**
	 * Simple function that validates data and retrieve bool on success
	 * and error message(s) on error
	 *
	 * @param array $item the abonnee involved.
	 * @return bool|string
	 */
	private function validate_abonnee( $item ) {
		$messages = [];

		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br />', $messages );
	}

	/**
	 * List page handler
	 *
	 * @since    4.0.87
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
	 * Form page handler checks is there some data posted and tries to save it
	 * Also it renders basic wrapper in which we are callin meta box render
	 *
	 * @since    4.0.87
	 */
	public function regelingen_form_page_handler() {

		$message = '';
		$notice  = '';

		// this is default $item which will be used for new records.
		$default = [
			'id'             => '',
			'gebruiker_id'   => 0,
			'oven_id'        => 0,
			'oven_naam'      => '',
			'gebruiker_naam' => '',
			'kosten'         => 0,
		];

		// here we are verifying does this request is post back and have correct nonce.
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_regeling' ) ) {
			// combine our default item with request params.
			$item = shortcode_atts( $default, $_REQUEST );
			// validate data, and if all ok save item to database.
			// if id is zero insert otherwise update.
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
				// if $item_valid not true it contains error message(s).
				$notice = $item_valid;
			}
		} else {
			// if this is not post back we load item to edit or give new one to create.
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
		// here we adding our custom meta box.
		add_meta_box( 'regelingen_form_meta_box', 'Regelingen', [ $this, 'regelingen_form_meta_box_handler' ], 'regeling', 'normal', 'default' );

		require 'partials/kleistad-admin-regelingen-form-page.php';
	}

	/**
	 * This function renders our custom meta box
	 *
	 * @param arrray $item the regeling.
	 */
	public function regelingen_form_meta_box_handler( $item ) {
		$gebruikers = get_users(
			[
				'fields'  => [ 'id', 'display_name' ],
				'orderby' => [ 'nicename' ],
			]
		);
		$ovens      = Kleistad_Oven::all();

		require 'partials/kleistad-admin-regelingen-form-meta-box.php';
	}

	/**
	 * Simple function that validates data and retrieve bool on success
	 * and error message(s) on error
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
		if ( ! empty( $item['kosten'] ) && ! absint( intval( $item['kosten'] ) ) ) {
			$messages[] = 'Kosten kunnen niet kleiner zijn dan 0';
		}
		if ( empty( $messages ) ) {
			return true;
		}
		return implode( '<br />', $messages );
	}

	/**
	 * List page handler
	 *
	 * @since    4.0.87
	 */
	public function stooksaldo_page_handler() {
		$table = new Kleistad_Admin_Stooksaldo();
		$table->prepare_items();

		$message = '';
		require 'partials/kleistad-admin-stooksaldo-page.php';
	}

	/**
	 * Form page handler checks is there some data posted and tries to save it
	 * Also it renders basic wrapper in which we are callin meta box render
	 *
	 * @since    4.0.87
	 */
	public function stooksaldo_form_page_handler() {

		$message = '';
		$notice  = '';

		$default = [
			'id'    => 0,
			'saldo' => 0,
			'naam'  => '',
		];

		// here we are verifying does this request is post back and have correct nonce.
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_stooksaldo' ) ) {
			// combine our default item with request params.
			$item = shortcode_atts( $default, $_REQUEST );
			// validate data, and if all ok save item to database.
			// if id is zero insert otherwise update.
			$item_valid = $this->validate_stooksaldo( $item );

			if ( true === $item_valid ) {
				$saldo         = new Kleistad_Saldo( $item['id'] );
				$saldo->bedrag = $item['saldo'];
				$beheerder     = wp_get_current_user();
				$saldo->save( 'correctie door ' . $beheerder->display_name );
			} else {
				// if $item_valid not true it contains error message(s).
				$notice = $item_valid;
			}
		} else {
			// if this is not post back we load item to edit or give new one to create.
			$item = $default;
			if ( isset( $_REQUEST['id'] ) ) {
				$gebruiker = get_userdata( $_REQUEST['id'] );
				if ( ! $gebruiker ) {
					$item   = $default;
					$notice = 'De gebruiker is niet gevonden';
				}
				$item          = [
					'id'   => $_REQUEST['id'],
					'naam' => $gebruiker->display_name,
				];
				$saldo         = new Kleistad_saldo( $item['id'] );
				$item['saldo'] = $saldo->bedrag;
			}
		}
		// here we adding our custom meta box.
		add_meta_box( 'stooksaldo_form_meta_box', 'Stooksaldo', [ $this, 'stooksaldo_form_meta_box_handler' ], 'stooksaldo', 'normal', 'default' );

		require 'partials/kleistad-admin-stooksaldo-form-page.php';
	}

	/**
	 * This function renders our custom meta box
	 * $item is row
	 *
	 * @param array $item the stooksaldo.
	 */
	public function stooksaldo_form_meta_box_handler( $item ) {
		require 'partials/kleistad-admin-stooksaldo-form-meta-box.php';
	}

	/**
	 * Simple function that validates data and retrieve bool on success
	 * and error message(s) on error
	 *
	 * @param array $item the stooksaldo.
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
