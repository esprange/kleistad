<?php
/**
 * Toon het recept beheer formulier
 *
 * @link       https://www.kleistad.nl
 * @since      4.1.0
 *
 * @package    Kleistad
 * @subpackage Kleistad/public/partials
 * @phan-file-suppress   PhanUndeclaredVariable, PhanTypeSuspiciousEcho
 */

if ( ! is_user_logged_in() ) :
	?>
	<p>Geen toegang tot dit formulier</p>
	<?php
else :
	if ( isset( $data['id'] ) ) :
		add_filter( 'wp_dropdown_cats', 'wp_dropdown_categories_required' );
		/**
		 * Voegt 'required' toe aan dropdown list.
		 *
		 * @param string $output Door wp_dropdown_categories aangemaakte select list.
		 * @return string
		 */
		function wp_dropdown_categories_required( $output ) {
			return preg_replace( '^' . preg_quote( '<select ' ) . '^', '<select required ', $output ); // phpcs:ignore
		}
		?>
	<form method="POST" enctype="multipart/form-data" autocomplete="off">
		<input type="hidden" name="action" value="" />
		<input type="hidden" name="id" value="<?php echo esc_attr( $data['recept']['id'] ); ?>" />
		<?php wp_nonce_field( 'kleistad_recept_beheer' ); ?>
		<div class="kleistad_row">
			<div class="kleistad_col_3 kleistad_label">
				<label for="kleistad_titel">Recept naam</label>
			</div>
			<div class="kleistad_col_7">
				<input class="kleistad_input" type="text" size="20" name="titel" tabindex="1" required id="kleistad_titel"
					value="<?php echo esc_attr( $data['recept']['titel'] ); ?>"/>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_3 kleistad_label">
				<label for="kleistad_glazuur">Soort glazuur</label>
			</div>
			<div class="kleistad_col_3 kleistad_label">
				<label for="kleistad_kleur">Kleur</label>
			</div>
			<div class="kleistad_col_3 kleistad_label">
				<label for="kleistad_uiterlijk">Uiterlijk</label>
			</div>
		</div>
		<div class="kleistad_row" style="padding-top:15px">
			<div class="kleistad_col_3">
			<?php
			$glazuur = get_term_by( 'name', '_glazuur', 'kleistad_recept_cat' );
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
					'selected'          => $data['recept']['glazuur'],
					'child_of'          => $glazuur->term_id,
					'tabindex'          => 2,
				]
			);
			?>
			</div>
			<div class="kleistad_col_3">
			<?php
			$kleur        = get_term_by( 'name', '_kleur', 'kleistad_recept_cat' );
			$cat_dropdown = wp_dropdown_categories(
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
					'selected'          => $data['recept']['kleur'],
					'child_of'          => $kleur->term_id,
					'tabindex'          => 3,
				]
			);
			?>
			</div>
			<div class="kleistad_col_3">
			<?php
			$uiterlijk = get_term_by( 'name', '_uiterlijk', 'kleistad_recept_cat' );
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
					'selected'          => $data['recept']['uiterlijk'],
					'child_of'          => $uiterlijk->term_id,
					'tabindex'          => 4,
				]
			);
			?>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_5 kleistad_label">
				<label for="kleistad_kenmerk">Kenmerken</label>
			</div>
			<div class="kleistad_col_5 kleistad_label">
				<label for="kleistad_herkomst">Herkomst</label>
			</div>
		</div>
		<div class="kleistad_row" style="padding-top:15px">
			<div class="kleistad_col_5">
				<textarea name="kenmerk" id="kleistad_kenmerk" tabindex="5" rows="5"><?php echo esc_textarea( $data['recept']['content']['kenmerk'] ); ?></textarea>
			</div>
			<div class="kleistad_col_5">
				<textarea name="herkomst" id="kleistad_herkomst" tabindex="6" rows="5"><?php echo esc_textarea( $data['recept']['content']['herkomst'] ); ?></textarea>
			</div>
		</div>
		<div class="kleistad_row">
			<div class="kleistad_col_5 kleistad_label">
				<label for="kleistad_stookschema">Stookschema</label>
			</div>
			<div class="kleistad_col_5 kleistad_label">
				<label for="kleistad_foto_input">Foto (max 2M bytes)</label>
			</div>
		</div>
		<div class="kleistad_row" style="padding-top:15px">
			<div class="kleistad_col_5">
				<textarea name="stookschema" id="kleistad_stookschema" tabindex="7" rows="5"><?php echo esc_textarea( $data['recept']['content']['stookschema'] ); ?></textarea>
			</div>
			<div class="kleistad_col_5">
				<input type="file" name="foto" id="kleistad_foto_input"  accept=".jpg" /><br />
				<?php
				if ( '' !== $data['recept']['content']['foto'] ) :
					?>
				<img id="kleistad_foto" src="<?php echo esc_url( $data['recept']['content']['foto'] ); ?>" alt="foto" >
				<?php else : ?>
				&nbsp;
				<?php endif ?>
				<input type="hidden" name="foto_url" value="<?php echo esc_url( $data['recept']['content']['foto'] ); ?>" >
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
			<select style="display: none;">
			<?php
			$grondstof_parent = get_term_by( 'name', '_grondstof', 'kleistad_recept_cat' );
			$recept_terms     = get_terms( // @phan-suppress-current-line PhanAccessMethodInternal
				[
					'taxonomy'   => 'kleistad_recept_cat',
					'hide_empty' => false,
					'orderby'    => 'name',
					'parent'     => $grondstof_parent->term_id,
				]
			);
			foreach ( $recept_terms as $recept_term ) :
				?>
				<option value="<?php echo esc_attr( $recept_term->name ); ?>" >&nbsp;
				<?php
			endforeach
			?>
			</select>
		</datalist>
		<div class="kleistad_row">
			<div class="kleistad_col_5">
				<table>
			<?php
			$index = 0;
			$count = count( $data['recept']['content']['basis'] );
			do {
				$component = $index < $count ? $data['recept']['content']['basis'][ $index ]['component'] : '';
				$gewicht   = $index < $count ? $data['recept']['content']['basis'][ $index ]['gewicht'] * 1.0 : 0.0;
				?>
				<tr>
					<td><input type="text" name="basis_component[]" list="kleistad_recept_grondstof" autocomplete="off" value="<?php echo esc_attr( $component ); ?>" ></td>
					<td><input type="text" class="kleistad_gewicht" name="basis_gewicht[]" maxlength="6" style="width:50%;text-align:right;" value="<?php echo esc_attr( number_format_i18n( $gewicht, 2 ) ); ?>" >&nbsp;gr.</td>
				</tr>
				<?php
			} while ( $index++ < $count );
			?>
				<tr>
					<td colspan="2"><button class="extra_regel ui-button ui-widget ui-corner-all" ><span class="dashicons dashicons-plus"></span></button></td>
				</tr>
				</table>
			</div>
			<div class="kleistad_col_5">
				<table>
			<?php
			$index = 0;
			$count = count( $data['recept']['content']['toevoeging'] );
			do {
				$component = $index < $count ? $data['recept']['content']['toevoeging'][ $index ]['component'] : '';
				$gewicht   = $index < $count ? $data['recept']['content']['toevoeging'][ $index ]['gewicht'] * 1.0 : 0.0;
				?>
				<tr>
					<td><input type="text" name="toevoeging_component[]" list="kleistad_recept_grondstof" autocomplete="off" value="<?php echo esc_attr( $component ); ?>" ></td>
					<td><input type="text" class="kleistad_gewicht" name="toevoeging_gewicht[]" maxlength="6" style="width:50%;text-align:right;" value="<?php echo esc_attr( number_format_i18n( $gewicht, 2 ) ); ?>" >&nbsp;gr.</td>
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
		<button id="kleistad_recept_opslaan" name="kleistad_submit_recept_beheer">Opslaan</button>
		<button onClick="window.history.back();">Annuleren</button>
	</form>
		<?php
		remove_filter( 'wp_dropdown_cats', 'wp_dropdown_categories_required' );
	else :
		?>
	<div id="kleistad_verwijder_recept" title="Recept verwijderen ?">
		<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Dit recept wordt verwijderd</p>
	</div>
	<form method="POST" >

		<input id="kleistad_recept_action" type="hidden" name="action" value="recept_overzicht" />
		<input id="kleistad_recept_id" type="hidden" name="recept_id" value="0" />
		<?php wp_nonce_field( 'kleistad_recept_beheer' ); ?>
		<table class="kleistad_datatable display" data-page-length="5" data-order='[[ 2, "desc" ]]'>
			<thead>
			<tr>
				<th data-orderable="false">Glazuur</th>
				<th>Titel</th>
				<th>Datum</th>
				<th>Status</th>
				<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $data['recept'] as $recept ) : ?>
			<tr>
				<td>
				<?php
				if ( '' !== $recept['foto'] ) :
					?>
					<img src="<?php echo esc_url( $recept['foto'] ); ?>" height="100" width="100" alt="<?php echo esc_attr( $recept['titel'] ); ?>" >
					<?php else : ?>
					&nbsp;
				<?php endif; ?>
				<td><?php echo esc_html( $recept['titel'] ); ?></td>
				<td data-sort="<?php echo esc_attr( $recept['modified'] );?>"><?php echo esc_html( date_i18n( 'd-m-Y H:i', $recept['modified'] ) ); ?></td>
				<td><?php echo esc_html( $recept['post_status'] ); ?></td>
				<td>
					<a href="<?php echo esc_url( wp_nonce_url( '', 'kleistad_wijzig_recept_' . $recept['id'] ) . '&action=wijzigen&id=' . $recept['id'] ); ?>"
						title="wijzig recept" class="ui-button ui-widget ui-corner-all" style="color:green;padding:.4em .8em;" data-recept_id="<?php echo esc_html( $recept['id'] ); ?>">
						<span class="dashicons dashicons-edit"></span>
					</a>
					<a href="<?php echo esc_url( wp_nonce_url( '', 'kleistad_publiceer_recept_' . $recept['id'] ) . '&action=publiceren&id=' . $recept['id'] ); ?>"
						title="<?php echo ( 'draft' === $recept['post_status'] ) ? 'publiceer recept' : 'concept'; ?>" class="ui-button ui-widget ui-corner-all" style="color:black;padding:.4em .8em;" data-recept_id="<?php echo esc_html( $recept['id'] ); ?>">
						<span class="dashicons dashicons-<?php echo ( 'draft' === $recept['post_status'] ) ? 'external' : 'hammer'; ?>"></span>
					</a>
					<a href="<?php echo esc_url( wp_nonce_url( '', 'kleistad_verwijder_recept_' . $recept['id'] ) . '&action=verwijderen&id=' . $recept['id'] ); ?>"
						title="verwijder recept" class="ui-button ui-widget ui-corner-all" style="color:red;padding:.4em .8em;" data-recept_id="<?php echo esc_html( $recept['id'] ); ?>">
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
