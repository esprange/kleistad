<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/admin
 */

/**
 * Include the entities
 */
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-entity.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-oven.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-cursus.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-roles.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-kleistad-gebruiker.php';

/**
 * The admin-specific functionality of the plugin.
 */
class Kleistad_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 *  The plugin options
	 *
	 * @since     4.0.0
	 * @access    private
	 * @var       array     $options  the plugin options
	 */
	private $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    4.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/kleistad-admin.css', [], $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    4.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/kleistad-admin.js', [ 'jquery' ], $this->version, false );
	}

	/**
	 * Define the admin panels
	 *
	 * @since    4.0.0
	 */
	public function add_plugin_admin_menu() {
		add_menu_page( 'Instellingen', 'Kleistad', 'manage_options', $this->plugin_name, [ $this, 'display_settings_page' ], plugins_url( '/images/kleistad_icon.png', __FILE__ ), ++$GLOBALS['_wp_last_object_menu'] );

		add_submenu_page( $this->plugin_name, 'Ovens', 'Ovens', 'manage_options', 'ovens', [ $this, 'ovens_page_handler' ] );
		add_submenu_page( 'ovens', 'Toevoegen oven', 'Toevoegen oven', 'manage_options', 'ovens_form', [ $this, 'ovens_form_page_handler' ] );

		add_submenu_page( $this->plugin_name, 'Regeling stookkosten', 'Regeling stookkosten', 'manage_options', 'regelingen', [ $this, 'regelingen_page_handler' ] );
		add_submenu_page( 'regelingen', 'Toevoegen regeling', 'Toevoegen regeling', 'manage_options', 'regelingen_form', [ $this, 'regelingen_form_page_handler' ] );

		add_submenu_page( $this->plugin_name, 'Stooksaldo beheer', 'Stooksaldo beheer', 'manage_options', 'stooksaldo', [ $this, 'stooksaldo_page_handler' ] );
		add_submenu_page( 'stooksaldo', 'Wijzigen stooksaldo', 'Wijzigen stooksaldo', 'manage_options', 'stooksaldo_form', [ $this, 'stooksaldo_form_page_handler' ] );
	}

	/**
	 * Add the field to user profiles
	 *
	 * @since 1.0.0
	 * @param object $user unused.
	 */
	public function use_profile_field( $user ) {
		// Only show this option to users who can delete other users.
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}
		require_once 'partials/kleistad-admin-use-profile-field.php';
	}

	/**
	 * Saves the custom field to user meta
	 *
	 * @since 1.0.0
	 * @param int $user_id unused.
	 */
	public function user_profile_field_save( $user_id ) {
		// Only worry about saving this field if the user has access.
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}
		if ( ! isset( $_POST['kleistad_disable_user'] ) ) {
			$disabled = 0;
		} else {
			$disabled = $_POST['kleistad_disable_user'];
		}
		update_user_meta( $user_id, 'kleistad_disable_user', $disabled );
	}

	/**
	 * Saves the custom field to user meta
	 *
	 * @since 1.0.0
	 * @param array  $errors unused.
	 * @param int    $update unused.
	 * @param object $user unused.
	 */
	public function check_role( &$errors, $update, &$user ) {
		if ( (get_the_author_meta( 'kleistad_disable_user', $user->ID ) == 1) ) {
			$user->role = '';
		}
	}

	/**
	 * Add custom disabled column to users list
	 *
	 * @since 1.0.3
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
	 * @since 1.0.3
	 * @param empty  $empty unused.
	 * @param string $column_name the column involved.
	 * @param int    $user_id the user_id.
	 * @return string
	 */
	public function manage_users_column_content( $empty, $column_name, $user_id ) {

		if ( 'kleistad_user_disabled' == $column_name ) {
			if ( get_the_author_meta( 'kleistad_disable_user', $user_id ) == 1 ) {
				return 'Gedeactiveerd';
			}
		}
	}

	/**
	 * Specifiy the width of our custom column
	 *
	 * @since 1.0.3
	 */
	public function manage_users_css() {
		echo '<style type="text/css">.column-kleistad_user_disabled { width: 80px; }</style>';
	}

	/**
	 * Register the settings
	 *
	 * @since   4.0.0
	 */
	public function register_settings() {
		register_setting( 'kleistad-opties', 'kleistad-opties', [ $this, 'validate_settings' ] );
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    4.0.0
	 */
	public function display_settings_page() {
		$this->options = get_option( 'kleistad-opties' );
		require_once 'partials/kleistad-admin-display-settings.php';
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
			if ( ! is_numeric( $element ) ) {
				$element = 0;
			}
		}
		return $input;
	}

	/**
	 * List page handler
	 *
	 * @since    4.0.0
	 */
	public function ovens_page_handler() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-kleistad-admin-ovens.php';

		$table = new Kleistad_Admin_Ovens();
		$table->prepare_items();

		$message = '';
		require_once 'partials/kleistad-admin-ovens-page.php';
	}

	/**
	 * Form page handler checks is there some data posted and tries to save it
	 * Also it renders basic wrapper in which we are callin meta box render
	 *
	 * @since    4.0.0
	 */
	public function ovens_form_page_handler() {
		$message = '';
		$notice = '';
		// here we are verifying does this request is post back and have correct nonce.
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_oven' ) ) {
			$item = filter_input_array(
				INPUT_POST, [
					'id' => FILTER_SANITIZE_NUMBER_INT,
					'naam' => FILTER_SANITIZE_STRING,
					'kosten' => [
						'filter' => FILTER_SANITIZE_NUMBER_FLOAT,
						'flags' => FILTER_FLAG_ALLOW_FRACTION,
					],
					'beschikbaarheid' => [
						'filter' => FILTER_SANITIZE_STRING,
						'flags' => FILTER_FORCE_ARRAY,
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
				$oven->naam = $item['naam'];
				$oven->kosten = $item['kosten'];
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
			$item['id'] = $oven->id;
			$item['naam'] = $oven->naam;
			$item['kosten'] = $oven->kosten;
			$item['beschikbaarheid'] = $oven->beschikbaarheid;
		}
		// here we adding our custom meta box.
		add_meta_box( 'ovens_form_meta_box', 'Ovens', [ $this, 'ovens_form_meta_box_handler' ], 'oven', 'normal', 'default' );
		require_once 'partials/kleistad-admin-ovens-form-page.php';
	}

	/**
	 * This function renders our custom meta box
	 *
	 * @param array $item the oven involved.
	 */
	public function ovens_form_meta_box_handler( $item ) {
		require_once 'partials/kleistad-admin-ovens-form-meta-box.php';
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
	 * @since    4.0.0
	 */
	public function regelingen_page_handler() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-kleistad-admin-regelingen.php';

		$message = '';
		$table = new Kleistad_Admin_Regelingen();
		if ( 'delete' === $table->current_action() ) {
			if ( isset( $_REQUEST['id'] ) ) {
				list($gebruiker_id, $oven_id) = sscanf( $_REQUEST['id'], '%d %d' );
				$regelingen = new Kleistad_Regelingen();
				$regelingen->delete_and_save( $gebruiker_id, $oven_id );
			}
			$message = '<div class="updated below-h2" id="message"><p>' . sprintf( 'Aantal verwijderd: %d', count( $_REQUEST['id'] ) ) . '</p></div>';
		}
		$table->prepare_items();

		require_once 'partials/kleistad-admin-regelingen-page.php';
	}

	/**
	 * Form page handler checks is there some data posted and tries to save it
	 * Also it renders basic wrapper in which we are callin meta box render
	 *
	 * @since    4.0.0
	 */
	public function regelingen_form_page_handler() {

		$message = '';
		$notice = '';

		// this is default $item which will be used for new records.
		$default = [
			'id' => '',
			'gebruiker_id' => 0,
			'oven_id' => 0,
			'kosten' => 0,
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
				$result = $regelingen->set_and_save( $item['gebruiker_id'], $item['oven_id'] , $item['kosten'] );
				if ( '' == $item['id'] ) {
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
			} else {
				// if $item_valid not true it contains error message(s).
				$notice = $item_valid;
			}
		} else {
			// if this is not post back we load item to edit or give new one to create.
			$item = $default;
			if ( isset( $_REQUEST['id'] ) ) {
				list($gebruiker_id, $oven_id) = sscanf( $_REQUEST['id'], '%d %d' );
				$regelingen = new Kleistad_Regelingen();
				$gebruiker_regeling = $regelingen->get( $gebruiker_id, $oven_id );

				$gebruiker = get_userdata( $gebruiker_id );
				$oven = new Kleistad_Oven( $oven_id );
				$item = [
					'id' => $_REQUEST['id'],
					'gebruiker_id' => $gebruiker_id,
					'gebruiker_naam' => $gebruiker->display_name,
					'oven_id' => $oven_id,
					'oven_naam' => $oven->naam,
					'kosten' => $gebruiker_regeling,
				];
				if ( ! $item ) {
					$item = $default;
					$notice = 'De regeling is niet gevonden';
				}
			}
		}
		// here we adding our custom meta box.
		add_meta_box( 'regelingen_form_meta_box', 'Regelingen', [ $this, 'regelingen_form_meta_box_handler' ], 'regeling', 'normal', 'default' );

		require_once 'partials/kleistad-admin-regelingen-form-page.php';
	}

	/**
	 * This function renders our custom meta box
	 *
	 * @param arrray $item the regeling.
	 */
	public function regelingen_form_meta_box_handler( $item ) {
		$gebruikers = get_users(
			[
				'fields' => [ 'id', 'display_name' ],
				'orderby' => [ 'nicename' ],
			]
		);
		$ovenstore = new Kleistad_Ovens();
		$ovens = $ovenstore->get();

		require_once 'partials/kleistad-admin-regelingen-form-meta-box.php';
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
	 * @since    4.0.0
	 */
	public function stooksaldo_page_handler() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-kleistad-admin-stooksaldo.php';

		$table = new Kleistad_Admin_Stooksaldo();
		$table->prepare_items();

		$message = '';
		require_once 'partials/kleistad-admin-stooksaldo-page.php';
	}

	/**
	 * Form page handler checks is there some data posted and tries to save it
	 * Also it renders basic wrapper in which we are callin meta box render
	 *
	 * @since    4.0.0
	 */
	public function stooksaldo_form_page_handler() {

		$message = '';
		$notice = '';

		$default = [
			'id' => 0,
			'saldo' => 0,
			'naam' => '',
		];

		// here we are verifying does this request is post back and have correct nonce.
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'kleistad_stooksaldo' ) ) {
			// combine our default item with request params.
			$item = shortcode_atts( $default, $_REQUEST );
			// validate data, and if all ok save item to database.
			// if id is zero insert otherwise update.
			$item_valid = $this->validate_stooksaldo( $item );

			if ( true === $item_valid ) {
				$huidig_saldo = get_user_meta( $item['id'], 'stooksaldo', true );
				$gebruiker = get_userdata( $item['id'] );
				$beheerder = wp_get_current_user();
				Kleistad_Oven::log_saldo( "correctie saldo $gebruiker->display_name van $huidig_saldo naar {$item['saldo']}, door $beheerder->display_name." );
				$result = update_user_meta( $item['id'], 'stooksaldo', $item['saldo'] );
				if ( $result ) {
					$message = 'Het saldo is gewijzigd';
				} else {
					$notice = 'Er was een probleem met het wijzigen van gegevens';
				}
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
					$item = $default;
					$notice = 'De gebruiker is niet gevonden';
				}
				$item = [
					'id' => $_REQUEST['id'],
					'saldo' => get_user_meta( $_REQUEST['id'], 'stooksaldo', true ),
					'naam' => $gebruiker->display_name,
				];
			}
		}
		// here we adding our custom meta box.
		add_meta_box( 'stooksaldo_form_meta_box', 'Stooksaldo', [ $this, 'stooksaldo_form_meta_box_handler' ], 'stooksaldo', 'normal', 'default' );

		require_once 'partials/kleistad-admin-stooksaldo-form-page.php';
	}

	/**
	 * This function renders our custom meta box
	 * $item is row
	 *
	 * @param array $item the stooksaldo.
	 */
	public function stooksaldo_form_meta_box_handler( $item ) {
		require_once 'partials/kleistad-admin-stooksaldo-form-meta-box.php';
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
