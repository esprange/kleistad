<?php
/**
 * De definitie van de email ontvangst class
 *
 * @link       https://www.kleistad.nl
 * @since      6.12.6
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Html2Text;
use PhpImap;

/**
 * Kleistad EmailReceiver class.
 *
 * @since 6.12.6
 */
class EmailReceiver {

	/**
	 * Ontvang en verwerk emails. De functie roept een callable aan met één parameter, een array met daarin de velden:
	 *   from-name
	 *   from
	 *   subject
	 *   contect
	 *
	 * @param string   $email      Het email adres dat ontvangen moet worden.
	 * @param Callable $verwerk    Functie die het ontvangen bericht verwerkt.
	 * @suppressWarnings(PHPMD.ExitExpression)
	 */
	public function ontvang( string $email, callable $verwerk ) {
		if ( empty( $email ) ) {
			die();
		}
		$setup    = setup();
		$answered = [];
		// phpcs:disable WordPress.NamingConventions
		$mailbox = new PhpImap\Mailbox(
			'{' . $setup['imap_server'] . '}INBOX',
			$setup['workshop'],
			$setup['imap_pwd']
		);
		try {
			$email_ids = $mailbox->searchMailbox( 'UNANSWERED' );
			foreach ( $email_ids as $email_id ) {
				$email = $mailbox->getMail( $email_id );
				$body  = $email->textPlain ?: '<p>bericht tekst kan niet worden weergegeven</p>';
				if ( $email->textHtml ) {
					$html = new Html2Text\Html2Text( preg_replace( '/<!--\[if gte mso 9\]>.*<!\[endif\]-->/s', '', $email->textHtml ) );
					$body = $html->getText() ?: $body;
				}
				$verwerk(
					[
						'from-name' => isset( $email->fromName ) ? sanitize_text_field( $email->fromName ) : sanitize_email( $email->fromAddress ),
						'from'      => sanitize_email( $email->fromAddress ),
						'subject'   => sanitize_text_field( $email->subject ),
						'content'   => sanitize_textarea_field( $body ),
					]
				);
				$answered[] = $email_id;
			}
			if ( ! empty( $answered ) ) {
				$mailbox->setFlag( $answered, '\\Answered' );
			}
			$mailbox->disconnect();
		} catch ( PhpImap\Exceptions\ConnectionException $ex ) {
			error_log( 'IMAP fail: ' . $ex->getMessage() ); // phpcs:ignore
			die();
		}
	// phpcs:enable
	}

}
