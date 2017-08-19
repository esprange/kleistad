<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.0.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 */
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kleistad-entity.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kleistad-ovens.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kleistad-cursussen.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kleistad-abonnementen.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kleistad-roles.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-kleistad-gebruikers.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-kleistad-public-shortcode.php';

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, generic shortcode handler as well as callbacks and scheduled job.
 *
 * @package    Kleistad
 * @subpackage Kleistad/public
 * @author     Eric Sprangers <e.sprangers@sprako.nl>
 */
class Kleistad_Public {

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
   *
   * @var string url voor Ajax callbacks 
   */
  private $url;

  /**
   *
   * @var array kleistad plugin settings 
   */
  private $options;

  /**
   * Initialize the class and set its properties.
   *
   * @since    4.0.0
   * @param      string    $plugin_name       The name of the plugin.
   * @param      string    $version    The version of this plugin.
   */
  public function __construct($plugin_name, $version) {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
    $this->url = 'kleistad_reserveren/v' . $version;
    $this->options = get_option('kleistad-opties');

    add_filter('widget_text', 'do_shortcode');
  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since    4.0.0
   */
  public function register_styles() {
    wp_register_style('jqueryui-css', "//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css");
    wp_register_style('datatables', "//cdn.datatables.net/1.10.15/css/jquery.dataTables.css");
    wp_register_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/kleistad-public.css', ['jqueryui-css', 'datatables'], $this->version, 'all');
  }

  /**
   * Register the JavaScripts for the public-facing side of the site.
   *
   * @since    4.0.0
   */
  public function register_scripts() {
    wp_register_script('datatables', "//cdn.datatables.net/1.10.15/js/jquery.dataTables.js", ['jquery']);
    wp_register_script($this->plugin_name . 'cursus_inschrijving', plugin_dir_url(__FILE__) . 'js/kleistad-public-cursus_inschrijving.js', ['jquery',], $this->version, false);
    wp_register_script($this->plugin_name . 'abonnee_inschrijving', plugin_dir_url(__FILE__) . 'js/kleistad-public-abonnee_inschrijving.js', ['jquery', 'jquery-ui-datepicker',], $this->version, true);
    wp_register_script($this->plugin_name . 'cursus_beheer', plugin_dir_url(__FILE__) . 'js/kleistad-public-cursus_beheer.js', ['jquery', 'jquery-ui-dialog', 'jquery-ui-tabs', 'jquery-ui-datepicker', 'jquery-ui-spinner', 'datatables',], $this->version, false);
    wp_register_script($this->plugin_name . 'saldo', plugin_dir_url(__FILE__) . 'js/kleistad-public-saldo.js', ['jquery', 'jquery-ui-datepicker',], $this->version, false);
    wp_register_script($this->plugin_name . 'saldo_overzicht', plugin_dir_url(__FILE__) . 'js/kleistad-public-saldo_overzicht.js', ['jquery', 'datatables',], $this->version, false);
    wp_register_script($this->plugin_name . 'stookbestand', plugin_dir_url(__FILE__) . 'js/kleistad-public-stookbestand.js', ['jquery', 'jquery-ui-datepicker',], $this->version, false);
    wp_register_script($this->plugin_name . 'registratie_overzicht', plugin_dir_url(__FILE__) . 'js/kleistad-public-registratie_overzicht.js', ['jquery', 'jquery-ui-dialog', 'datatables'], $this->version, false);
    wp_register_script($this->plugin_name . 'rapport', plugin_dir_url(__FILE__) . 'js/kleistad-public-rapport.js', ['jquery', 'jquery-ui-dialog', 'datatables'], $this->version, false);
    wp_register_script($this->plugin_name . 'betalingen', plugin_dir_url(__FILE__) . 'js/kleistad-public-betalingen.js', ['jquery', 'jquery-ui-dialog', 'datatables'], $this->version, false);
    wp_register_script($this->plugin_name . 'reservering', plugin_dir_url(__FILE__) . 'js/kleistad-public-reservering.js', ['jquery', 'jquery-ui-dialog',], $this->version, false);
    wp_localize_script($this->plugin_name . 'reservering', 'kleistad_data', [
        'nonce' => wp_create_nonce('wp_rest'),
        'base_url' => rest_url($this->url),
        'success_message' => 'de reservering is geslaagd!',
        'error_message' => 'het was niet mogelijk om de reservering uit te voeren',
            ]
    );
  }

  /**
   * Register the AJAX endpoints
   * 
   * @since   4.0.0
   */
  public function register_endpoints() {
   require plugin_dir_path(dirname(__FILE__)) . 'public/class-kleistad-public-reservering.php';
    register_rest_route($this->url, '/reserveer', [
        'methods' => 'POST',
        'callback' => ['kleistad_public_reservering', 'callback_muteer'],
        'args' => [
            'dag' => ['required' => true],
            'maand' => ['required' => true],
            'jaar' => ['required' => true],
            'oven_id' => ['required' => true],
            'temperatuur' => ['required' => false],
            'soortstook' => ['required' => false],
            'programma' => ['required' => false],
            'verdeling' => ['required' => false],
            'opmerking' => ['required' => false],
            'gebruiker_id' => ['required' => true],
        ],
        'permission_callback' => function() {
          return is_user_logged_in();
        }
    ]);
    register_rest_route($this->url, '/show', [
        'methods' => 'POST',
        'callback' => ['kleistad_public_reservering', 'callback_show'],
        'args' => [
            'maand' => ['required' => true],
            'jaar' => ['required' => true],
            'oven_id' => ['required' => true],
        ],
        'permission_callback' => function() {
          return is_user_logged_in();
        }
    ]);
  }

  /**
   * After login check to see if user account is disabled
   *
   * @since 4.0.0
   * @param string $user_login
   * @param object $user
   */
  public function user_login($user_login, $user = null) {

    if (!$user) {
      $user = get_user_by('login', $user_login);
    }
    if (!$user) {
      // not logged in - definitely not disabled
      return;
    }
    // Get user meta
    $disabled = get_user_meta($user->ID, 'kleistad_disable_user', true);

    // Is the use logging in disabled?
    if ($disabled == '1') {
      // Clear cookies, a.k.a log user out
      wp_clear_auth_cookie();

      // Build login URL and then redirect
      $login_url = add_query_arg('disabled', '1', site_url('wp-login.php', 'login'));
      wp_redirect($login_url);
      exit;
    }
  }

  /**
   * Show a notice to users who try to login and are disabled
   *
   * @since 4.0.0
   * @param string $message
   * @return string
   */
  public function user_login_message($message) {

    // Show the error message if it seems to be a disabled user
    if (isset($_GET['disabled']) && $_GET['disabled'] == 1) {
      $message = '<div id="login_error">' . apply_filters('kleistad_disable_users_notice', 'Inloggen op dit account niet toegestaan') . '</div>';
    }
    return $message;
  }

  /**
   * shortcode form handler functie, toont formulier, valideert input, bewaart gegevens en toont resultaat
   * 
   * @since 4.0.0
   * @param array $atts       wordt niet gebruikt
   * @param string $content   wordt niet gebruikt
   * @param string $tag       wordt gebruikt als selector voor de diverse functie aanroepen
   * @return string           html resultaat
   */
  public function shortcode_handler($atts, $content = '', $tag) {

    $html = '';
    $input = null;
    $form = substr($tag, strlen('kleistad-'));
    require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-kleistad-public-' . $form . '.php';

    wp_enqueue_style($this->plugin_name);
    if (wp_style_is($this->plugin_name . $form, 'registered')) {
      wp_enqueue_style($this->plugin_name . $form);
    }
    if (wp_script_is($this->plugin_name . $form, 'registered')) {
      wp_enqueue_script($this->plugin_name . $form);
    }

    $formClass = 'Kleistad_Public_' . str_replace(' ', '', ucwords(str_replace('_', ' ', $form))); 
    $formObject = new $formClass($this->plugin_name, $atts);

    if (!is_null(filter_input(INPUT_POST, 'kleistad_submit_' . $form))) {
      if (wp_verify_nonce(filter_input(INPUT_POST, '_wpnonce'), 'kleistad_' . $form)) {
        $result = $formObject->validate();
        if (!is_wp_error($result)) {
          $input = $result;
          $result = $formObject->save($input);
        }
        if (is_wp_error($result)) {
          foreach ($result->get_error_messages() as $error) {
            $html .= '<div class="kleistad_fout"><p>' . $error . '</p></div>';
          }
        } else {
          $html .= '<div class="kleistad_succes"><p>' . $result . '</p></div>';
          $input = null;
        }
      } else {
        $html .= '<div class="kleistad_fout"><p>security fout</p></div>';
      }
    }
    $data = $formObject->prepare($input);
    if (is_wp_error($data)) {
      $html .= '<div class="kleistad_fout"><p>' . $data->get_error_message() . '</p></div>';
      return $html;
    }
    ob_start();
    require plugin_dir_path(dirname(__FILE__)) . 'public/partials/kleistad-public-' . $form . '.php';
    $html .= ob_get_contents();
    ob_clean();
    return $html;
  }

  /**
   * 
   * Update ovenkosten batch job
   * 
   * @since 4.0.0
   */
  public function update_ovenkosten() {
    // class included to enable usage of compose_email method
    require plugin_dir_path(dirname(__FILE__)) . 'public/class-kleistad-public-saldo.php';
    
    Kleistad_Oven::log_saldo("verwerking stookkosten gestart.");
    $options = get_option('kleistad-opties');

    $regelingen = new Kleistad_Regelingen();

    $ovenStore = new Kleistad_Ovens();
    $ovens = $ovenStore->get();

    $reserveringStore = new Kleistad_Reserveringen();
    $reserveringen = $reserveringStore->get();

    /*
     * saldering transacties uitvoeren
     */
    foreach ($reserveringen as &$reservering) {
      if (!$reservering->verwerkt && $reservering->datum <= strtotime('- ' . $options['termijn'] . ' days')) {
        $gebruiker = get_userdata($reservering->gebruiker_id);
        foreach ($reservering->verdeling as $stookdeel) {
          if (intval($stookdeel['id']) == 0) {
            continue;
          }
          $medestoker = get_userdata($stookdeel['id']);
          $regeling = $regelingen->get($stookdeel['id'], $reservering->oven_id);
          $kosten = ( is_null($regeling) ) ? $ovens[$reservering->oven_id]->kosten : $regeling;
          $prijs = round($stookdeel['perc'] / 100 * $kosten, 2);

          $huidig_saldo = (float) get_user_meta($stookdeel['id'], 'stooksaldo', true);
          $nieuw_saldo = ($huidig_saldo == '') ? 0 - (float) $prijs : round((float) $huidig_saldo - (float) $prijs, 2);

          Kleistad_Oven::log_saldo("wijziging saldo $medestoker->display_name van $huidig_saldo naar $nieuw_saldo, stook op " .
                  date("d-m-Y", $reservering->datum));
          update_user_meta($stookdeel['id'], 'stooksaldo', $nieuw_saldo);
          $reservering->verwerkt = true;
          $reservering->save();

          $to = "$medestoker->first_name $medestoker->last_name <$medestoker->user_email>";
          Kleistad_Public_Saldo::compose_email($to, 'Kleistad kosten zijn verwerkt op het stooksaldo', 'kleistad_email_stookkosten_verwerkt', [
              'voornaam' => $medestoker->first_name,
              'achternaam' => $medestoker->last_name,
              'stoker' => $gebruiker->display_name,
              'bedrag' => number_format($prijs, 2, ',', ''),
              'saldo' => number_format($nieuw_saldo, 2, ',', ''),
              'stookdeel' => $stookdeel['perc'],
              'stookdatum' => date('d-m-Y', $reservering->datum),
              'stookoven' => $ovens[$reservering->oven_id]->naam,
          ]);
        }
      }
    }
    /*
     * de notificaties uitsturen voor stook die nog niet verwerkt is. 
     */
    foreach ($reserveringen as &$reservering) {
      if (!$reservering->verwerkt && !$reservering->gemeld && $reservering->datum < time()) {

        $regeling = $regelingen->get($reservering->gebruiker_id, $reservering->oven_id);
   
        $gebruiker = get_userdata($reservering->gebruiker_id);
        $to = "$gebruiker->first_name $gebruiker->last_name <$gebruiker->user_email>";
        Kleistad_Public_Saldo::compose_email($to, "Kleistad oven gebruik op " . date('d-m-Y', $reservering->datum), 'kleistad_email_stookmelding', [
            'voornaam' => $gebruiker->first_name,
            'achternaam' => $gebruiker->last_name,
            'bedrag' => number_format(( is_null($regeling) ) ? $ovens[$reservering->oven_id]->kosten : $regeling, 2, ',', ''),
            'datum_verwerking' => date('d-m-Y', strtotime('+' . $options['termijn'] . ' day', $reservering->datum)), // $datum_verwerking, 
            'datum_deadline' => date('d-m-Y', strtotime('+' . $options['termijn'] - 1 . ' day', $reservering->datum)), //$datum_deadline,
            'stookoven' => $ovens[$reservering->oven_id]->naam,
        ]);
        $reservering->gemeld = true;
        $reservering->save();
      }
    }

    Kleistad_Oven::log_saldo("verwerking stookkosten gereed.");
  }

  /**
   * Verwijder gebruiker, geactiveerd als er een gebruiker verwijderd wordt.
   * 
   * @since 4.0.0
   * @param int $gebruiker_id gebruiker id
   */
  public function verwijder_gebruiker($gebruiker_id) {
    Kleistad_reservering::verwijder($gebruiker_id);
  }

}
