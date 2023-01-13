<?php
/**
 * Toon het showcase beheer formulier
 *
 * @link       https://www.kleistad.nl
 * @since      7.6.0
 *
 * @package    Kleistad
 */

namespace Kleistad;

/**
 * Render van de cursus beheer formulier.
 */
class Public_Showcase_Beheer_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function toevoegen() : void {
		$this->wijzigen();
	}

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function wijzigen() : void {
		$this->form(
			function() {
				if ( isset( $this->data['actie'] ) && 'verkoop' === $this->data['actie'] ) {
					$this->tentoonstellen();

					return;
				}
				$this->aanmelden();
			}
		);
	}

	/**
	 * Toon het overzicht van showcases van een abonnee
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function overzicht() {
		if ( count( $this->data['showcases'] ) ) :
			?>
		<table class="kleistad-datatable display" id="kleistad_showcases_beheer" data-page-length="10" data-order='[[ 2, "desc" ]]'>
			<thead>
			<tr>
				<th data-orderable="false"></th>
				<th>Titel</th>
				<th>Status</th>
				<th data-orderable="false">&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $this->data['showcases'] as $showcase ) : ?>
			<tr>
				<td>
				<?php
				if ( $showcase->foto_id ) :
					echo wp_get_attachment_image( $showcase->foto_id );
				else :
					?>
					&nbsp;
				<?php endif; ?></td>
				<td><?php echo esc_html( $showcase->titel ); ?></td>
				<td><?php echo esc_html( $showcase->get_statustekst() ); ?></td>
				<td>
					<a href="#" title="wijzig werkstuk" class="kleistad-edit kleistad-edit-link" data-id="<?php echo esc_attr( $showcase->id ); ?>" data-actie="wijzigen" >&nbsp;</a>
				</td>
			</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<?php else : ?>
		<p><strong>Je hebt nog geen werkstukken aangeboden voor verkoop. Klik op 'toevoegen werkstuk' om een werkstuk aan te melden.</strong></p>
		<?php endif; ?>
		<div class="kleistad-row" style="padding-top: 15px;">
		<button class="kleistad-button kleistad-edit-link" type="button" data-id="0" data-actie="toevoegen" >Toevoegen werkstuk</button>
		</div>
		<?php
	}

	/**
	 * Toon het overzicht van showcases voor de verkoper
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function verkoop() : void {
		?>
		<table class="kleistad-datatable responsive display" id="kleistad_showcases" data-page-length="10" data-order='[[ 2, "desc" ]]'>
			<thead>
			<tr>
				<th data-orderable="false" data-priority="4">Foto &nbsp; &nbsp;</th>
				<th data-priority="1">Titel</th>
				<th>Keramist</th>
				<th>Nummer</th>
				<th data-priority="3">Status</th>
				<th data-orderable="false" data-priority="2">&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $this->data['showcases'] as $showcase ) : ?>
				<tr style="overflow:hidden;white-space:nowrap;text-overflow:ellipsis;" >
					<td>
						<?php if ( $showcase->foto_id ) : ?>
							<?php echo wp_get_attachment_image( $showcase->foto_id ); ?>
						<?php else : ?>
							&nbsp;
						<?php endif; ?></td>
					<td><?php echo esc_html( $showcase->titel ); ?></td>
					<td><?php echo esc_html( get_user_by( 'ID', $showcase->keramist_id )->display_name ); ?></td>
					<td><?php echo esc_html( sprintf( '%04d', $showcase->keramist_id ) ); ?></td>
					<td><?php echo esc_html( $showcase->get_statustekst() ); ?></td>
					<td>
						<a href="#" title="wijzig werkstuk" class="kleistad-view kleistad-edit-link" data-id="<?php echo esc_attr( $showcase->id ); ?>" data-actie="wijzigen" >&nbsp;</a>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<div class="kleistad-row" style="padding-top: 15px;">
			<button class="kleistad-button kleistad-download-link" type="button" data-actie="beschikbaar" >Download beschikbaar</button>
			<button class="kleistad-button kleistad-download-link" type="button" data-actie="verkoop" >Download verkoop</button>
		</div>
		<?php
	}

	/**
	 * Formulier voor het lid om een werkstuk aan te melden.
	 *
	 * @return void
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function aanmelden() : void {
		$tentoongesteld = $this->data['showcase']->is_tentoongesteld();
		$verkocht       = Showcase::VERKOCHT === $this->data['showcase']->status;
		?>
		<input type="hidden" name="id" value="<?php echo esc_attr( $this->data['showcase']->id ); ?>" />
		<div class="kleistad-row">
			<div class="kleistad-col-6 kleistad-label">
				<label for="kleistad_titel">Werkstuk naam</label>
			</div>
			<div class="kleistad-col-6">
				<input class="kleistad-input" type="text" size="20" name="titel" required id="kleistad_titel"
					value="<?php echo esc_attr( $this->data['showcase']->titel ); ?>"/>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-10">
				<label for="kleistad_beschrijving" class="kleistad-label">Beschrijving</label>
				<textarea name="beschrijving" id="kleistad_beschrijving" maxlength="200" rows="3"><?php echo esc_textarea( $this->data['showcase']->beschrijving ); ?></textarea>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<label for="kleistad_breedte" class="kleistad-label">Breedte (cm)</label>
				<input class="kleistad-input" name="breedte" id="kleistad_breedte" type="number" required value="<?php echo esc_attr( $this->data['showcase']->breedte ); ?>" />
			</div>
			<div class="kleistad-col-3">
				<label for="kleistad_diepte" class=" kleistad-label">Diepte (cm)</label>
				<input class="kleistad-input" name="diepte" id="kleistad_diepte" type="number" required value="<?php echo esc_attr( $this->data['showcase']->diepte ); ?>" />
			</div>
			<div class="kleistad-col-3">
				<label for="kleistad_hoogte" class="kleistad-label">Hoogte (cm)</label>
				<input class="kleistad-input" name="hoogte" id="kleistad_hoogte" type="number" required value="<?php echo esc_attr( $this->data['showcase']->hoogte ); ?>" />
			</div>
		</div>
		<?php if ( $verkocht ) : ?>
			<input type="hidden" name="prijs" value="<?php echo esc_attr( $this->data['showcase']->prijs ); ?>" />
			<input type="hidden" name="positie" value="<?php echo esc_attr( $this->data['showcase']->positie ); ?>" />
			<div class="kleistad-row">
				<div class="kleistad-col-10">
					<strong>Het werkstuk is verkocht. De prijs waarvoor het werkstuk was aangeboden bedroeg <?php echo intval( $this->data['showcase']->prijs ); ?> euro.</strong>
				</div>
			</div>
		<?php elseif ( $tentoongesteld ) : ?>
			<input type="hidden" name="prijs" value="<?php echo esc_attr( $this->data['showcase']->prijs ); ?>" />
			<input type="hidden" name="positie" value="<?php echo esc_attr( $this->data['showcase']->positie ); ?>" />
			<div class="kleistad-row">
				<div class="kleistad-col-10">
					<strong>Het werkstuk staat nu tentoongesteld. De prijs <?php echo intval( $this->data['showcase']->prijs ); ?> euro is nu niet te wijzigen.</strong>
				</div>
			</div>
		<?php else : ?>
			<div class="kleistad-row">
				<div class="kleistad-col-5">
					<label for="kleistad_prijs" class="kleistad-label">Prijs (euro)</label>
					<input class="kleistad-input" name="prijs" id="kleistad_prijs" type="number" min="1" required value="<?php echo esc_attr( intval( $this->data['showcase']->prijs ) ); ?>" />
				</div>
				<div class="kleistad-col-5">
					<label for="kleistad_btw" class="kleistad-label">BTW percentage</label>
					<select name="btw_percentage" id="kleistad_btw" class="kleistad-select">
						<?php foreach ( [ 0, 9, 21 ] as $percent ) : ?>
							<option value="<?php echo esc_attr( $percent ); ?>" <?php selected( $this->data['showcase']->btw_percentage, $percent ); ?> ><?php echo esc_html( $percent ); ?> %</option>						?>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="kleistad-row">
				<div class="kleistad-col-5">
					<label for="kleistad_positie" class="kleistad-label">Voorkeur positie</label>
					<select name="positie" id="kleistad_positie" class="kleistad-select">
						<?php
						foreach (
							[
								''              => 'Geen voorkeur',
								'vitrine'       => 'Vitrine',
								'sokkel'        => 'Sokkel',
								'stelltingkast' => 'Stellingkast',
							] as $key => $value ) :
							?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $this->data['showcase']->positie, $key ); ?> ><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		<?php endif; ?>
		<div class="kleistad-row">
			<div class="kleistad-col-5">
				<label for="kleistad_foto" class="kleistad-label">Foto (max 2M bytes)</label>
				<input type="file" name="foto" id="kleistad_foto" accept=".jpeg,.jpg,.tiff,.tif;capture=camera" /><br />
			</div>
		</div>
		<div class="kleistad-row" style="padding-top:15px;padding-bottom:15px;">
			<div class="kleistad-col-5">
				<?php
				if ( $this->data['showcase']->foto_id ) :
					echo wp_get_attachment_image( $this->data['showcase']->foto_id, 'medium' );
				endif
				?>
			</div>
		</div>
		<button class="kleistad-button" type="submit" name="kleistad_submit_showcase_beheer" id="kleistad_submit_aanmelden" value="aanmelden" >
			<?php echo $this->data['showcase']->id ? 'Bewaren' : 'Aanmelden'; ?></button>
		<?php if ( ! $verkocht ) : ?>
			<button class="kleistad-button" type="submit" name="kleistad_submit_showcase_beheer" id="kleistad_submit_verwijderen" data-confirm="Showcase beheer|weet je zeker dat je dit werkstuk wilt verwijderen" value="verwijderen">Verwijderen</button>
		<?php endif; ?>
		<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
		<?php
	}

	/**
	 * Render het formulier voor de verkoop commissie
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function tentoonstellen() : void {
		?>
		<input type="hidden" name="id" value="<?php echo esc_attr( $this->data['showcase']->id ); ?>" />
		<div class="kleistad-row">
			<div class="kleistad-col-7">
				<h2><?php echo esc_html( $this->data['showcase']->titel . ' (' . get_user_by( 'ID', $this->data['showcase']->keramist_id )->display_name . ')' ); ?></h2>
			</div>
		</div>
		<?php if ( $this->data['showcase']->beschrijving ) : ?>
			<div class="kleistad-row" style="padding-top:15px">
				<div class="kleistad-col-10">
					<?php echo esc_textarea( $this->data['showcase']->beschrijving ); ?>
				</div>
			</div>
		<?php endif ?>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label>Breedte </label><?php echo esc_html( $this->data['showcase']->breedte ); ?> cm
			</div>
			<div class="kleistad-col-3 kleistad-label">
				<label>Diepte </label><?php echo esc_html( $this->data['showcase']->diepte ); ?> cm
			</div>
			<div class="kleistad-col-3 kleistad-label">
				<label>Hoogte </label><?php echo esc_attr( $this->data['showcase']->hoogte ); ?> cm
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label>Prijs </label> <?php echo esc_html( intval( $this->data['showcase']->prijs ) ); ?> euro
			</div>
			<div class="kleistad-col-3 kleistad-label">
				<label>BTW </label> <?php echo esc_html( intval( $this->data['showcase']->btw_percentage ) ); ?> %
			</div>
		</div>
		<?php if ( $this->data['showcase']->positie ) : ?>
			<div class="kleistad-row">
				<div class="kleistad-col-3 kleistad-label">
					<label for="kleistad_positie">Gewenste positie </label><?php echo esc_html( $this->data['showcase']->positie ); ?>
				</div>
			</div>
		<?php endif ?>
		<div class="kleistad-row" style="padding-top:15px">
			<div class="kleistad-col-5">
				<?php
				if ( $this->data['showcase']->foto_id ) :
					echo wp_get_attachment_image( $this->data['showcase']->foto_id, 'medium' );
				else :
					echo '<p>geen foto beschikbaar</p>';
				endif
				?>
			</div>
		</div>
		<div class="kleistad-row">
			<?php
			foreach ( ( new Shows() )->get_datums() as $index => $show_datum ) :
				$checked = false;
				foreach ( $this->data['showcase']->shows as $show ) :
					if ( $show['start'] >= $show_datum['start'] && $show['eind'] <= $show_datum['eind'] ) :
						$checked = true;
						break;
					endif;
				endforeach;
				?>
				<div class="kleistad-col-3" style="padding-top: 20px;padding-bottom: 20px">
					<label for="kleistad_show_<?php echo esc_attr( $index ); ?>" class="kleistad-label"><?php echo esc_html( date_i18n( 'M', $show_datum['start'] ) . '-' . date_i18n( 'M', $show_datum['eind'] ) ); ?>
						<input name="shows[]" id="kleistad_show_<?php echo esc_attr( $index ); ?>" type="checkbox" value="<?php echo esc_attr( $show_datum['start'] . ';' . $show_datum['eind'] ); ?>" <?php checked( $checked ); ?> class="kleistad-checkbox" />
					</label>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( Showcase::VERKOCHT !== $this->data['showcase']->status ) : ?>
			<button class="kleistad-button" type="submit" name="kleistad_submit_showcase_beheer" id="kleistad_submit_tentoonstellen" value="tentoonstellen">Bewaren</button>
			<button class="kleistad-button" type="submit" name="kleistad_submit_showcase_beheer" id="kleistad_submit_verkochtmelden" value="verkochtmelden">Verkocht</button>
		<?php endif; ?>
		<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
		<?php
	}

}
