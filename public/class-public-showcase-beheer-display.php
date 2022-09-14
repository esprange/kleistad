<?php
/**
 * Toon het showcase beheer formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.0.87
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
		$this->form();
	}

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function wijzigen() : void {
		$this->form();
	}

	/**
	 * Render het formulier. Twee mogelijkheden voor resp. het lid en voor de verkoper.
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function form_content() {
		if ( isset( $this->data['actie'] ) && 'verkoop' === $this->data['actie'] ) {
			$this->tentoonstellen();
			return;
		}
		$this->aanmelden();
	}

	/**
	 * Formulier voor het lid om een werkstuk aan te melden.
	 *
	 * @return void
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
			<div class="kleistad-col-4">
				<strong><?php echo $this->data['showcase']->id ? esc_html( $this->data['showcase']->show_status() ) : ''; ?></strong>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-10 kleistad-label">
				<label for="kleistad_beschrijving">Beschrijving</label>
			</div>
		</div>
		<div class="kleistad-row" style="padding-top:15px">
			<div class="kleistad-col-10">
				<textarea name="beschrijving" id="kleistad_beschrijving" maxlength="1000" rows="5"><?php echo esc_textarea( $this->data['showcase']->beschrijving ); ?></textarea>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_breedte">Breedte (cm)</label>
			</div>
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_diepte">Diepte (cm)</label>
			</div>
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_hoogte">Hoogte (cm)</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3">
				<input name="breedte" id="kleistad_breedte" type="number" required value="<?php echo esc_attr( $this->data['showcase']->breedte ); ?>" />
			</div>
			<div class="kleistad-col-3">
				<input name="diepte" id="kleistad_diepte" type="number" required value="<?php echo esc_attr( $this->data['showcase']->diepte ); ?>" />
			</div>
			<div class="kleistad-col-3">
				<input name="hoogte" id="kleistad_hoogte" type="number" required value="<?php echo esc_attr( $this->data['showcase']->hoogte ); ?>" />
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
			<div class="kleistad-col-5 kleistad-label">
				<label for="kleistad_prijs">Prijs (euro)</label>
			</div>
			<div class="kleistad-col-5 kleistad-label">
				<label for="kleistad_positie">Gewenste positie</label>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-5">
				<input name="prijs" id="kleistad_prijs" type="number" min="1" required value="<?php echo esc_attr( intval( $this->data['showcase']->prijs ) ); ?>" />
			</div>
			<div class="kleistad-col-5">
				<select name="positie" id="kleistad_positie" class="kleistad-select">
					<?php
					foreach (
						[
							''             => 'Geen voorkeur',
							'vitrine'      => 'Vitrine',
							'sokkel'       => 'Sokkel',
							'stellingkast' => 'Stellingkast',
						] as $key => $value ) :
						?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $this->data['showcase']->positie, $key ); ?> ><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<?php endif; ?>
		<div class="kleistad-row">
			<div class="kleistad-col-5 kleistad-label">
				<label for="kleistad_foto">Foto (max 2M bytes)</label>
			</div>
		</div>
		<div class="kleistad-row" style="padding-top:15px">
			<div class="kleistad-col-5">
				<input type="file" name="foto" id="kleistad_foto" accept=".jpeg,.jpg,.tiff,.tif" /><br />
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
				<h2><?php echo esc_html( "{$this->data['showcase']->titel} ({$this->data['showcase']->keramist})" ); ?></h2>
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
				<label for="kleistad_breedte">Breedte </label><?php echo esc_html( $this->data['showcase']->breedte ); ?> cm
			</div>
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_diepte">Diepte </label><?php echo esc_html( $this->data['showcase']->diepte ); ?> cm
			</div>
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_hoogte">Hoogte </label><?php echo esc_attr( $this->data['showcase']->hoogte ); ?> cm
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-5 kleistad-label">
				<label for="kleistad_prijs">Prijs </label> <?php echo esc_html( intval( $this->data['showcase']->prijs ) ); ?> euro
			</div>
		</div>
		<?php if ( $this->data['showcase']->positie ) : ?>
		<div class="kleistad-row">
			<div class="kleistad-col-5 kleistad-label">
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
		$offset = ( intval( date( 'm' ) ) - 1 ) % 2;
		for ( $index = 0; $index < 3; $index ++ ) :
			$periode     = $offset + $index * 2;
			$vanaf_datum = strtotime( "first monday of $periode month 0:00" );
			$tot_datum   = strtotime( 'first monday of ' . $periode + 2 . ' month 0:00' );
			$checked     = false;
			foreach ( $this->data['showcase']->shows as $show ) :
				if ( $show['start'] >= $vanaf_datum && $show['eind'] <= $tot_datum ) :
					$checked = true;
					break;
				endif;
			endforeach;
			?>
			<div class="kleistad-col-2">
				<label for="kleistad_show_<?php echo esc_attr( $index ); ?>" class="kleistad-label"><?php echo esc_html( date_i18n( 'M', $vanaf_datum ) . '-' . date_i18n( 'M', $tot_datum ) ); ?>
				</label>
			</div>
			<div class="kleistad-col-1">
				<input name="shows[]" id="kleistad_show_<?php echo esc_attr( $index ); ?>" type="checkbox" value="<?php echo esc_attr( $vanaf_datum . ';' . $tot_datum ); ?>" <?php checked( $checked ); ?> class="kleistad-checkbox" />
			</div>
		<?php endfor; ?>
		</div>
		<button class="kleistad-button" type="submit" name="kleistad_submit_showcase_beheer" id="kleistad_submit_tentoonstellen" value="tentoonstellen">Opslaan</button>
		<button class="kleistad-button" type="submit" name="kleistad_submit_showcase_beheer" id="kleistad_submit_verkochtmelden" value="verkochtmelden">Verkocht</button>
		<button class="kleistad-button kleistad-terug-link" type="button" style="float:right" >Terug</button>
		<?php
	}

	/**
	 * Toon het overzicht van showcases van een abonnee
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function overzicht() {
		?>
		<table class="kleistad-datatable display" data-page-length="10" data-order='[[ 2, "desc" ]]'>
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
				<td><?php echo esc_html( $showcase->show_status() ); ?></td>
				<td>
					<a href="#" title="wijzig werkstuk" class="kleistad-edit kleistad-edit-link" data-id="<?php echo esc_attr( $showcase->id ); ?>" data-actie="wijzigen" >&nbsp;</a>
				</td>
			</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<button class="kleistad-button kleistad-edit-link" type="button" data-id="0" data-actie="toevoegen" >Toevoegen</button>
		<?php
	}

	/**
	 * Toon het overzicht van showcases voor de verkoper
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	protected function verkoop() : void {
		?>
		<table class="kleistad-datatable display" data-page-length="10" data-order='[[ 2, "desc" ]]'>
			<thead>
			<tr>
				<th data-orderable="false"></th>
				<th>Titel</th>
				<th>Keramist</th>
				<th>Status</th>
				<th data-orderable="false">&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $this->data['showcases'] as $showcase ) : ?>
				<tr>
					<td>
						<?php if ( $showcase->foto_id ) : ?>
							<?php echo wp_get_attachment_image( $showcase->foto_id ); ?>
							<!--" height="100" width="100" alt="< ?php echo esc_attr( $showcase->titel ); ?-->
						<?php else : ?>
							&nbsp;
						<?php endif; ?></td>
					<td><?php echo esc_html( $showcase->titel ); ?></td>
					<td><?php echo esc_html( $showcase->keramist ); ?></td>
					<td><?php echo esc_html( $showcase->show_status() ); ?></td>
					<td>
						<a href="#" title="wijzig werkstuk" class="kleistad-view kleistad-edit-link" data-id="<?php echo esc_attr( $showcase->id ); ?>" data-actie="wijzigen" >&nbsp;</a>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<button class="kleistad-button kleistad-download-link" type="button" data-actie="beschikbaar" >Download beschikbaar</button>
		<button class="kleistad-button kleistad-download-link" type="button" data-actie="verkoop" >Download verkoop</button>
		<?php
	}
}
