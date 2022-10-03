<?php
/**
 * De class voor de rendering van regelingen functies van de plugin.
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
class Admin_Regelingen_Display extends Admin_Display {

	/**
	 * Toon de metabox
	 *
	 * @param array $item Het weer te geven object in de meta box.
	 * @param array $metabox De metabox argumenten.
	 * @return void
	 *
	 * @suppressWarnings(PHPMD.ElseExpression)
	 * @suppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function form_meta_box( array $item, array $metabox ) : void {
		$gebruikers = get_users(
			[
				'fields'   => [ 'ID', 'display_name' ],
				'orderby'  => [ 'display_name' ],
				'role__in' => [ LID, BESTUUR, DOCENT ],
			]
		);
		$ovens      = new Ovens();
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<?php if ( 0 === $item['gebruiker_id'] ) : ?>
					<th scope="row"><label for="gebruiker_id">Naam gebruiker</label></th>
					<td>
						<select name="gebruiker_id" id="gebruiker_id" required>
							<?php foreach ( $gebruikers as $gebruiker ) : ?>
								<option value="<?php echo esc_attr( $gebruiker->ID ); ?>" <?php selected( $item['gebruiker_id'], $gebruiker->ID ); ?> ><?php echo esc_html( $gebruiker->display_name ); ?></option>
							<?php endforeach ?>
						</select>
					<?php else : ?>
					<th scope="row"><label>Naam gebruiker</label></th>
					<td>
						<input type ="hidden" id="gebruiker_id" name="gebruiker_id" value="<?php echo esc_attr( $item['gebruiker_id'] ); ?>" >
						<input type ="hidden" id="gebruiker_naam" name="gebruiker_naam" value="<?php echo esc_attr( $item['gebruiker_naam'] ); ?>" >
						<?php echo esc_html( $item['gebruiker_naam'] ); ?>
					<?php endif ?>
					</td>
				</tr>
				<tr>
				<?php if ( 0 === $item['oven_id'] ) : ?>
					<th scope="row"><label for="oven_id">Naam oven</label></th>
					<td>
						<select name="oven_id" id="oven_id" required>
						<?php foreach ( $ovens as $oven ) : ?>
							<option value="<?php echo esc_attr( $oven->id ); ?>" <?php selected( $item['oven_id'] == $oven->id ); // phpcs:ignore ?> ><?php echo esc_html( $oven->naam ); ?></option>
						<?php endforeach ?>
						</select>
				<?php else : ?>
					<th scope="row"><label>Naam oven</label></th>
					<td>
						<input type ="hidden" id="oven_id" name="oven_id" value="<?php echo esc_attr( $item['oven_id'] ); ?>" >
						<input type ="hidden" id="oven_naam" name="oven_naam" value="<?php echo esc_attr( $item['oven_naam'] ); ?>" >
						<?php echo esc_html( $item['oven_naam'] ); ?>
				<?php endif ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="kosten">Tarief</label></th>
					<td>
						<input id="kosten" name="kosten" type="number" size="5" value="<?php echo esc_attr( sprintf( '%.2f', $item['kosten'] ) ); ?>"
							step="0.01" placeholder="99.99" required>
					</td>
				</tr>
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
		$table = new Admin_Regelingen();
		?>
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
			<h2>Regelingen<a class="add-new-h2"
						href="<?php echo esc_url( get_admin_url( get_current_blog_id(), 'admin.php?page=regelingen_form' ) ); ?>">Toevoegen</a>
			</h2>
			<form id="regelingen-table" method="GET">
				<input type="hidden" name="page" value="<?php echo filter_input( INPUT_GET, 'page' ); ?>"/>
				<?php
					$table->prepare_items();
					$table->display();
				?>
			</form>
		</div>
		<?php
	}

}
