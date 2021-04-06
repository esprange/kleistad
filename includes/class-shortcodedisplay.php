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

/**
 * De abstract class voor shortcodes
 */
abstract class ShortcodeDisplay {

	/**
	 * De weer te geven data
	 *
	 * @var array $data De data.
	 */
	protected array $data;

	/**
	 * De functie die de html aanmaakt
	 *
	 * @return void
	 */
	abstract protected function html();

	/**
	 * Constructor
	 *
	 * @param array $data De weer te geven data.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * De render functie
	 *
	 * @return string
	 */
	public function render() : string {
		ob_start();
		$this->html();
		return ob_get_clean();
	}

	/**
	 * Helper functie voor een formulier
	 */
	protected function form() : ShortcodeDisplay {
		?>
		<form action="#" autocomplete="off" enctype="multipart/form-data" >
		<?php
		return $this;
	}

	/**
	 * Toon de gebruikers, bijv. ingeval een beheerder namens een bestaande gebruiker optreedt.
	 *
	 * @return ShortcodeDisplay
	 */
	protected function gebruiker_selectie() : ShortcodeDisplay {
		?>
		<div class="kleistad-row" >
			<div class="kleistad-col-3 kleistad-label" >
				<label for="kleistad_gebruiker_id">Cursist</label>
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
	 * @return ShortcodeDisplay
	 */
	protected function gebruiker_logged_in() : ShortcodeDisplay {
		?>
		<input type="hidden" name="gebruiker_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" />
		<input type="hidden" name="aantal" id="kleistad_aantal" value="1" />
		<?php
		return $this;
	}

	/**
	 * De invoervelden voor een opgave van een nieuwe gebruiker
	 *
	 * @return ShortcodeDisplay
	 */
	protected function gebruiker() : ShortcodeDisplay {
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_voornaam">Voornaam</label>
			</div>
			<div class="kleistad-col-4">
				<input class="kleistad-input" name="first_name" id="kleistad_voornaam" type="text"
				required maxlength="25" placeholder="voornaam" title="Vul s.v.p. de voornaam in"
				value="<?php echo esc_attr( $this->data['input']['first_name'] ); ?>" autocomplete="given-name" />
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_achternaam">Achternaam</label>
			</div>
			<div class="kleistad-col-4">
				<input class="kleistad-input" name="last_name" id="kleistad_achternaam" type="text"
				required maxlength="25" placeholder="achternaam" title="Vul s.v.p. de achternaam in"
				value="<?php echo esc_attr( $this->data['input']['last_name'] ); ?>" autocomplete="family-name" />
			</div>
		</div>
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
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_telnr">Telefoon</label>
			</div>
			<div class="kleistad-col-2">
				<input class="kleistad-input" name="telnr" id="kleistad_telnr" type="text"
				maxlength="15" placeholder="0123456789" title="Vul s.v.p. een geldig telefoonnummer in"
				value="<?php echo esc_attr( $this->data['input']['telnr'] ); ?>" autocomplete="tel" />
			</div>
		</div>
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
		<?php
		return $this;
	}

	/**
	 * Input eventuele opmerking
	 *
	 * @return ShortcodeDisplay
	 */
	protected function opmerking() : ShortcodeDisplay {
		?>
		<div class ="kleistad-row" title="Wat is je ervaring met klei? Je kunt hier ook andere opmerkingen achterlaten die van belang zouden kunnen zijn voor Kleistad" >
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_opmerking">Opmerking</label>
			</div>
			<div class="kleistad-col-7 kleistad-input">
				<textarea class="kleistad-input" name="opmerking" id="kleistad_opmerking" maxlength="1000" rows="3" cols="50"><?php echo esc_textarea( $this->data['input']['opmerking'] ); ?></textarea>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Toon een OK button in het midden van het scherm
	 *
	 * @return ShortcodeDisplay
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function goto_home() : ShortcodeDisplay {
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
			<button type="button" onclick="location.href='<?php echo esc_url( $url ); ?>';" >
				&nbsp;OK&nbsp;
			</button>
		</div>
		<?php
		return $this;
	}



	/**
	 * Input eventuele aanmelding nieuwsbrief
	 *
	 * @return ShortcodeDisplay
	 */
	protected function nieuwsbrief() : ShortcodeDisplay {
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
	 * @return ShortcodeDisplay
	 */
	protected function verklaring() : ShortcodeDisplay {
		if ( empty( $this->data['verklaring'] ) ) {
			return $this;
		}
		?>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				<input type="checkbox" id="verklaring" onchange="document.getElementById( 'kleistad_submit' ).disabled = !this.checked;" />
				<label for="verklaring"><?php echo $this->data['verklaring']; // phpcs:ignore ?></label>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render de ideal betaal sectie
	 *
	 * @return void
	 */
	protected function ideal() {
		$betalen = new Betalen();
		?>
		<img src="<?php echo esc_url( plugins_url( '../public/images/iDEAL_48x48.png', __FILE__ ) ); ?>" alt="iDEAL" style="padding-left:40px"/>
		<label for="kleistad_bank" class="kleistad-label">Mijn bank:&nbsp;</label>
		<select name="bank" id="kleistad_bank" style="padding-left:15px;width: 200px;font-weight:normal">
			<option value="" >&nbsp;</option>
			<?php foreach ( $betalen->banken() as $bank ) : ?>
				<option value="<?php echo esc_attr( $bank->id ); ?>"><?php echo esc_html( $bank->name ); ?></option>
			<?php endforeach ?>
		</select>
		<?php
	}
}
