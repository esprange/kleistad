( function( $ ) {
	'use strict';

	function leesFilters() {
		if ( window.sessionStorage.getItem( 'debiteur_filter' ) ) {
			$( '#kleistad_zoek' ).val( window.sessionStorage.getItem( 'debiteur_filter' ) );
			$( '#kleistad_zoek_knop' ).data( 'id', $( '#kleistad_zoek' ).val() );
		}
	}

	function onLoad() {
		leesFilters();
	}

	$( document ).ajaxComplete(
		function() {
			onLoad();
		}
	);

    $( document ).ready(
        function() {

			onLoad();

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
			.on( 'click', '#kleistad_deb_afboeken',
                function() {
					$( '.kleistad_deb_veld' ).hide();
					$( '.kleistad_deb_afboeken' ).toggle( this.checked );
					$( '#kleistad_submit_debiteuren' ).prop( 'disabled', false ).data( 'confirm', 'Debiteuren|Verwacht je inderdaad dat er niet meer betaald wordt ?' );
                }
			)
			.on( 'click', '#kleistad_deb_annulering',
                function() {
					$( '.kleistad_deb_veld' ).hide();
					$( '.kleistad_deb_annulering' ).toggle( this.checked );
					$( '#kleistad_submit_debiteuren' ).prop( 'disabled', false ).data( 'confirm', 'Debiteuren|Klopt het bedrag van het restant te betalen ?' );
                }
			)
			.on( 'change', '#kleistad_zoek',
				function() {
					window.sessionStorage.setItem( 'debiteur_filter', $( this ).val() );
					$( '#kleistad_zoek_knop' ).data( 'id', $( this ).val() );
				}
			);
        }
    );

} )( jQuery );
