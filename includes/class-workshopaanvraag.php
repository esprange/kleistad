<?php
/**
 * De definitie van de recept class
 *
 * @link       https://www.kleistad.nl
 * @since      5.6.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use WP_Post;

/**
 * Kleistad WorkshopAanvraag class.
 *
 * @since 5.6.0
 *
 * @property int ID
 * @property string post_content
 * @property string post_status
 * @property string post_title
 * @property string post_date
 * @property string post_name
 * @property string post_modified
 * @property string email
 * @property string contact
 * @property string omvang
 * @property string periode
 * @property int    plandatum
 * @property string dagdeel
 * @property string telnr
 * @property string naam
 * @property array  technieken
 * @property int    workshop_id
 */
class WorkshopAanvraag {

	/**
	 * We maken gebruik van een custom post object
	 */
	public const POST_TYPE  = 'kleistad_workshopreq';
	public const GEPLAND    = 'gepland';
	public const GEREAGEERD = 'gereageerd';
	public const NIEUW      = 'nieuw';
	public const VRAAG      = 'vraag';
	public const MOMENT     = [
		OCHTEND  => [
			'start' => '09:30',
			'eind'  => '11:30',
		],
		MIDDAG   => [
			'start' => '13:00',
			'eind'  => '15:00',
		],
		NAMIDDAG => [
			'start' => '16:30',
			'eind'  => '18:30',
		],
	];

	/**
	 * De communicatie met de klant
	 *
	 * @var array $communicatie Bevat de gevoerde communicatie
	 */
	public array $communicatie = [];

	/**
	 * Het custom post aanvraag object
	 *
	 * @var array $aanvraag De aanvraag.
	 */
	private array $aanvraag;

	/**
	 * Constructor
	 *
	 * @param mixed $key Eventuele om het object op te halen.
	 */
	public function __construct( mixed $key = null ) {
		$this->aanvraag = [
			'ID'          => 0,
			'post_type'   => self::POST_TYPE,
			'post_status' => self::NIEUW,
		];
		if ( ! is_null( $key ) ) {
			if ( is_object( $key ) ) {
				$this->aanvraag = (array) $key;
			} elseif ( 0 < intval( $key ) ) {
				$this->aanvraag = (array) get_post( $key );
			} elseif ( is_string( $key ) ) {
				$result = $this->find_by_email( $key );
				if ( is_object( $result ) ) {
					$this->aanvraag = (array) $result;
				}
			}
		}
		if ( ! empty( $this->aanvraag['post_content'] ) ) {
			$this->communicatie = unserialize( base64_decode( $this->post_content ) ); // phpcs:ignore
		}
	}

	/**
	 * Magic get function
	 *
	 * @param string $attribuut Het attribuut.
	 *
	 * @return mixed
	 */
	public function __get( string $attribuut ) {
		if ( property_exists( 'WP_Post', $attribuut ) ) {
			return $this->aanvraag[ $attribuut ];
		}
		$details = maybe_unserialize( $this->aanvraag['post_excerpt'] );
		if ( isset( $details[ $attribuut ] ) ) {
			return $details[ $attribuut ];
		}
		return null;
	}

	/**
	 * Setter magic function
	 *
	 * @param string $attribuut Het attribuut.
	 * @param mixed  $value     De waarde.
	 */
	public function __set( string $attribuut, mixed $value ) {
		if ( property_exists( 'WP_Post', $attribuut ) ) {
			$this->aanvraag[ $attribuut ] = $value;
			return;
		}
		$details                        = maybe_unserialize( $this->aanvraag['post_excerpt'] ?? '' ) ?: [];
		$details[ $attribuut ]          = $value;
		$this->aanvraag['post_excerpt'] = maybe_serialize( $details );
	}

	/**
	 * Bewaar de aanvraag
	 *
	 * @return int Het workshop id.
	 */
	public function save() : int {
		$this->post_content = base64_encode( serialize( $this->communicatie ) ); // phpcs:ignore
		if ( $this->ID ) {
			wp_update_post( $this->aanvraag );
			return $this->ID;
		}
		$result   = wp_insert_post( $this->aanvraag );
		$this->ID = is_int( $result ) ? $result : 0;
		return $this->ID;
	}

	/**
	 * Start een nieuwe casus en email de aanvrager
	 *
	 * @param array $casus_data De gegevens behorende bij de casus.
	 */
	public function start( array $casus_data ) {
		$emailer           = new Email();
		$this->post_title  = $casus_data['contact'] . ' met vraag over ' . $casus_data['naam'];
		$this->post_name   = $casus_data['user_email'];
		$this->email       = $casus_data['user_email'];
		$this->contact     = $casus_data['contact'];
		$this->omvang      = $casus_data['omvang'];
		$this->periode     = $casus_data['periode'] ?? '';
		$this->plandatum   = $casus_data['plandatum'] ?? 0;
		$this->dagdeel     = $casus_data['dagdeel'] ?? '';
		$this->telnr       = $casus_data['telnr'];
		$this->naam        = $casus_data['naam'];
		$this->technieken  = $casus_data['technieken'];
		$this->workshop_id = 0;
		$this->communicatie(
			[
				'tekst'   => $casus_data['vraag'],
				'type'    => 'aanvraag',
				'from'    => $casus_data['contact'],
				'subject' => '',
			]
		);
		$this->ID = $this->save();
		$emailer->send(
			[
				'to'         => "{$casus_data['contact']} <{$casus_data['user_email']}>",
				'subject'    => sprintf( "[WA#%08d] Bevestiging {$casus_data['naam']} vraag", $this->ID ),
				'from'       => $this->mbx() . $emailer->verzend_domein,
				'reply-to'   => $this->mbx() . $emailer->domein,
				'slug'       => 'workshop_aanvraag_bevestiging',
				'parameters' => [
					'naam'       => strtolower( $casus_data['naam'] ),
					'contact'    => $casus_data['contact'],
					'vraag'      => $casus_data['vraag'],
					'omvang'     => $casus_data['omvang'],
					'dagdeel'    => strtolower( ( $casus_data['dagdeel'] ) ),
					'technieken' => implode( ', ', $casus_data['technieken'] ),
					'datum'      => strftime( '%A, %d-%m-%y', $casus_data['plandatum'] ),
				],
				'sign_email' => false,
				'auto'       => 'reply',
			]
		);
	}

	/**
	 * Verander de status van de casus naar gepland.
	 *
	 * @param int $workshop_id De id van de workshop.
	 */
	public function gepland( int $workshop_id ) {
		if ( $this->ID && $this->workshop_id !== $workshop_id ) {
			$this->workshop_id = $workshop_id;
			$this->post_status = $workshop_id ? self::GEPLAND : self::GEREAGEERD;
			$this->save();
		}
	}

	/**
	 * Bepaal of de aanvraag al afgehandeld is. Als de aanvraag al ouder is dan x weken of er is al een workshop ingepland, dan is de aanvraag verwerkt.
	 *
	 * @return bool True als nog in verwerking.
	 */
	public function is_inverwerking() : bool {
		if ( $this->ID ) {
			return strtotime( $this->post_date ) > strtotime( '- ' . opties()['verloopaanvraag'] . 'week' ) && ! $this->workshop_id;
		}
		return false;
	}

	/**
	 * Voeg een reactie toe en email de aanvrager.
	 *
	 * @param string $reactie     De reactie op de vraag.
	 */
	public function reactie( string $reactie ) {
		$emailer           = new Email();
		$subject           = sprintf( "[WA#%08d] Reactie op $this->naam vraag", $this->ID );
		$this->post_status = self::GEREAGEERD;
		$this->communicatie(
			[
				'type'    => 'reactie',
				'from'    => wp_get_current_user()->display_name,
				'tekst'   => $reactie,
				'subject' => $subject,
			]
		);
		$this->save();
		$emailer->send(
			[
				'to'         => "$this->contact  <$this->email>",
				'from'       => $this->mbx() . $emailer->verzend_domein,
				'sign'       => wp_get_current_user()->display_name . ',<br/>Kleistad',
				'reply-to'   => $this->mbx() . $emailer->domein,
				'subject'    => $subject,
				'slug'       => 'workshop_aanvraag_reactie',
				'auto'       => false,
				'parameters' => [
					'reactie' => nl2br( $reactie ),
					'contact' => $this->contact,
					'naam'    => $this->naam,
				],
				'sign_email' => false,
			]
		);
	}

	/**
	 * Initialiseer de aanvragen als custom post type.
	 */
	public static function create_type() {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'               => 'Workshop aanvragen',
					'singular_name'      => 'Workshop aanvraag',
					'add_new'            => 'Toevoegen',
					'add_new_item'       => 'Aanvraag toevoegen',
					'edit'               => 'Wijzigen',
					'edit_item'          => 'Aanvraag wijzigen',
					'view'               => 'Inzien',
					'view_item'          => 'Aanvraag inzien',
					'search_items'       => 'Aanvraag zoeken',
					'not_found'          => 'Niet gevonden',
					'not_found_in_trash' => 'Niet in prullenbak gevonden',
				],
				'public'              => true,
				'supports'            => [
					'title',
					'comments',
					'thumbnail',
				],
				'rewrite'             => [
					'slug' => 'workshopaanvragen',
				],
				'show_ui'             => false,
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'exclude_from_search' => true,
			]
		);
		register_post_status(
			self::NIEUW,
			[
				'label'     => 'nieuwe aanvraag',
				'post_type' => self::POST_TYPE,
				'public'    => true,
			]
		);
		register_post_status(
			self::GEREAGEERD,
			[
				'label'     => 'gereageerd naar aanvrager',
				'post_type' => self::POST_TYPE,
				'public'    => true,
			]
		);
		register_post_status(
			self::VRAAG,
			[
				'label'     => 'aanvrager heeft nieuwe vraag gesteld',
				'post_type' => self::POST_TYPE,
				'public'    => true,
			]
		);
		register_post_status(
			self::GEPLAND,
			[
				'label'     => 'de workshop is ingepland',
				'post_type' => self::POST_TYPE,
				'public'    => true,
			]
		);
	}

	/**
	 * Verwerk een ontvangen email.
	 *
	 * @param array $email De ontvangen email.
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	public static function verwerk( array $email ) {
		$emailer = new Email();
		/**
		 * Zoek eerst op basis van het case nummer in subject.
		 */
		if ( 2 === sscanf( $email['subject'], '%*[^[WA#][WA#%u]', $aanvraag_id ) ) {
			$result = get_post( $aanvraag_id );
		} else {
			/**
			 * Als niet gevonden probeer dan te zoeken op het email adres van de afzender.
			 */
			$result = self::find_by_email( $email['from'] );
		}
		if ( is_object( $result ) && self::POST_TYPE === $result->post_type ) {
			$aanvraag = new self( $result );
			$emailer->send(
				[
					'to'      => "Workshop mailbox <$emailer->info@$emailer->domein>",
					'subject' => "aanvraag $aanvraag->naam",
					'content' => "<p>Er is een reactie ontvangen van {$email['from-name']}</p>",
					'sign'    => 'Workshop mailbox',
				]
			);
			$aanvraag->post_status = self::VRAAG;
			$aanvraag->communicatie(
				[
					'type'    => 'vraag',
					'from'    => $email['from-name'],
					'subject' => $email['subject'],
					'tekst'   => $email['content'],
				]
			);
			$aanvraag->save();
			return;
		}
		$email['to'] = "Kleistad <$emailer->info@$emailer->domein>";
		$emailer->send( $email );
	}

	/**
	 * Geef het begin van de email aan.
	 *
	 * @return string
	 */
	private function mbx() : string {
		return 'production' === wp_get_environment_type() ? 'workshops@' : ( strtok( get_bloginfo( 'admin_email' ), '@' ) . 'workshops@' );
	}

	/**
	 * Voeg de communicatie toe aan de ticket.
	 *
	 * @param array $parameters De parameters van de communicatie.
	 */
	private function communicatie( array $parameters ) {
		$parameters['tijd'] ??= current_time( 'd-m-Y H:i' );
		array_unshift(
			$this->communicatie,
			str_replace(
				[ '{', '}' ],
				[ '&#123', '&#125' ],
				$parameters
			)
		);
	}

	/**
	 * Zoek de aanvraag obv het email adres.
	 *
	 * @param string $email Email adres waar naar gezocht moet worden.
	 *
	 * @return bool|WP_Post
	 */
	private static function find_by_email( string $email ): bool|WP_Post {
		$posts = get_posts(
			[
				'post_type'   => self::POST_TYPE,
				'post_name'   => $email,
				'post_status' => 'any',
				'numberposts' => '1',
				'orderby'     => 'date',
				'order'       => 'DESC',
			]
		);
		return count( $posts ) ? $posts[0] : false;
	}
}
