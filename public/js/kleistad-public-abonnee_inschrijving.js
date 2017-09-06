( function ( $ ) {
    'use strict';

    $( "#kleistad_start_datum" ).datepicker( {
        dateFormat: "dd-mm-yy"
    } );

    $( document ).ready( function () {
        $( 'input[name=abonnement_keuze]' ).change( function () {
            if ( this.value === 'beperkt' ) {
                $( '#kleistad_dag' ).css( 'visibility', 'visible' );
            } else {
                $( '#kleistad_dag' ).css( 'visibility', 'hidden' );
            }
        } );

    } );

} )( jQuery );
