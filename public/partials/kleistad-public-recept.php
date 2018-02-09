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

if ( isset( $data['recept'] ) ) :
	?>
<button id="kleistad_recept_print" data-css="<?php echo esc_url( $data['css'] ); ?>">Afdrukken</button>
<div class="kleistad_recept" >
	<h2><?php echo esc_html( $data['recept']['titel'] ); ?></h2>
	<div style="width:100%"> 
		<div style="float:left;width:30%;">
			<img src="<?php echo esc_url( $data['recept']['meta']['foto'] ); ?>" width="100%" >
		</div>
		<div style="float:left;width:70%;">
			<table>
			<tr>
				<th>Type glazuur</th>
				<td><?php echo esc_html( $data['recept']['glazuur'] ); ?></td>
			</tr>
			<tr>
				<th>Uiterlijk</th>
				<td><?php echo esc_html( $data['recept']['uiterlijk'] ); ?></td>
			</tr>
			<tr>
				<th>Kleur</th>
				<td><?php echo esc_html( $data['recept']['kleur'] ); ?></td>
			</tr>
			<tr>
				<th>Stookschema</th>
				<td><?php echo $data['recept']['meta']['stookschema']; // WPCS: XSS ok. ?></td>
			</tr>
			</table>
		</div>
	</div>
	<div style="clear:both;">
		<table>
			<tr>
				<th>Auteur</th>
				<td><?php echo esc_html( $data['recept']['author'] ); ?></td>
				<th>Laatste wijziging</th>
				<td><?php echo esc_html( strftime( '%A %d-%m-%y', $data['recept']['modified'] ) ); ?></td>
			</tr>
			<tr>
				<th colspan="2">Basis recept</th>
				<th colspan="2">Toevoegingen</th>
			</tr>
			<tr>
				<td colspan="2">
					<table>
				<?php
				foreach ( $data['recept']['meta']['basis'] as $basis ) :
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
				foreach ( $data['recept']['meta']['toevoeging'] as $toevoeging ) :
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
		<?php echo $data['recept']['meta']['kenmerk']; // WPCS: XSS ok. ?>
	</div>
	<div>
		<h3>Oorsprong</h3>
		<?php echo $data['recept']['meta']['herkomst']; // WPCS: XSS ok. ?>
	</div>
</div>
	<?php
else :
?>
<div class="kleistad_row" >
	<div class="kleistad_col_3">
		<label for="kleistad_zoek" >Zoek een recept</label>
	</div>
	<div class="kleistad_col_7 kleistad_zoek" >
		<span class="dashicons dashicons-search"></span>		
		<input type="search" id="kleistad_zoek" placeholder="zoeken..." value="" >
	</div>
</div>
<div class="kleistad_row">
	<button type="button" id="kleistad_filter_btn" value="show" ></button>
</div>
<div class="kleistad_recepten" id="kleistad_recepten">
	de recepten worden opgehaald...
</div>	
<?php
endif;