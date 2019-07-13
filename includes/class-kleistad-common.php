<?php
/**
 * Definitie van de common functies class van de plugin.
 *
 * @link       https://www.kleistad.nl
 * @since      5.5.1
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

/**
 * De kleistad class voor de common functies.
 */
class Kleistad_Common {

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
			wp_safe_redirect( $login_url );
			die();
		}
	}

	/**
	 * Toont het Kleistad logo op de login.
	 */
	public function login_enqueue_scripts() {
		?>
		<style type="text/css">
			#login h1 a, .login h1 a {
				background-image: url(https://www.kleistad.nl/wp/wp-content/uploads/2016/03/cropped-logo-kleistad.jpg);
			height:150px;
			width:150px;
			background-size: 150px 150px;
			background-repeat: no-repeat;
			padding-bottom: 10px;
			}
			body.login {
				background-color: #fcfcfc;
			}
			.login form {
				border-radius: 10px;
				border: 4px solid #080007;
			}
		</style>
		<?php
	}

	/**
	 * Wijzig de url naar de home url.
	 */
	public function login_headerurl() {
		return home_url();
	}

	/**
	 * Toon de juiste text bij hovering over het login logo.
	 */
	public function login_headertext() {
		return 'Kleistad';
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
	 * Redirect gebruikers naar de leden pagina.
	 *
	 * @since 4.5.2
	 *
	 * @param string  $url De bestaande url als er niets gewijzigd wordt.
	 * @param object  $request Wordt niet gebruikt.
	 * @param WP_User $user Het WordPress user object.
	 * @return string De Url.
	 */
	public function login_redirect( $url, $request, $user ) {
		if ( isset( $request ) && $user && is_object( $user ) && is_a( $user, 'WP_User' ) ) { // De test van request is dummy statement, altijd true.
			$url = ( $user->has_cap( 'bestuur' ) ) ? home_url( '/bestuur/' ) : home_url( '/leden/' );
		}
		return $url;
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
	 * @staticvar bool $is_active Bewaart de activeringsstatus, als true dan niets doen.
	 * @param string   $items     De menu opties.
	 * @param stdClass $args      De argumenten van het filter.
	 * @return string
	 */
	public function loginuit_menu( $items, $args ) {
		static $is_active = false;

		if ( is_admin() || 'primary' !== $args->theme_location || $is_active ) {
			return $items;
		}

		$redirect = get_permalink();
		if ( false === $redirect || is_home() ) {
			$redirect = home_url();
		}
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
	 * Voeg 15 minuten schedule toe aan bestaande set van schedules.
	 *
	 * @param array $schedules De set van schedules.
	 */
	public function cron_schedules( $schedules ) {
		if ( ! isset( $schedules['15_mins'] ) ) {
			$schedules['15_mins'] = [
				'interval' => 900,
				'display'  => 'Elke 15 minuten',
			];
		}
		return $schedules;
	}
}
