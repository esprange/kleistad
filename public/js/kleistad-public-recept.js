/* global kleistadData */

( function ( $ ){
    'use strict';

    function displayFilters( status ){
        if ( 'show' === status ) {
            $( '#kleistad_filters' ).css( { width: '30%', display: 'block' } );
            $( '#kleistad_recept_overzicht' ).css( { marginLeft: '30%' } );
            $( '#kleistad_filter_btn').html ( '- verberg filters' ).val( status );
        } else {            
            $( '#kleistad_filters' ).css( { display: 'none' } );
            $( '#kleistad_recept_overzicht' ).css( { marginLeft: '0%' } );
            $( '#kleistad_filter_btn').html ( '+ filter resultaten' ).val( status );
        }
        sessionStorage.receptFilter = status;
    };

    function zoekRecepten(){
        var terms = [];
        $( '#kleistad_filters input[type="checkbox"]:checked' ).each( function() {
            terms.push ( $( this ).val() );
        });
        $.ajax(
            {
                url: kleistadData.base_url + '/recept/',
                method: 'POST',
                beforeSend: function ( xhr ){
                    xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
                },
                data: {
                    zoek: { 
                        zoeker: $( '#kleistad_zoek' ).val(),
                        terms: terms
                    }
                }
            }
        ).done(
            function ( data ){
                $( '#kleistad_recepten' ).html( data.html );
                $( '#kleistad_filters input[type="checkbox"]' ).each( function() {
                    if ( -1 !== $.inArray( $( this ).val(), data.zoek.terms ) ) {
                        $( this ).prop( 'checked', true );
                        $( this ).next().css( { visibility: 'visible' } );
                        $( this ).parent().css( { fontWeight: 'bold' } );
                    }
                });
                if ( 'undefined' === typeof sessionStorage.receptFilter ) {
                    sessionStorage.receptFilter = 'hide';
                }
                displayFilters( sessionStorage.receptFilter );
            }
        ).fail(
            /* jshint unused:vars */
                function ( jqXHR, textStatus, errorThrown ){
                    if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
                        window.alert( jqXHR.responseJSON.message );
                        return;
                    }
                    window.alert( kleistadData.error_message );
                }
            );

        }
    ;

    $( document ).ready(
        function (){
            $( '#kleistad_recepten' ).ready( function() {
                zoekRecepten();
                return false;
            });

            $( '#kleistad_filter_btn' ).click ( function (){
                if ( 'show' === $( this ).val()) {
                    $( this ).val( 'hide' );
                } else {
                    $( this ).val( 'show' );
                }
                displayFilters( $( this ).val() );
            } );
            
            $( '#kleistad_zoek' ).on('keyup', function (e) {
                if (e.keyCode == 13) {
                    zoekRecepten();
                }
                return false;
            });

            $( 'body' ).on(
                'click', '.kleistad_filter', function (){
                    if ( $( this ).is( ':checked' ) ) {
                        $( this ).parent().css( { fontWeight: 'bold' } );
                    } else {
                        $( this ).parent().css( { fontWeight: 'normal' } );
                    }
                    zoekRecepten();
                    return false;
                } 
            );
        
            $( 'body' ).on(
                'click', '.kleistad_meer', function (){
                    var filter;
                    var name = $( this ).attr( 'name' );

                    if ( 'meer' === $( this ).val() ) {
                        filter = $( this ).parent().parent(); // Checkbox -> Label -> List element.
                    } else {
                        filter = $( 'input[name=' + name + '][value=meer]' ).parent().parent();
                    }
                    filter.toggle();
                    filter.nextAll().toggle(); 
                    return false;
                } 
            );

        }
    );

} )( jQuery );
