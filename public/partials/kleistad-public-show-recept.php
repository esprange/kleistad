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

/**
 * Truncate een tekstregel tot gewenste woordlengte
 *
 * @param string $string    Tekstregel.
 * @param int    $width     Gewenste lengte.
 * @return string
 */
function truncate_string( $string, $width ) {
	if ( strlen( $string ) > $width ) {
		$string = wordwrap( $string, $width );
		$string = substr( $string, 0, strpos( $string, "\n" ) );
	}
	return $string;
}

/**
 * Toont filter opties voor term met naam
 *
 * @param string $titel     De h3 titel.
 * @param array  $naam      Naam van de filtergroep.
 * @param array  $termen    Array van termen.
 * @return string           Html tekst.
 */
function filter( $titel, $naam, $termen ) {
	$html = '';
	$count = count( $termen );
	$toon = 4;
	if ( 0 < $count ) {
		$html .= "<h3>$titel</h3><ul>";
		$index = 0;
		foreach ( $termen as $term ) {
			$index++;
			$style = ( $toon < $index ) ? 'display:none;' : '';
			$html .= '<li class="kleistad_filter_term" style="' . $style . '">';
			$html .= '<label><input type="checkbox" class="kleistad_filter" value="' . $term->term_id . '" style="display:none;" >';
			$html .= esc_html( truncate_string( $term->name, 25 ) ); // Max. 30 karakters.
			$html .= '<span style="visibility:hidden;float:right">&#9932;</span></label></li>';
			if ( $toon === $index ) {
				$html .= '<li class="kleistad_filter_term">';
				$html .= '<label><input type="checkbox" name="' . $naam . '" class="kleistad_meer" value="meer" style="display:none;" >+ meer ... </label></li>';
			}
		}
		if ( $toon < $index ) {
			$html .= '<li class="kleistad_filter_term" style="display:none;" >';
			$html .= '<label><input type="checkbox" name="' . $naam . '" class="kleistad_meer" value="minder" style="display:none;" >- minder ... </label></li>';
		}
		$html .= '</ul>';
	}
	return $html;
}

?>
<div id="kleistad_filters" class="kleistad_filters" >
<?php
	echo filter( 'Type glazuur', 'glazuur', $data['glazuur'] ); // WPCS: XSS ok.
	echo filter( 'Uiterlijk', 'uiterlijk', $data['uiterlijk'] ); // WPCS: XSS ok.
	echo filter( 'Kleur', 'kleur', $data['kleur'] ); // WPCS: XSS ok.
?>
</div>

<div id="kleistad_recept_overzicht" class="kleistad_recept_overzicht">
<?php
if ( count( $data['recepten'] ) ) :
	foreach ( $data['recepten'] as $recept ) :
	?>
		<div style="width:250px;float:left;padding:15px;border:0px;">
			<a href="<?php echo esc_url( get_post_permalink( $recept['id'] ) ); ?>" >
			<div class="kleistad_recept_img" style="background-image:url('<?php echo esc_url( $recept['foto'] ); ?>');" ></div>
			<div class="kleistad_recept_titel" >
	<?php
			// De titel wordt afgekapt op de eerste 30 karakters...
			echo esc_html( truncate_string( $recept['titel'], 25 ) );
	?>
			</div>
			</a>
		</div>
	<?php
	endforeach;
else :
	?>
	<p>er zijn geen recepten gevonden...</p>
	<?php
endif;
?>
</div>