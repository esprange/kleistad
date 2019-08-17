/* global timetostr, strtotime */

( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {
			/**
			 * Initieer de datepicker.
			 */
			$( '#kleistad_datum' ).datepicker();

			$( '#kleistad_workshop_beheer_form input[type=checkbox]' ).click( function() {
				return ! $( this ).attr( 'readonly' );
			});

			$( '#kleistad_start_tijd' ).change(
				function() {
					var startTijd = strtotime( $( this ).val() );
					var eindTijd  = strtotime( $( '#kleistad_eind_tijd' ).val() );
					if ( startTijd + 60 > eindTijd ) {
						$( '#kleistad_eind_tijd' ).val( timetostr( Math.min( startTijd + 60, 24 * 60 ) ) );
					}
				}
			);

			$( '#kleistad_eind_tijd' ).change(
				function() {
					var startTijd = strtotime( $( '#kleistad_start_tijd' ).val() );
					var eindTijd  = strtotime( $( this ).val() );
					if ( startTijd > eindTijd - 60 ) {
						$( '#kleistad_start_tijd' ).val( timetostr( Math.max( eindTijd - 60, 0 ) ) );
					}
				}
			);

			$( '#kleistad_kosten' ).on( 'change paste keyup',
				function() {
					$( '#kleistad_kosten_ex_btw' ).val( ( $( this ).val() / 1.21 ).toFixed( 2 ) );
				}
			);

			$( '#kleistad_kosten_ex_btw' ).on( 'change paste keyup',
				function() {
					$( '#kleistad_kosten' ).val( ( $( this ).val() * 1.21 ).toFixed( 2 ) );
				}
			);

			$( '#kleistad_workshop_toevoegen' ).click(
				function() {
					window.location.href = $( this ).val();
				}
			);

			$( 'a.kleistad_workshop_unfold' ).click(
				function() {
					$( this ).closest( 'div' ).toggleClass( 'kleistad_workshop_compact' );
					$( this ).hide().closest( 'div' ).find( 'a.kleistad_workshop_fold' ).show();
					return false;
				}
			);

			$( 'a.kleistad_workshop_fold' ).click(
				function() {
					$( this ).closest( 'div' ).toggleClass( 'kleistad_workshop_compact' );
					$( this ).hide().closest( 'div' ).find( 'a.kleistad_workshop_unfold' ).show();
					return false;
				}
			);

        }
    );

} )( jQuery );
