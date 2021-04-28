<?php
/**
 * Toon het recept formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de email formulier.
 */
class Public_Recept_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function html() {
		if ( isset( $this->data['recept'] ) ) {
			$this->details();
			return;
		}
		$this->overzicht();
	}

	/**
	 * Render het recept
	 *
	 * @return Public_Recept_Display
	 */
	private function details() : Public_Recept_Display {
		?>
		<button class="kleistad-button" id="kleistad_recept_print" >Afdrukken</button>
		<div class="kleistad_recept" >
			<h2><?php echo esc_html( $this->data['recept']['titel'] ); ?></h2>
			<div style="width:100%">
				<div style="float:left;width:30%;">
					<img src="<?php echo esc_url( $this->data['recept']['content']['foto'] ); ?>" width="100%" >
				</div>
				<div style="float:left;width:70%;">
					<table>
					<tr>
						<th>Type glazuur</th>
						<td><?php echo esc_html( $this->data['recept']['glazuur'] ); ?></td>
					</tr>
					<tr>
						<th>Uiterlijk</th>
						<td><?php echo esc_html( $this->data['recept']['uiterlijk'] ); ?></td>
					</tr>
					<tr>
						<th>Kleur</th>
						<td><?php echo esc_html( $this->data['recept']['kleur'] ); ?></td>
					</tr>
					<tr>
						<th>Stookschema</th>
						<td><?php echo $this->data['recept']['content']['stookschema']; // phpcs:ignore ?></td>
					</tr>
					</table>
				</div>
			</div>
			<div style="clear:both;">
				<table>
					<tr>
						<th>Auteur</th>
						<td><?php echo esc_html( $this->data['recept']['author'] ); ?></td>
						<th>Laatste wijziging</th>
						<td><?php echo esc_html( strftime( '%A %d-%m-%y', $this->data['recept']['modified'] ) ); ?></td>
					</tr>
					<tr>
						<th colspan="2">Basis recept</th>
						<th colspan="2">Toevoegingen</th>
					</tr>
					<tr>
						<td colspan="2">
							<table>
						<?php
						foreach ( $this->data['recept']['content']['basis'] as $basis ) :
							?>
								<tr>
									<td><?php echo esc_html( $basis['component'] ); ?></td>
									<td><?php echo esc_html( $basis['gewicht'] ); ?> gr.</td>
								</tr>
							<?php
						endforeach;
						?>
							</table>
						</td>
						<td colspan="2">
							<table>
						<?php
						foreach ( $this->data['recept']['content']['toevoeging'] as $toevoeging ) :
							?>
								<tr>
									<td><?php echo esc_html( $toevoeging['component'] ); ?></td>
									<td><?php echo esc_html( $toevoeging['gewicht'] ); ?> gr.</td>
								</tr>
							<?php
						endforeach;
						?>
							</table>
						</td>
					</tr>
				</table>
			</div>
			<div>
				<h3>Kenmerken</h3>
				<?php echo $this->data['recept']['content']['kenmerk']; // phpcs:ignore ?>
			</div>
			<div>
				<h3>Oorsprong</h3>
				<?php echo $this->data['recept']['content']['herkomst']; // phpcs:ignore ?>
			</div>
		</div>
		<?php
		return $this;
	}

	/**
	 * Render het overzicht van recepten
	 *
	 * @return Public_Recept_Display
	 */
	private function overzicht() : Public_Recept_Display {
		?>
		<div class="kleistad-row" style="padding-bottom:15px;">
			<div class="kleistad-col-2">
				<label for="kleistad_zoek" >Zoek een recept</label>
			</div>
			<div class="kleistad-col-4" style="position: relative;">
				<input type="search" id="kleistad_zoek" style="height:40px;" placeholder="zoeken..." value="" >
				<button class="kleistad-button" type="button" id="kleistad_zoek_icon" style="height:40px;position:absolute;right:0px;z-index:2;"><span class="dashicons dashicons-search"></span></button>
			</div>
			<div class="kleistad-col-2" style="text-align:right;">
				<label for="kleistad_sorteer" >Sorteer op</label>
			</div>
			<div class="kleistad-col-2">
				<select id="kleistad_sorteer" >
					<option value="titel">Titel</option>
					<?php if ( function_exists( 'the_ratings' ) ) : ?>
					<option value="waardering">Waardering</option>
					<?php endif ?>
					<option value="nieuwste" selected>Nieuwste</option>
				</select>
			</div>
		</div>
		<div class="kleistad-row">
			<button class="kleistad-button" type="button" id="kleistad_filter_btn"></button>
		</div>
		<div class="kleistad_recepten" id="kleistad_recepten">
			de recepten worden opgehaald...
		</div>
		<?php
		return $this;
	}

}
