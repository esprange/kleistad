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

use PhpImap;

/**
 * Kleistad EmailReceiver class.
 *
 * @since 6.12.6
 */
class EmailReceiver {

	/**
	 * Ontvang emails. De functie roept een callable aan met één parameter, een array met daarin de velden:
	 *   from-name
	 *   from
	 *   subject
	 *   contect
	 *
	 * @param callable $verwerk Functie die het ontvangen bericht verwerkt.
	 * @suppressWarnings(PHPMD.ExitExpression)
	 */
	public function ontvang( callable $verwerk ) {
		if ( empty( setup()['imap_server'] ) ) {
			exit( 0 );
		}
		$answered = [];
		// phpcs:disable WordPress.NamingConventions
		try {
			$mailbox = new PhpImap\Mailbox(
				'{' . setup()['imap_server'] . '}INBOX',
				setup()['imap_adres'],
				setup()['imap_pwd']
			);
		} catch ( PhpImap\Exceptions\InvalidParameterException $e ) {
			error_log( 'IMAP fail: ' . $e->getMessage() ); // phpcs:ignore
			exit( 0 );
		}
		$email_ids = $mailbox->searchMailbox( 'UNANSWERED', true );
		foreach ( $email_ids as $email_id ) {
			$email  = $mailbox->getMail( $email_id );
			$header = $mailbox->getMailHeader( $email_id );
			$body   = $email->textPlain ?: '<p>bericht tekst kan niet worden weergegeven</p>';
			$verwerk(
				[
					'from-name' => isset( $email->fromName ) ? sanitize_text_field( $email->fromName ) : sanitize_email( $email->fromAddress ),
					'from'      => sanitize_email( $email->fromAddress ),
					'subject'   => sanitize_text_field( $email->subject ),
					'content'   => $body,
					'tijd'      => date( 'd-m-Y H:i', strtotime( $header->date ?? date( 'd-m-Y' ) ) ),
				]
			);
			$answered[] = $email_id;
		}
		if ( ! empty( $answered ) ) {
			$mailbox->setFlag( $answered, '\\Answered' );
		}
		$mailbox->disconnect();
		// phpcs:enable
	}

}
