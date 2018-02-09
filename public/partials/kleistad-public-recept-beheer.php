<?php
/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       www.sprako.nl/wordpress/eric
 * @since      4.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 */

if ( ! is_user_logged_in() ) :
	?>
	<p>Geen toegang tot dit formulier</p>
	<?php
else :
	if ( isset( $data['id'] ) ) :
	?>
	<form method="post" action="<?php echo esc_url( get_permalink() ); ?>" enctype="multipart/form-data">
		<input type="hidden" name="action" value="" />
		<input type="hidden" name="id" value="<?php echo esc_attr( $data['recept']['id'] ); ?>" />
		<?php wp_nonce_field( 'kleistad_recept_beheer' ); ?>
		<div class="kleistad_row">
			<div class="kleistad_label kleistad_col_3">
				<label for="kleistad_titel">Recept naam</label>
			</div>
			<div class="kleistad_col_7">
				<input class="kleistad_input" type="text" size="20" name="titel" id="kleistad_titel"
					   value="<?php echo esc_attr( $data['recept']['titel'] ); ?>"/>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_label kleistad_col_3">
				<label for="kleistad_glazuur">Soort glazuur</label>
			</div>
			<div class="kleistad_label kleistad_col_3">
				<label for="kleistad_kleur">Kleur</label>
			</div>
			<div class="kleistad_label kleistad_col_3">
				<label for="kleistad_uiterlijk">Uiterlijk</label>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_3">
			<?php
			$glazuur = get_term_by( 'name', '_glazuur', 'kleistad_recept_cat' );
			wp_dropdown_categories(
				[
					'orderby' => 'name',
					'hide_empty' => 0,
					'show_count' => 0,
					'show_option_none' => 'Kies soort glazuur',
					'class' => 'cat',
					'taxonomy' => 'kleistad_recept_cat',
					'hierarchical' => 1,
					'id' => 'kleistad_glazuur',
					'name' => 'glazuur',
					'selected' => $data['recept']['glazuur'],
					'child_of' => $glazuur->term_id,
				]
			);
			?>
			</div>
			<div class="kleistad_col_3">
			<?php
			$kleur = get_term_by( 'name', '_kleur', 'kleistad_recept_cat' );
			wp_dropdown_categories(
				[
					'orderby' => 'name',
					'hide_empty' => 0,
					'show_count' => 0,
					'show_option_none' => 'Kies kleur',
					'class' => 'cat',
					'taxonomy' => 'kleistad_recept_cat',
					'hierarchical' => 1,
					'id' => 'kleistad_kleur',
					'name' => 'kleur',
					'selected' => $data['recept']['kleur'],
					'child_of' => $kleur->term_id,
				]
			);
			?>
			</div>
			<div class="kleistad_col_3">
			<?php
			$uiterlijk = get_term_by( 'name', '_uiterlijk', 'kleistad_recept_cat' );
			wp_dropdown_categories(
				[
					'orderby' => 'name',
					'hide_empty' => 0,
					'show_count' => 0,
					'show_option_none' => 'Kies uiterlijk',
					'class' => 'cat',
					'taxonomy' => 'kleistad_recept_cat',
					'hierarchical' => 1,
					'id' => 'kleistad_uiterlijk',
					'name' => 'uiterlijk',
					'selected' => $data['recept']['uiterlijk'],
					'child_of' => $uiterlijk->term_id,
				]
			);
			?>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_label kleistad_col_5">
				<label for="kleistad_kenmerk">Kenmerken</label>
			</div>
			<div class="kleistad_label kleistad_col_5">
				<label for="kleistad_herkomst">Herkomst</label>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_5">
				<?php
				wp_editor(
					$data['recept']['meta']['kenmerk'], 'kleistad_kenmerk', [
						'textarea_name' => 'kenmerk',
						'textarea_rows' => 2,
						'media_buttons' => false,
						'teeny' => true,
						'quicktags' => false,
					]
				);
				?>
			</div>
			<div class="kleistad_col_5">
				<?php
				wp_editor(
					$data['recept']['meta']['herkomst'], 'kleistad_herkomst', [
						'textarea_name' => 'herkomst',
						'textarea_rows' => 2,
						'media_buttons' => false,
						'teeny' => true,
						'quicktags' => false,
					]
				);
				?>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_label kleistad_col_5">
				<label for="kleistad_stookschema">Stookschema</label>
			</div>
			<div class="kleistad_label kleistad_col_5">
				<label for="kleistad_foto">Foto (max <?php echo esc_html( ini_get( 'upload_max_filesize' ) ); ?> bytes)</label>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_5">
				<?php
				wp_editor(
					$data['recept']['meta']['stookschema'], 'kleistad_stookschema', [
						'textarea_name' => 'stookschema',
						'textarea_rows' => 2,
						'media_buttons' => false,
						'teeny' => true,
						'quicktags' => false,
					]
				);
				?>
			</div>
			<div class="kleistad_col_5">
				<input type="file" name="foto" id="kleistad_foto"  multiple="false" accept="image/*" /><br />
				<img src="<?php echo esc_url( $data['recept']['meta']['foto'] ); ?>" >
				<input type="hidden" name="foto_url" value="<?php echo esc_url( $data['recept']['meta']['foto'] ); ?>" >
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_5">
				<label>Basis recept</label>
			</div>
			<div class="kleistad_col_5">
				<label>Toevoegingen</label>
			</div>
		</div>
		<datalist id="kleistad_recept_grondstof">
			<?php
			$grondstof_parent = get_term_by( 'name', '_grondstof', 'kleistad_recept_cat' );
			$terms = get_terms(
				array(
					'taxonomy' => 'kleistad_recept_cat',
					'hide_empty' => false,
					'orderby'    => 'name',
				)
			);
			foreach ( $terms as $term ) :
				if ( intval( $term->parent ) === intval( $grondstof_parent->term_id ) ) :
			?>
				<option value="<?php echo esc_attr( $term->name ); ?>">
			<?php
				endif;
			endforeach
			?>
	
					</datalist>
		<div class="kleistad_row">
			<div class="kleistad_col_5">
				<table>
			<?php
			$basis_regel = false;
			foreach ( $data['recept']['meta']['basis'] as $basis ) :
				$basis_regel = true;
			?>
				<tr>
					<td><input type="text" name="basis_component[]" list="kleistad_recept_grondstof" value="<?php echo esc_attr( $basis['component'] ); ?>" ></td>
					<td><input type="text" name="basis_gewicht[]" value="<?php echo esc_attr( $basis['gewicht'] ); ?>"  min="0" max="999" maxlength="3" style="width:50%">&nbsp;gram</td>
				</tr>
			<?php
			endforeach;
			if ( ! $basis_regel ) :
			?>
				<tr>
					<td>
					<div class="select-editable">
						<select onchange="this.nextElementSibling.value=this.value">
							<option value=""></option>
			<?php
			foreach ( $terms as $term ) :
				if ( intval( $term->parent ) === intval( $grondstof_parent->term_id ) ) :
			?>
							<option value="<?php echo esc_attr( $term->name ); ?>">
								<?php echo esc_html( $term->name ); ?>
							</option>
			<?php
				endif;
			endforeach
			?>
						</select>
						<input type="text" name="basis_component[]" list="kleistad_recept_grondstof" >
					</div>
					</td>
					<td><input type="number" name="basis_gewicht[]"  min="0" max="999" maxlength="3" style="width:50%">&nbsp;gram</td>
				</tr>
			<?php
			endif;
			?>
				<tr>
					<td col="2"><button class="extra_regel ui-button ui-widget ui-corner-all" ><span class="dashicons dashicons-plus"></span></button></td>
				</tr>
				</table>
			</div>
			<div class="kleistad_col_5">
				<table>
			<?php
			$toevoeging_regel = false;
			foreach ( $data['recept']['meta']['toevoeging'] as $toevoeging ) :
				$toevoeging_regel = true;
			?>
				<tr>
					<td><input type="text" name="toevoeging_component[]" list="kleistad_recept_grondstof" value="<?php echo esc_attr( $toevoeging['component'] ); ?>" ></td>
					<td><input type="number" name="toevoeging_gewicht[]" value="<?php echo esc_attr( $toevoeging['gewicht'] ); ?>"  min="0" max="999" maxlength="3" style="width:50%">&nbsp;gram</td>
				</tr>
			<?php
			endforeach;
			if ( ! $toevoeging_regel ) :
			?>
				<tr>
					<td><input type="text" name="toevoeging_component[]" list="kleistad_recept_grondstof" ></td>
					<td><input type="number" name="toevoeging_gewicht[]" min="0" max="999" maxlength="3" style="width:50%">&nbsp;gram</td>
				</tr>
			<?php
			endif;
			?>
				<tr>
					<td col="2"><button class="extra_regel ui-button ui-widget ui-corner-all" ><span class="dashicons dashicons-plus"></span></button></td>
				</tr>
				</table>
			</div>
		</div>
		<button id="kleistad_recept_opslaan" name="kleistad_submit_recept_beheer">Opslaan</button>
	</form>
	<?php
	else :
	?>
	<div id="kleistad_verwijder_recept" title="Recept verwijderen ?">
	  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Dit recept wordt verwijderd</p>
	</div>
	<form method="POST" action="<?php echo esc_url( get_permalink() ); ?>">

		<input id="kleistad_recept_action" type="hidden" name="action" value="recept_overzicht" />
		<input id="kleistad_recept_id" type="hidden" name="recept_id" value="0" />
		<?php wp_nonce_field( 'kleistad_recept_beheer' ); ?>
		<table class="kleistad_rapport">
			<thead>
			<tr>
				<th>Glazuur</th>
				<th>Titel</th>
				<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $data['recept'] as $recept ) : ?>
			<tr>
				<td><img src="<?php echo esc_url( $recept['foto'] ); ?>" height="100" width="100" >
				<td><?php echo esc_html( $recept['titel'] ); ?><b>
					<?php
						echo ( 'private' === $recept['post_status'] ? ' - prive' : // WPCS: XSS ok.
							( 'pending' === $recept['post_status'] ? ' - wacht op publicatie' :
							( 'draft' === $recept['post_status'] ? ' - concept' : '' ) ) );
					?>
					</b>
					</td>
				<td>
					<a href="<?php echo esc_url( wp_nonce_url( get_permalink(), 'kleistad_wijzig_recept_' . $recept['id'] ) . '&action=wijzigen&id=' . $recept['id'] ); ?>"
					   class="ui-button ui-widget ui-corner-all" style="color:green;" name="wijzigen" data-recept_id="<?php echo esc_html( $recept['id'] ); ?>">
						<span class="dashicons dashicons-edit"></span>
					</a>
					<a href="<?php echo esc_url( wp_nonce_url( get_permalink(), 'kleistad_publiceer_recept_' . $recept['id'] ) . '&action=publiceren&id=' . $recept['id'] ); ?>"
					   class="ui-button ui-widget ui-corner-all" style="color:black;" name="publiceren" data-recept_id="<?php echo esc_html( $recept['id'] ); ?>">
						<span class="dashicons dashicons-<?php echo ( 'private' === $recept['post_status'] ) ? 'visibility' : 'hidden'; ?>"></span>
					</a>
					<a href="<?php echo esc_url( wp_nonce_url( get_permalink(), 'kleistad_verwijder_recept_' . $recept['id'] ) . '&action=verwijderen&id=' . $recept['id'] ); ?>"
					   class="ui-button ui-widget ui-corner-all" style="color:red;" name="verwijderen" data-recept_id="<?php echo esc_html( $recept['id'] ); ?>">
						<span class="dashicons dashicons-trash"></span>
					</a>
				</td>
			</tr>
			<?php endforeach ?>
			</tbody>
		</table>
		<button id="kleistad_recept_toevoegen">Toevoegen</button>
	</form>
	<?php
	endif;
endif
?>
