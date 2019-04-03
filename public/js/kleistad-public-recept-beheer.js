/* global FileReader */

( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {

            $( '#kleistad_recept_toevoegen' ).click( function() {
                $( '#kleistad_recept_action' ).val( 'toevoegen' );
                $( '#kleistad_recept_id' ).val( 0 );
            });

            $( '#kleistad_foto_input' ).change( function() {
				var reader = new FileReader();

                if ( this.files && this.files[0] ) {
					if ( this.files[0].size > 2000000 ) {
						window.alert( 'deze foto is te groot (' + this.files[0].size + ' bytes)' );
                        $( this ).val( '' );
                        return false;
					}
					reader.onload = function( e ) {
						$( '#kleistad_foto' ).attr( 'src', e.target.result );
					};
					reader.readAsDataURL( this.files[0] );
				}
				return undefined;
            });

            $( '[name="wijzigen"]' ).click( function() {
                var id = $( this ).data( 'recept_id' );
                $( '#kleistad_recept_action' ).val( 'wijzigen' );
                $( '#kleistad_recept_id' ).val( id );
            });

            $( '#kleistad_verwijder_recept' ).dialog( {
                autoOpen: false,
                resizable: false,
                height: 'auto',
                width: 400,
                modal: true
            });

            $( '[name="verwijderen"]' ).click( function( event ) {
                var targetUrl = $( this ).attr( 'href' );
                var id = $( this ).data( 'recept_id' );

                event.preventDefault();
                $( '#kleistad_recept_action' ).val( 'verwijderen' );
                $( '#kleistad_recept_id' ).val( id );
                $( '#kleistad_verwijder_recept' ).dialog( {
                    buttons: {
                        Ok: function() {
                            window.location.href = targetUrl;
                        },
                        Annuleren: function() {
                            $( this ).dialog( 'close' );
                        }
                    }
                });
                $( '#kleistad_verwijder_recept' ).dialog( 'open' );
            });

            $( '.extra_regel' ).click( function() {
                var oldRow, newRow;
                oldRow = $( this ).closest( 'tr' ).prev();
                newRow = oldRow.clone().find( 'input' ).val( '' ).end();
                oldRow.after( newRow );
                return false;
            });
        }
    );

} )( jQuery );
