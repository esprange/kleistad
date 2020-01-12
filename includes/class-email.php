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
	 * De mail parameters.
	 *
	 * @var array $mailparams
	 */
	private $mailparams;

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
				'labels'            => [
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
				'public'            => true,
				'supports'          => [
					'title',
					'editor',
					'revisions',
				],
				'rewrite'           => false,
				'show_ui'           => true,
				'show_in_menu'      => 'kleistad',
				'show_in_admin_bar' => false,
				'show_in_nav_menus' => false,
				'delete_with_user'  => false,
			]
		);
	}

	/**
	 * Helper functie, haalt het domein op van de website.
	 *
	 * @return string
	 */
	public static function domein() {
		return substr( strrchr( get_bloginfo( 'admin_email' ), '@' ), 1 );
	}

	/**
	 * Helper functie, haalt het domein op van de verzender.
	 *
	 * @return string
	 */
	public static function verzend_domein() {
		$mailgun_opties = get_option( 'wp_mail_smtp' );
		return false === $mailgun_opties ? self::domein() : $mailgun_opties['mailgun']['domain'];
	}

	/**
	 * Initialisatie functie zodat filters e.d. maar eenmalig gerealiseerd worden.
	 */
	private function headers() {
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
		add_action(
			'wp_mail_failed',
			function( $wp_error ) {
				return error_log( "mail fout: " . $wp_error->get_error_message() ); // phpcs:ignore
			}
		);
		add_action(
			'phpmailer_init',
			function( $phpmailer ) {
				// phpcs:disable
				if ( empty( $phpmailer->AltBody ) ) {
					$html = new \Html2Text\Html2Text( $phpmailer->Body );
					$phpmailer->AltBody = $html->getText();
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
	 */
	private function prepare( $args ) {
		$this->mailparams = wp_parse_args(
			$args,
			[
				'auto'        => 'noreply',
				'bcc'         => [],
				'cc'          => [],
				'content'     => '',
				'from'        => 'no_reply@' . self::verzend_domein(),
				'from_name'   => 'Kleistad',
				'parameters'  => [],
				'reply-to'    => 'no_reply@' . self::domein(),
				'sign'        => 'Kleistad',
				'sign_email'  => true,
				'slug'        => '',
				'to'          => 'Kleistad <info@' . self::domein() . '>',
				'attachments' => '',
			]
		);

		if ( ! empty( $this->mailparams['slug'] ) ) {
			$page = get_page_by_title( $this->mailparams['slug'], OBJECT, self::POST_TYPE );
			if ( ! is_null( $page ) ) {
				$this->mailparams['content'] = apply_filters( 'the_content', $page->post_content );
			} else {
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
		return preg_replace_callback_array(
			[
				'#\[\s*pagina\s*:\s*([a-z,_,-]+?)\s*\]#i' => function( $match ) {
					// Include pagina.
					$page = get_page_by_title( $match[1], OBJECT, self::POST_TYPE );
					return ! is_null( $page ) ? apply_filters( 'the_content', $page->post_content ) : '';
				},
				'#\[\s*([a-z,_]+)\s*\]#i'                 => function( $match ) {
					// Include parameters.
					return isset( $this->mailparams['parameters'] ) ? $this->mailparams['parameters'][ $match[1] ] : '';
				},
				'#\[\s*(cc|bcc)\s*:\s*(.+?)\s*\]#i'       => function( $match ) {
					// Bcc of Cc parameters.
					$this->mailparams[ $match[1] ][] = $match[2];
					return '';
				},
			],
			$this->mailparams['content']
		);
	}

	/**
	 * Email notificatie functie, maakt email tekst op t.b.v. standaard WP notificaties
	 *
	 * @param string $slug De email slug.
	 * @param array  $user De user welke de email gaat ontvangen.
	 * @param string $copy Een eventuele copy email adres.
	 * @return array
	 */
	public function notify( $slug, $user, $copy = '' ) {
		$args = [
			'slug'       => $slug,
			'to'         => $user['user_email'],
			'cc'         => [ $copy ],
			'parameters' => [
				'voornaam'   => $user['first_name'],
				'achternaam' => $user['last_name'],
				'email'      => $user['user_email'],
			],
		];
		return [
			'message' => $this->inhoud( $this->prepare( $args ) ),
			'subject' => 'Wijziging email adres',
			'headers' => $this->headers(),
			'to'      => $this->mailparams['to'],
		];
	}

	/**
	 * Email verzend functie, maakt email tekst op en verzendt de mail
	 *
	 * @param array $args parameters voor verzending.
	 */
	public function send( $args ) {
		$tekst = $this->prepare( $args );
		if ( get_option( 'kleistad_email_actief' ) ) {
			return wp_mail(
				$this->mailparams['to'],
				$this->mailparams['subject'],
				$this->inhoud( $tekst ),
				$this->headers(),
				$this->mailparams['attachments']
			);
		} else {
			error_log( "E-mail aan: {$this->mailparams['to']} over {$this->mailparams['subject']} met bijlage {$this->mailparams['attachments']}" ); // phpcs:ignore
			return true;
		}
	}

	/**
	 * Maak de email tekst aan.
	 *
	 * @param string $tekst De content.
	 * @return string De opgemaakte tekst.
	 */
	private function inhoud( $tekst ) {
		$schone_tekst = wordwrap( preg_replace( '/\s+/', ' ', $tekst ), 76, "\r\n", false );
		ob_start();
		?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<!--[if !mso]><!-->
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<!--<![endif]-->
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="format-detection" content="telephone=no" />
	<title>Kleistad Email</title>
	<!--[if (gte mso 9)|(IE)]>
	<style type="text/css">
		table {border-collapse: collapse;}
	</style>
	<![endif]-->
	<style type="text/css">
		body {
			margin: 0 !important;
			padding: 0;
			background-color: #ffffff;
			-webkit-font-smoothing: antialiased;
			-webkit-text-size-adjust: none;
		}
		table {
			border-spacing: 0;
			font-family: Helvetica;
			font-size: 11pt;
			color: #333333;
		}
		td {
			padding: 0;
		}
		img {
			border: 0;
		}
		div[style*="margin: 16px 0"] {
			margin:0 !important;
		}
		.wrapper {
			width: 100%;
			table-layout: fixed;
			-webkit-text-size-adjust: 100%;
			-ms-text-size-adjust: 100%;
		}
		.webkit {
			max-width: 600px;
			margin: 0 auto;
		}
		.outer {
			Margin: 0 auto;
			width: 100%;
			max-width: 600px;
		}
		.inner {
			padding: 10px;
			text-align: left;
		}
		p {
			Margin: 0;
		}
		a {
			color: #f60083;
			text-decoration: underline;
		}
		.h1 {
			font-size: 21px;
			font-weight: bold;
			Margin-bottom: 18px;
		}
		.h2 {
			font-size: 18px;
			font-weight: bold;
			Margin-bottom: 12px;
		}
		.one-column {
			text-align: left;
		}
		.one-column p {
			font-size: 14px;
			Margin-bottom: 10px;
		}
	</style>
</head>
<body>
	<center class="wrapper">
		<div class="webkit">
		<!--[if (gte mso 9)|(IE)]>
		<table width="600" align="center" cellpadding="0" cellspacing="0" border="0" >
		<tr>
		<td>
		<![endif]-->
		<table class="outer" align="center">
			<tr>
				<td align="right">
				<img alt="Inloop atelier Kleistad" src="<?php echo chunk_split( 'data:image/jpeg;base64,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' ); //phpcs:ignore ?>"/>
				</td>
			</tr>
			<tr>
				<td class="one-column">
					<table width="100%">
						<tr>
							<td class="inner">
								<?php echo $schone_tekst; // phpcs:ignore ?><br />
								<p>Met vriendelijke groet,</p>
								<p><?php echo $this->mailparams['sign']; // phpcs:ignore ?></p>
								<?php if ( $this->mailparams['sign_email'] ) : ?>
								<p><a href="mailto:<?php echo esc_attr( 'info@' . self::domein() ); ?>" target="_top" ><?php echo esc_html( 'info@' . self::domein() ); ?></a></p>
								<?php endif ?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td align="center" style="font-family: Calibri; font-size: 9pt;" >
					<?php if ( 'noreply' === $this->mailparams['auto'] ) : ?>
						Deze e-mail is automatisch gegenereerd en kan niet beantwoord worden.
					<?php elseif ( 'reply' === $this->mailparams['auto'] ) : ?>
						Deze e-mail is automatisch gegenereerd.
					<?php endif ?>
				</td>
			</tr>
		</table>
		<!--[if (gte mso 9)|(IE)]>
		</td>
		</tr>
		</table>
		<![endif]-->
		</div>
	</center>
</body>
</html>
		<?php
		return preg_replace( '/>\s+</m', '><', ob_get_clean() );
	}

}
