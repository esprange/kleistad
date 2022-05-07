<?php
/**
 * De  class voor het verzenden van email.
 *
 * @link       https://www.kleistad.nl
 * @since      5.2.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

/**
 * De class voor email
 */
class Email {

	/**
	 * Het domein van de emails waar antwoorden naar gezonden worden
	 *
	 * @var string $domein Het domein.
	 */
	public string $domein;

	/**
	 * Het domein van de emails die verzonden worden
	 *
	 * @var string $verzenddomein Het domein, kan een mailgun domein zijn.
	 */
	public string $verzend_domein;

	/**
	 * De mailbox van de standaard verzender
	 *
	 * @var string $info De mailbox, meestal info maar bij development en test de beheerder.
	 */
	public string $info;

	/**
	 * De mail parameters.
	 *
	 * @var array $mailparams
	 */
	private array $mailparams;

	/**
	 * We maken gebruik van een custom post object
	 */
	const POST_TYPE = 'kleistad_email';

	/**
	 * Initialiseer de aanvragen als custom post type.
	 */
	public static function create_type() {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'               => 'Email templates',
					'singular_name'      => 'Email template',
					'add_new'            => 'Toevoegen',
					'add_new_item'       => 'Template toevoegen',
					'edit'               => 'Wijzigen',
					'edit_item'          => 'Template wijzigen',
					'view'               => 'Inzien',
					'view_item'          => 'Template inzien',
					'search_items'       => 'Template zoeken',
					'not_found'          => 'Niet gevonden',
					'not_found_in_trash' => 'Niet in prullenbak gevonden',
				],
				'public'              => true,
				'supports'            => [
					'title',
					'editor',
					'revisions',
				],
				'rewrite'             => false,
				'show_ui'             => true,
				'show_in_menu'        => 'kleistad',
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'delete_with_user'    => false,
				'exclude_from_search' => true,
			]
		);
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		static $info           = '';
		static $domein         = '';
		static $verzend_domein = '';
		if ( empty( $info ) ) {
			$info           = 'development' !== wp_get_environment_type() ? 'info' : ( strtok( get_bloginfo( 'admin_email' ), '@' ) );
			$domein         = substr( strrchr( get_bloginfo( 'admin_email' ), '@' ), 1 );
			$verzend_domein = $domein;
			$active_plugins = get_option( 'active_plugins' ) ?? [];
			foreach ( $active_plugins as $active_plugin ) {
				if ( str_contains( $active_plugin, 'wp-mail-smtp' ) ) {
					$mailgun_opties = get_option( 'wp_mail_smtp' );
					$verzend_domein = $mailgun_opties['mailgun']['domain'];
				}
			}
		}
		$this->info           = $info;
		$this->domein         = $domein;
		$this->verzend_domein = $verzend_domein;
	}

	/**
	 * Initialisatie functie zodat filters e.d. maar eenmalig gerealiseerd worden.
	 *
	 * @return array De headers.
	 */
	private function headers() : array {
		$headers   = [];
		$from      = $this->mailparams['from'];
		$from_name = $this->mailparams['from_name'];
		foreach ( $this->mailparams['cc'] as $copy ) {
			$headers[] = "Cc:$copy";
		}
		foreach ( $this->mailparams['bcc'] as $copy ) {
			$headers[] = "Bcc:$copy";
		}
		$headers[] = "Reply-to:{$this->mailparams['reply-to']}";

		add_filter(
			'wp_mail_from',
			function() use ( $from ) {
				return $from;
			}
		);
		add_filter(
			'wp_mail_from_name',
			function() use ( $from_name ) {
				return $from_name;
			}
		);
		add_filter(
			'wp_mail_content_type',
			function() {
				return 'text/html';
			}
		);
		add_action(
			'wp_mail_failed',
			function( $wp_error ) {
				fout( __CLASS__, $wp_error->get_error_message() );
			}
		);
		add_action(
			'phpmailer_init',
			function( $phpmailer ) {
				// phpcs:disable WordPress.NamingConventions
				if ( empty( $phpmailer->AltBody ) ) {
					$phpmailer->AltBody = 'Emails van Kleistad zijn in HTML formaat en kunnen via de meeste mailclients normaal weergegeven worden';
				}
				// phpcs:enable
			}
		);
		return $headers;
	}

	/**
	 * Helper functie, maakt email tekst voor een WP notificatie email op.
	 *
	 * @param array $args parameters voor email.
	 * @return string De email inhoud.
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function prepare( array $args ) : string {
		$this->mailparams = wp_parse_args(
			$args,
			[
				'auto'        => 'noreply',
				'bcc'         => [],
				'cc'          => [],
				'content'     => '',
				'from'        => "no_reply@$this->verzend_domein",
				'from_name'   => 'Kleistad',
				'parameters'  => [],
				'reply-to'    => "no_reply@$this->domein",
				'sign'        => 'Kleistad',
				'sign_email'  => true,
				'slug'        => '',
				'to'          => "Kleistad <$this->info@$this->domein>",
				'attachments' => [],
			]
		);

		if ( ! empty( $this->mailparams['slug'] ) ) {
			$page = get_page_by_title( $this->mailparams['slug'], OBJECT, self::POST_TYPE );
			if ( ! is_null( $page ) ) {
				$this->mailparams['content'] = apply_filters( 'the_content', $page->post_content );
			} else {
				if ( 'production' === wp_get_environment_type() ) {
					return ''; // Verstuur geen bericht als er geen inhoud is.
				}
				$this->mailparams['content'] = "<table><tr><th colspan=\"2\">{$this->mailparams['slug']}</th></tr>";
				foreach ( $this->mailparams['parameters'] as $key => $parameter ) {
					$this->mailparams['content'] .= "<tr><td>$key</td><td>$parameter</td></tr>";
				}
				$this->mailparams['content'] .= '</table>';
			}
		}
		/**
		 * Via regexp de tekst bewerken. De match variable bevat resp. de match, een sleutel en eventueel een waarde.
		 */
		return (string) preg_replace_callback_array(
			[
				'#\[\s*pagina\s*:\s*([a-z,_,-]+?)\s*\]#i' => function( $match ) {
					// Include pagina.
					$page = get_page_by_title( $match[1], OBJECT, self::POST_TYPE );
					return ! is_null( $page ) ? apply_filters( 'the_content', $page->post_content ) : '';
				},
				'#\[\s*([a-z,_]+)\s*\]#i'                 => function( $match ) {
					// Include parameters.
					return $this->mailparams['parameters'][ $match[1] ] ?? '';
				},
				'#\[\s*(cc|bcc)\s*:\s*(.+?)\s*\]#i'       => function( $match ) {
					// Bcc of Cc parameter, alleen gebruiken ingeval van productie.
					if ( 'production' === wp_get_environment_type() ) {
						$this->mailparams[ $match[1] ][] = $match[2];
					}
					return '';
				},
			],
			$this->mailparams['content']
		);
	}

	/**
	 * Email notificatie functie, maakt email tekst op t.b.v. standaard WP notificaties
	 *
	 * @param array $args De argumenten voor de email.
	 * @return array
	 */
	public function notify( array $args ) : array {
		$tekst = $this->prepare( $args );
		return [
			'to'      => $this->mailparams['to'],
			'subject' => $this->mailparams['subject'],
			'message' => $this->format( $tekst ),
			'headers' => $this->headers(),
		];
	}

	/**
	 * Email verzend functie, maakt email tekst op en verzendt de mail
	 *
	 * @param array $args parameters voor verzending.
	 * @return bool
	 */
	public function send( array $args ) : bool {
		$tekst = $this->prepare( $args );
		if ( $tekst && get_option( 'kleistad_email_actief' ) ) {
			return wp_mail(
				$this->mailparams['to'],
				$this->mailparams['subject'],
				$this->format( $tekst ),
				$this->headers(),
				$this->mailparams['attachments']
			);
		}
		$bijlagen = is_array( $this->mailparams['attachments'] ) ? implode( ', ', $this->mailparams['attachments'] ) : $this->mailparams['attachments'];
		fout( __CLASS__, "e-mail aan: {$this->mailparams['to']} over {$this->mailparams['subject']} met bijlage $bijlagen" );
		return true;
	}

	/**
	 * Maak de email body aan.
	 *
	 * @param string $tekst De content.
	 * @return string De opgemaakte tekst.
	 */
	private function format( string $tekst ) : string {
		$schone_tekst  = wordwrap( preg_replace( '/\s+/', ' ', $tekst ), 75, "\r\n", false );
		$ondertekening = '';
		if ( ! empty( $this->mailparams['sign'] ) ) {
			$ondertekening = "<p>Met vriendelijke groet,</p><p>{$this->mailparams['sign']}</p>";
			if ( $this->mailparams['sign_email'] ) {
				$ondertekening .= "<p><a href=\"mailto:$this->info@$this->domein\" target=\"_top\" >$this->info@$this->domein</a></p>";
			}
		}
		$footer   = 'noreply' === $this->mailparams['auto'] ? 'Deze e-mail is automatisch gegenereerd en kan niet beantwoord worden.' :
			( 'reply' === $this->mailparams['auto'] ? 'Deze e-mail is automatisch gegenereerd.' : '' );
		$template = file_get_contents( __DIR__ . '/mailtemplate.html', 'r' ); // phpcs:ignore

		return str_replace(
			[
				'[INHOUD]',
				'[ONDERTEKENING]',
				'[FOOTER]',
			],
			[
				$schone_tekst,
				$ondertekening,
				$footer,
			],
			$template
		);
	}
}
