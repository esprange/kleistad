<?php
/**
 * De  abstracte class voor shortcode display.
 *
 * @link       https://www.kleistad.nl
 * @since      6.7.1
 *
 * @package    Kleistad
 * @subpackage Kleistad/includes
 */

namespace Kleistad;

use Mollie\Api\Exceptions\ApiException;

/**
 * De abstract class voor shortcodes
 */
abstract class Public_Shortcode_Display {

	/**
	 * De weer te geven data
	 *
	 * @var array $data De data.
	 */
	protected array $data;

	/**
	 * De uit te voeren actie
	 *
	 * @var string $display_actie De actie.
	 */
	protected string $display_actie;

	/**
	 * Constructor
	 *
	 * @param array  $data          De weer te geven data.
	 * @param string $display_actie De te tonen actie.
	 */
	public function __construct( array $data, string $display_actie ) {
		$this->data          = $data;
		$this->display_actie = $display_actie;
	}

	/**
	 * De render functie
	 *
	 * @return string
	 */
	public function render() : string {
		ob_start();
		$this->{$this->display_actie}();
		return ob_get_clean();
	}

	/**
	 * Toon een OK button in het midden van het scherm
	 *
	 * @return Public_Shortcode_Display
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	public function home() : Public_Shortcode_Display {
		if ( ! is_user_logged_in() ) {
			$url = home_url();
		} elseif ( current_user_can( BESTUUR ) ) {
			$url = home_url( '/bestuur/' );
		} else {
			$url = home_url( '/leden/' );
		}
		?>
		<br/><br/>
		<div style="text-align:center;" >
			<button class="kleistad-button" type="button" onclick="location.href='<?php echo esc_url( $url ); ?>';" >
				&nbsp;OK&nbsp;
			</button>
		</div>
		<?php
		return $this;
	}

	/**
	 * Helper functie voor een formulier
	 *
	 * @param string $form_content De formulier functie.
	 */
	protected function form( string $form_content = 'form_content' ) {
		if ( method_exists( $this, $form_content ) ) {
			?>
		<form action="#" autocomplete="off" enctype="multipart/form-data" class="kleistad-form" >
			<?php
			$this->$form_content();
			?>
		</form>
			<?php
		}
	}

	/**
	 * Toon de gebruikers, bijv. ingeval een beheerder namens een bestaande gebruiker optreedt.
	 *
	 * @param string $label De tekst in het label.
	 * @return Public_Shortcode_Display
	 */
	protected function gebruiker_selectie( string $label ) : Public_Shortcode_Display {
		?>
		<div class="kleistad-row" >
			<div class="kleistad-col-3 kleistad-label" >
				<label for="kleistad_gebruiker_id"><?php echo esc_html( $label ); ?></label>
			</div>
			<div class="kleistad-col-7">
				<select class="kleistad-input" name="gebruiker_id" id="kleistad_gebruiker_id" >
					<?php foreach ( $this->data['gebruikers'] as $gebruiker ) { ?>
						<option value="<?php echo esc_attr( $gebruiker->ID ); ?>"><?php echo esc_html( $gebruiker->display_name ); ?></option>
					<?php } ?>
				</select>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Geef de gebruiker info die al ingelogd is.
	 *
	 * @return Public_Shortcode_Display
	 */
	protected function gebruiker_logged_in() : Public_Shortcode_Display {
		?>
		<div>
			<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" />
		</div>
		<?php
		return $this;
	}

	/**
	 * De invoervelden voor een opgave van een nieuwe gebruiker
	 *
	 * @return Public_Shortcode_Display
	 */
	protected function gebruiker() : Public_Shortcode_Display {
		?>
		<div>
			<div class="kleistad-row">
				<div class="kleistad-col-6">
					<label class="kleistad-label">Wat zijn je contact gegevens</label>
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-3 kleistad-label">
					<label for="kleistad_voornaam">Voornaam</label>
				</div>
				<div class="kleistad-col-4">
					<input class="kleistad-input" name="first_name" id="kleistad_voornaam" type="text"
					required maxlength="25" pattern="^[ a-zA-Z\-']+$" placeholder="voornaam" title="Vul s.v.p. de voornaam correct in"
					value="<?php echo esc_attr( $this->data['input']['first_name'] ); ?>" autocomplete="given-name" />
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-3 kleistad-label">
					<label for="kleistad_achternaam">Achternaam</label>
				</div>
				<div class="kleistad-col-4">
					<input class="kleistad-input" name="last_name" id="kleistad_achternaam" type="text"
					required maxlength="25" pattern="^[ a-zA-Z\-']+$" placeholder="achternaam" title="Vul s.v.p. de achternaam correct in"
					value="<?php echo esc_attr( $this->data['input']['last_name'] ); ?>" autocomplete="family-name" />
				</div>
			</div>
			<?php $this->email()->telnr(); ?>
			<div class="kleistad-row">
				<div class="kleistad-col-3 kleistad-label">
					<label for="kleistad_pcode">Postcode, huisnummer</label>
				</div>
				<div class="kleistad-col-2">
					<input class="kleistad-input" name="pcode" id="kleistad_pcode" type="text"
						maxlength="10" placeholder="1234AB" pattern="^[1-9][0-9]{3}?[A-Z]{2}$" title="Vul s.v.p. een geldige Nederlandse postcode in"
						value="<?php echo esc_attr( $this->data['input']['pcode'] ); ?>" autocomplete="postal-code" />
				</div>
				<div class="kleistad-col-2">
					<input class="kleistad-input" name="huisnr" id="kleistad_huisnr" type="text"
						maxlength="10" placeholder="nr" title="Vul s.v.p. een huisnummer in"
						value="<?php echo esc_attr( $this->data['input']['huisnr'] ); ?>" />
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-3 kleistad-label">
					<label for="kleistad_straat">Straat, Plaats</label>
				</div>
				<div class="kleistad-col-3">
					<input class="kleistad-input" name="straat" id="kleistad_straat" type="text" tabindex="-1"
					maxlength="50" placeholder="straat" title="Vul s.v.p. een straatnaam in"
					value="<?php echo esc_attr( $this->data['input']['straat'] ); ?>" />
				</div>
				<div class="kleistad-col-4">
					<input class="kleistad-input" name="plaats" id="kleistad_plaats" type="text" tabindex="-1"
					maxlength="50" placeholder="MijnWoonplaats" title="Vul s.v.p. de woonplaats in"
					value="<?php echo esc_attr( $this->data['input']['plaats'] ); ?>" />
				</div>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Input eventuele opmerking
	 *
	 * @param string $titel De titel die bij de opmerking getoond kan worden.
	 * @return Public_Shortcode_Display
	 */
	protected function opmerking( string $titel = 'Wat is je ervaring met klei? Je kunt hier ook andere opmerkingen achterlaten die van belang zouden kunnen zijn voor Kleistad' ) : Public_Shortcode_Display {
		if ( is_super_admin() ) {
			return $this;
		}
		?>
		<div class ="kleistad-row" title="<?php echo esc_attr( $titel ); ?>" >
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_opmerking">Opmerking</label>
			</div>
			<div class="kleistad-col-7 kleistad-input">
				<textarea class="kleistad-input" name="opmerking" id="kleistad_opmerking" maxlength="1000" rows="5" cols="50"><?php echo esc_textarea( $this->data['input']['opmerking'] ); ?></textarea>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Input eventuele aanmelding nieuwsbrief
	 *
	 * @return Public_Shortcode_Display
	 */
	protected function nieuwsbrief() : Public_Shortcode_Display {
		if ( is_super_admin() ) {
			return $this;
		}
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				<input type="checkbox" name="mc4wp-subscribe" id="subscribe" value="1" <?php checked( $this->data['input']['mc4wp-subscribe'], '1' ); ?> />
				<label for="subscribe">Ik wil de Kleistad nieuwsbrief ontvangen.</label>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Accepteer een eventuele verklaring
	 *
	 * @return Public_Shortcode_Display
	 */
	protected function verklaring() : Public_Shortcode_Display {
		if ( empty( $this->data['verklaring'] ) || is_super_admin() ) {
			return $this;
		}
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				<input type="checkbox" id="verklaring" required />
				<label for="verklaring"><?php echo $this->data['verklaring']; // phpcs:ignore ?></label>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Invoegen contact in tekst.
	 */
	protected function contact() {
		if ( empty( $this->data['contact'] ) ) {
			?>
			contact
			<?php
			return;
		}
		?>
			<a href="<?php echo esc_url( $this->data['contact'] ); ?>">contact</a>
		<?php
	}

	/**
	 * Render de ideal betaal sectie
	 */
	protected function ideal() {
		try {
			$service = new MollieClient();
			$banks   = $service->get_banks();
		} catch ( ApiException ) {
			echo melding( 0, 'Er is helaas iets misgegaan, probeer het later eventueel opnieuw' ); // phpcs:ignore
			return;
		}
		?>
		<img src="<?php echo esc_url( plugins_url( '../public/images/iDEAL_48x48.png', __FILE__ ) ); ?>" alt="iDEAL" style="padding-left:40px"/>
		<label for="kleistad_bank" class="kleistad-label">Mijn bank:&nbsp;</label>
		<select name="bank" id="kleistad_bank" class="kleistad-selectmenu" style="display:none;">
			<option value="" data-class="kleistad-bank" data-style="background-image: url();" >&nbsp;</option>
			<?php foreach ( $banks as $bank ) : ?>
				<option value="<?php echo esc_attr( $bank->id ); ?>"
					data-class="kleistad-bank"
					data-style="background-image: url(&apos;<?php echo esc_attr( $bank->image->size1x ); ?>&apos;);" >
					&nbsp;<?php echo esc_html( $bank->name ); ?>
				</option>
			<?php endforeach ?>
		</select>
		<?php
	}

	/**
	 * Render de betaal sectie
	 */
	protected function betaal_info() {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				<label class="kleistad-label">Bepaal de wijze van betalen.</label>
			</div>
		</div>
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<input type="radio" name="betaal" id="kleistad_betaal_ideal" value="ideal" <?php checked( $this->data['input']['betaal'], 'ideal' ); ?> />
				<label for="kleistad_betaal_ideal"></label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				<?php $this->ideal(); ?>
			</div>
		</div>
		<div class ="kleistad-row">
			<div class="kleistad-col-10">
				<input type="radio" name="betaal" id="kleistad_betaal_stort" required value="stort" <?php checked( $this->data['input']['betaal'], 'stort' ); ?> />
				<label for="kleistad_betaal_stort"></label>
			</div>
		</div>
		<?php
	}

	/**
	 * De invoervelden voor een opgave van een email adres
	 *
	 * @return Public_Shortcode_Display
	 */
	protected function email() : Public_Shortcode_Display {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_emailadres">Email adres</label>
			</div>
			<div class="kleistad-col-4">
				<input class="kleistad-input" name="user_email" id="kleistad_emailadres" type="email"
				required placeholder="mijnemailadres@voorbeeld.nl" pattern="^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$"
				title="Vul s.v.p. een geldig email adres in"
				value="<?php echo esc_attr( $this->data['input']['user_email'] ); ?>" autocomplete="email" />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_emailadres_controle">Email adres (controle)</label>
			</div>
			<div class="kleistad-col-4">
				<input class="kleistad-input" name="email_controle" id="kleistad_emailadres_controle" type="email"
				required title="Vul ter controle s.v.p. opnieuw het email adres in"
				value="<?php echo esc_attr( $this->data['input']['email_controle'] ); ?>" />
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * De invoervelden voor een opgave van een telefoonnr
	 *
	 * @return Public_Shortcode_Display
	 */
	protected function telnr() : Public_Shortcode_Display {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_telefoon">Telefoon</label>
			</div>
			<div class="kleistad-col-2">
				<input class="kleistad-input" name="telnr" id="kleistad_telefoon" type="text"
				maxlength="15" placeholder="0123456789" title="Vul s.v.p. een geldig telefoonnummer in"
				value="<?php echo esc_attr( $this->data['input']['telnr'] ); ?>" autocomplete="tel" />
			</div>
		</div>
		<?php
		return $this;
	}
}
