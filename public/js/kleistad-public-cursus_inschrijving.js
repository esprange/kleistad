( function ( $ ) {
    'use strict';

    $( document ).ready( function () {
        
        /**
         * Toon afhankelijk van de cursus de technieken.
         */
        $( 'input[name=cursus_id]:radio' ).change( function () {
            var technieken = $( this ).data( 'technieken' );
            $( '#kleistad_cursus_draaien' ).css( 'visibility', 'hidden' );
            $( '#kleistad_cursus_boetseren' ).css( 'visibility', 'hidden' );
            $( '#kleistad_cursus_handvormen' ).css( 'visibility', 'hidden' );
            $( '#kleistad_cursus_technieken' ).css( 'visibility', 'hidden' );
            $.each( technieken, function ( key, value ) {
                $( '#kleistad_cursus_' + value.toLowerCase() ).css( 'visibility', 'visible' ).find( 'input' ).prop( 'checked', false );
                $( '#kleistad_cursus_technieken' ).css( 'visibility', 'visible' );
            } );
        } );
    } );


} )( jQuery );
