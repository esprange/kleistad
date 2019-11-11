( function( $ ) {
	'use strict';

    $( document ).ready(
        function() {

			$( '.kleistad_shortcode' )
			.on( 'click', '#kleistad_deb_bankbetaling',
                function() {
					$( '.kleistad_deb_veld' ).hide();
					$( '.kleistad_deb_bankbetaling' ).toggle( this.checked );
					$( '#kleistad_submit_debiteuren' ).prop( 'disabled', false ).data( 'confirm', 'Debiteuren|Klopt het bedrag van de bankbetaling ?' );
                }
			)
			.on( 'click', '#kleistad_deb_korting',
                function() {
					$( '.kleistad_deb_veld' ).hide();
					$( '.kleistad_deb_korting' ).toggle( this.checked );
					$( '#kleistad_submit_debiteuren' ).prop( 'disabled', false ).data( 'confirm', 'Debiteuren|Klopt het bedrag van de korting ?' );
                }
			)
			.on( 'click', '#kleistad_deb_annulering',
                function() {
					$( '.kleistad_deb_veld' ).hide();
					$( '.kleistad_deb_annulering' ).toggle( this.checked );
					$( '#kleistad_submit_debiteuren' ).prop( 'disabled', false ).data( 'confirm', 'Debiteuren|Klopt het bedrag van het restant te betalen ?' );
                }
			);
        }
    );

} )( jQuery );
