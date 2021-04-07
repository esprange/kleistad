<?php
/**
 * Toon het recept beheer formulier
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
class Public_Recept_Beheer_Display extends Public_Shortcode_Display {

	/**
	 * Render het formulier
	 *
	 * @return void
	 */
	protected function html() {
		if ( isset( $this->data['recept'] ) ) {
			$this->form()->edit()->form_end();
			return;
		}
		$this->overzicht();
	}

	/**
	 * Toon het overzicht van recepten
	 *
	 * @return Public_Recept_Beheer_Display
	 */
	private function overzicht() : Public_Recept_Beheer_Display {
		?>
		<table class="kleistad-datatable display" data-page-length="5" data-order='[[ 2, "desc" ]]'>
			<thead>
			<tr>
				<th data-orderable="false">Glazuur</th>
				<th>Titel</th>
				<th>Datum</th>
				<th>Status</th>
				<th data-orderable="false">&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $this->data['recepten'] as $recept ) : ?>
			<tr>
				<td>
				<?php if ( '' !== $recept['foto'] ) : ?>
					<img src="<?php echo esc_url( $recept['foto'] ); ?>" height="100" width="100" alt="<?php echo esc_attr( $recept['titel'] ); ?>" >
					<?php else : ?>
					&nbsp;
				<?php endif; ?></td>
				<td><?php echo esc_html( $recept['titel'] ); ?></td>
				<td data-sort="<?php echo esc_attr( $recept['modified'] ); ?>"><?php echo esc_html( date_i18n( 'd-m-Y H:i', $recept['modified'] ) ); ?></td>
				<td><?php echo esc_html( 'private' === $recept['status'] ? 'prive' : ( 'publish' === $recept['status'] ? 'gepubliceerd' : ( 'draft' === $recept['status'] ? 'concept' : '' ) ) ); ?></td>
				<td>
					<a href="#" title="wijzig recept" class="kleistad-edit kleistad-edit-link" data-id="<?php echo esc_attr( $recept['id'] ); ?>" data-actie="wijzigen" >&nbsp;</a>
				</td>
			</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<button type="button" class="kleistad-edit kleistad-edit-link" data-id="0" data-actie="toevoegen" >Toevoegen</button>
		<?php
		return $this;
	}

	/**
	 * Render het formulier
	 *
	 * @return Public_Recept_Beheer_Display
	 * @suppressWarnings(PHPMD.ElseExpression)
	 */
	private function edit() : Public_Recept_Beheer_Display {
		?>
		<input type="hidden" name="id" value="<?php echo esc_attr( $this->data['recept']['id'] ); ?>" />
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_titel">Recept naam</label>
			</div>
			<div class="kleistad-col-7">
				<input class="kleistad-input" type="text" size="20" name="titel" tabindex="1" required id="kleistad_titel"
					value="<?php echo esc_attr( $this->data['recept']['titel'] ); ?>"/>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_glazuur">Soort glazuur</label>
			</div>
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_kleur">Kleur</label>
			</div>
			<div class="kleistad-col-3 kleistad-label">
				<label for="kleistad_uiterlijk">Uiterlijk</label>
			</div>
		</div>
		<div class="kleistad-row" style="padding-top:15px">
			<div class="kleistad-col-3">
			<?php
			wp_dropdown_categories(
				[
					'orderby'           => 'name',
					'hide_empty'        => 0,
					'show_count'        => 0,
					'show_option_none'  => 'Kies soort glazuur',
					'option_none_value' => '',
					'class'             => 'cat',
					'taxonomy'          => 'kleistad_recept_cat',
					'hierarchical'      => 1,
					'id'                => 'kleistad_glazuur',
					'name'              => 'glazuur',
					'selected'          => $this->data['recept']['glazuur'],
					'child_of'          => Recept::hoofdtermen()[ Recept::GLAZUUR ]->term_id,
					'tabindex'          => 2,
				]
			);
			?>
			</div>
			<div class="kleistad-col-3">
			<?php
			wp_dropdown_categories(
				[
					'orderby'           => 'name',
					'hide_empty'        => 0,
					'show_count'        => 0,
					'show_option_none'  => 'Kies kleur',
					'option_none_value' => '',
					'class'             => 'cat',
					'taxonomy'          => 'kleistad_recept_cat',
					'hierarchical'      => 1,
					'id'                => 'kleistad_kleur',
					'name'              => 'kleur',
					'selected'          => $this->data['recept']['kleur'],
					'child_of'          => Recept::hoofdtermen()[ Recept::KLEUR ]->term_id,
					'tabindex'          => 3,
				]
			);
			?>
			</div>
			<div class="kleistad-col-3">
			<?php
			wp_dropdown_categories(
				[
					'orderby'           => 'name',
					'hide_empty'        => 0,
					'show_count'        => 0,
					'show_option_none'  => 'Kies uiterlijk',
					'option_none_value' => '',
					'class'             => 'cat',
					'taxonomy'          => 'kleistad_recept_cat',
					'hierarchical'      => 1,
					'id'                => 'kleistad_uiterlijk',
					'name'              => 'uiterlijk',
					'selected'          => $this->data['recept']['uiterlijk'],
					'child_of'          => Recept::hoofdtermen()[ Recept::UITERLIJK ]->term_id,
					'tabindex'          => 4,
				]
			);
			?>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-5 kleistad-label">
				<label for="kleistad_kenmerk">Kenmerken</label>
			</div>
			<div class="kleistad-col-5 kleistad-label">
				<label for="kleistad_herkomst">Herkomst</label>
			</div>
		</div>
		<div class="kleistad-row" style="padding-top:15px">
			<div class="kleistad-col-5">
				<textarea name="kenmerk" id="kleistad_kenmerk" tabindex="5" maxlength="1000" rows="5"><?php echo esc_textarea( $this->data['recept']['content']['kenmerk'] ); ?></textarea>
			</div>
			<div class="kleistad-col-5">
				<textarea name="herkomst" id="kleistad_herkomst" tabindex="6" maxlength="1000" rows="5"><?php echo esc_textarea( $this->data['recept']['content']['herkomst'] ); ?></textarea>
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-5 kleistad-label">
				<label for="kleistad_stookschema">Stookschema</label>
			</div>
			<div class="kleistad-col-5 kleistad-label">
				<label for="kleistad_foto_input">Foto (max 2M bytes)</label>
			</div>
		</div>
		<div class="kleistad-row" style="padding-top:15px">
			<div class="kleistad-col-5">
				<textarea name="stookschema" id="kleistad_stookschema" tabindex="7" maxlength="1000" rows="5"><?php echo esc_textarea( $this->data['recept']['content']['stookschema'] ); ?></textarea>
			</div>
			<div class="kleistad-col-5">
				<input type="file" name="foto" id="kleistad_foto_input" accept=".jpeg,.jpg,.tiff,.tif" /><br />
				<img id="kleistad_foto" src="<?php echo esc_url( $this->data['recept']['content']['foto'] ); ?>" alt=" " >
				<input type="hidden" name="foto_url" value="<?php echo esc_url( $this->data['recept']['content']['foto'] ); ?>" >
			</div>
		</div>
		<div class="kleistad-row">
			<div class="kleistad-col-5">
				<label>Basis recept</label>
			</div>
			<div class="kleistad-col-5">
				<label>Toevoegingen</label>
			</div>
		</div>
		<datalist id="kleistad_recept_grondstof">
			<select style="display: none;">
			<?php
			$grondstof_parent = get_term_by( 'name', '_grondstof', 'kleistad_recept_cat' );
			$recept_terms     = get_terms(
				[
					'taxonomy'   => 'kleistad_recept_cat',
					'hide_empty' => false,
					'orderby'    => 'name',
					'parent'     => $grondstof_parent->term_id,
				]
			);
			if ( is_array( $recept_terms ) ) :
				foreach ( $recept_terms as $recept_term ) :
					?>
					<option value="<?php echo esc_attr( $recept_term->name ); ?>" >&nbsp;
					<?php
				endforeach;
			endif
			?>
			</select>
		</datalist>
		<div class="kleistad-row">
			<div class="kleistad-col-5">
				<table>
			<?php
			$index = 0;
			$count = count( $this->data['recept']['content']['basis'] );
			do {
				$component = $index < $count ? $this->data['recept']['content']['basis'][ $index ]['component'] : '';
				$gewicht   = $index < $count ? $this->data['recept']['content']['basis'][ $index ]['gewicht'] * 1.0 : 0.0;
				?>
				<tr>
					<td><input type="text" name="basis_component[]" list="kleistad_recept_grondstof" autocomplete="off" value="<?php echo esc_attr( $component ); ?>" ></td>
					<td><input type="number" lang="nl" step="any" name="basis_gewicht[]" maxlength="6" style="width:50%;text-align:right;" value="<?php echo esc_attr( $gewicht ); ?>" >&nbsp;gr.</td>
				</tr>
				<?php
			} while ( $index++ < $count );
			?>
				<tr>
					<td colspan="2"><button class="extra_regel ui-button ui-widget ui-corner-all" ><span class="dashicons dashicons-plus"></span></button></td>
				</tr>
				</table>
			</div>
			<div class="kleistad-col-5">
				<table>
			<?php
			$index = 0;
			$count = count( $this->data['recept']['content']['toevoeging'] );
			do {
				$component = $index < $count ? $this->data['recept']['content']['toevoeging'][ $index ]['component'] : '';
				$gewicht   = $index < $count ? $this->data['recept']['content']['toevoeging'][ $index ]['gewicht'] * 1.0 : 0.0;
				?>
				<tr>
					<td><input type="text" name="toevoeging_component[]" list="kleistad_recept_grondstof" autocomplete="off" value="<?php echo esc_attr( $component ); ?>" ></td>
					<td><input type="number" lang="nl" step="0.01" name="toevoeging_gewicht[]" maxlength="6" style="width:50%;text-align:right;" value="<?php echo esc_attr( $gewicht ); ?>" >&nbsp;gr.</td>
				</tr>
				<?php
			} while ( $index++ < $count );
			?>
				<tr>
					<td colspan="2"><button class="extra_regel ui-button ui-widget ui-corner-all" ><span class="dashicons dashicons-plus"></span></button></td>
				</tr>
				</table>
			</div>
		</div>
		<p style="font-size:small;text-align:center;">Bij weergave van het recept op de website worden de basis ingrediënten genormeerd naar 100 gram</p>
		<button type="submit" name="kleistad_submit_recept_beheer" id="kleistad_submit_bewaren" value="bewaren">Opslaan</button>
		<?php
		if ( 0 < $this->data['recept']['id'] ) :
			if ( 'publish' !== $this->data['recept']['status'] ) :
				?>
		<button type="submit" name="kleistad_submit_recept_beheer" id="kleistad_submit_publiceren" value="publiceren" >Publiceren</button>
		<?php else : ?>
		<button type="submit" name="kleistad_submit_recept_beheer" id="kleistad_submit_verbergen" value="verbergen" >Verbergen</button>
			<?php
			endif;
		?>
		<button type="submit" name="kleistad_submit_recept_beheer" id="kleistad_submit_verwijderen" data-confirm="Recept beheer|weet je zeker dat je dit recept wilt verwijderen" value="verwijderen">Verwijderen</button>
		<?php endif ?>
		<button type="button" style="float:right" class="kleistad-terug-link" >Terug</button>
		<?php
		return $this;
	}
}
