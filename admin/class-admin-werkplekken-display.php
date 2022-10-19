<?php
/**
 * De class voor de rendering van werkplekken functies van de plugin.
 *
 * @link https://www.kleistad.nl
 *
 * @package Kleistad
 * @subpackage Kleistad/admin
 */

namespace Kleistad;

/**
 * Admin display class
 */
class Admin_Werkplekken_Display extends Admin_Display {

	/**
	 * Toon de metabox
	 *
	 * @param array $item Het weer te geven object in de meta box.
	 * @param array $metabox De metabox argumenten.
	 * @return void
	 *
	 * @suppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function form_meta_box( array $item, array $metabox ) : void {
		?>
		<table style="width: 100%;border-spacing: 2px; padding: 5px" >
			<tbody>
				<tr>
					<th  scope="row">
						<label for="kleistad_start_config">Start datum</label>
					</th>
					<td colspan="2">
						<input type="text" id="kleistad_start_config" name="start_datum" class="kleistad-datum" required value="<?php echo esc_attr( $item['start_datum'] ); ?>" autocomplete="off" >
					</td>
				</tr>
				<tr>
					<th  scope="row">
						<label for="kleistad_eind_config">Eind datum</label>
					</th>
					<td colspan="2">
						<input type="hidden" name="config_eind" value="<?php echo esc_attr( intval( $item['config_eind'] ) ); ?>" >
						<input type="text" id="kleistad_eind_config" name="eind_datum" class="kleistad-datum" value="<?php echo esc_attr( $item['eind_datum'] ); ?>" autocomplete="off" >
					</td>
				</tr>
				<tr><td></td>
				<?php foreach ( array_keys( $item['config'] ) as $atelierdag ) : ?>
					<th scope="col"><label><?php echo esc_html( $atelierdag ); ?></label></th>
				<?php endforeach ?>
				</tr>
				<?php foreach ( Werkplek::WERKPLEK_DAGDEEL as $dagdeel ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $dagdeel ); ?></th>
				</tr>
				<tr>
					<td>Meester</td>
					<?php foreach ( array_keys( $item['config'] ) as $atelierdag ) : ?>
					<td><?php $this->meester_selectie( "meesters[$atelierdag][$dagdeel]", $item['meesters'][ $atelierdag ][ $dagdeel ] ?? 0 );  //phpcs:ignore ?></td>
					<?php endforeach ?>
				</tr>
					<?php foreach ( opties()['werkruimte'] as $activiteit ) : ?>
				<tr>
					<td><?php echo esc_html( $activiteit['naam'] ); ?></td>
						<?php foreach ( array_keys( $item['config'] ) as $atelierdag ) : ?>
						<td><!--suppress HtmlFormInputWithoutLabel -->
							<input type="text" size="4"
							value="<?php echo esc_attr( $item['config'][ $atelierdag ][ $dagdeel ][ $activiteit['naam'] ] ); ?>"
							name="<?php echo esc_attr( "config[$atelierdag][$dagdeel][{$activiteit['naam']}]" ); ?>" ></td>
					<?php endforeach ?>
				</tr>
				<?php endforeach ?>
			<?php endforeach ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Toon de pagina
	 *
	 * @return void
	 */
	public function page() : void {
		$table = new Admin_Werkplekken();
		?>
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<h2>Werkplek configuraties<a class="add-new-h2"
						href="<?php echo esc_url( get_admin_url( get_current_blog_id(), 'admin.php?page=werkplekken_form' ) ); ?>">Toevoegen</a>
			</h2>
			<form id="werkplekconfigs-table" method="GET">
				<input type="hidden" name="page" value="<?php echo filter_input( INPUT_GET, 'page' ); ?>"/>
				<?php
					$table->prepare_items();
					$table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Maak een lijst van mogelijke werkplaats meesters. Dit zijn bestuursleden, docenten of abonnees.
	 *
	 * @param string $name        Het name van de select box.
	 * @param int    $id_selected Het id als er een gebruiker geselecteerd is.
	 */
	private function meester_selectie( string $name, int $id_selected ) {
		static $meesters = null;
		if ( is_null( $meesters ) ) {
			$meesters = get_users(
				[
					'fields'   => [ 'display_name', 'ID' ],
					'orderby'  => 'display_name',
					'role__in' => [ LID, DOCENT, BESTUUR ],
				]
			);
		}
		?>
	<label for="<?php echo esc_attr( "meesters_$name" ); ?>" ></label>
	<select name="<?php echo esc_attr( $name ); ?>" style="width:100%;" id="<?php echo esc_attr( "meesters_$name" ); ?>" >
		<option value="0" ></option>
		<?php foreach ( $meesters as $meester ) : ?>
		<option value="<?php echo esc_attr( $meester->ID ); ?>" <?php selected( intval( $meester->ID ), $id_selected ); ?> >
			<?php echo esc_html( $meester->display_name ); ?>
		</option>
		<?php endforeach; ?>
	<\select>
		<?php
	}

}
