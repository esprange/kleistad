/* global kleistadData */
/* global: sessionStorage */

( function( $ ) {
    'use strict';

    function displayFilters( status ) {
        if ( 'show' === status ) {
            $( '#kleistad_filters' ).css( { width: '30%', display: 'block' } );
            $( '#kleistad_recept_overzicht' ).css( { marginLeft: '30%' } );
            $( '#kleistad_filter_btn' ).html( '- verberg filters' ).val( status );
        } else {
            $( '#kleistad_filters' ).css( { display: 'none' } );
            $( '#kleistad_recept_overzicht' ).css( { marginLeft: '0%' } );
            $( '#kleistad_filter_btn' ).html( '+ filter resultaten' ).val( status );
        }
        sessionStorage.receptFilter = status;
    }

    function zoekRecepten( refresh ) {
        var terms = [], auteurs = [];
        var value;

        if ( 'bewaard' === sessionStorage.bewaard && refresh ) {
            $( '#kleistad_filters input[name="term"], #kleistad_filters input[name="auteur"]' ).prop( 'checked', false );
            terms = sessionStorage.terms.split( ',' );
            terms.forEach( function( item ) {
                $( '#kleistad_filters input[name="term"][value="' + item + '"]' ).prop( 'checked' );
            });
            auteurs = sessionStorage.auteurs.split( ',' );
            auteurs.forEach( function( item ) {
                $( '#kleistad_filters input[name="auteur"][value="' + item + '"]' ).prop( 'checked' );
            });

            $( '#kleistad_zoek' ).val( sessionStorage.zoeker );
            $( '#kleistad_sorteer' ).val( sessionStorage.sorteer );
        } else {
            $( '#kleistad_filters input[name="term"]:checked' ).each( function() {
				value = $( this ).val();
                if ( ! isNaN( value ) ) {
                    terms.push( value );
                }
            });
            $( '#kleistad_filters input[name="auteur"]:checked' ).each( function() {
                value = $( this ).val();
                if ( ! isNaN( value ) ) {
                    auteurs.push( value );
                }
            });

            sessionStorage.terms   = terms.join( ',' );
            sessionStorage.auteurs = auteurs.join( ',' );
            sessionStorage.zoeker  = $( '#kleistad_zoek' ).val();
            sessionStorage.sorteer = $( '#kleistad_sorteer' ).val();
            sessionStorage.bewaard = 'bewaard';
        }
        $.ajax(
            {
                url: kleistadData.base_url + '/recept/',
                method: 'POST',
                beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
                },
                data: {
                    zoek: {
                        zoeker: $( '#kleistad_zoek' ).val(),
                        terms: terms,
                        auteurs: auteurs,
                        sorteer: $( '#kleistad_sorteer' ).val()
                    }
                }
            }
        ).done(
            function( data ) {
                $( '#kleistad_recepten' ).html( data.content );
                $( '#kleistad_filters input[name="term"]' ).each( function() {
                    if ( -1 !== $.inArray( $( this ).val(), data.zoek.terms ) ) {
                        $( this ).prop( 'checked', true );
                        $( this ).next().css( { visibility: 'visible' } );
                        $( this ).parent().css( { fontWeight: 'bold' } );
                    }
                });
                $( '#kleistad_filters input[name="auteur"]' ).each( function() {
                    if ( -1 !== $.inArray( $( this ).val(), data.zoek.auteurs ) ) {
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
            function( jqXHR ) {
                if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
                    window.alert( jqXHR.responseJSON.message );
                    return;
				}
                window.alert( kleistadData.error_message );
            }
        );
    }

    $( document ).ready(
        function() {
			zoekRecepten( true );

			$( '#kleistad_filter_btn' ).on( 'click',
				function() {
					if ( 'show' === $( this ).val() ) {
						$( this ).val( 'hide' );
					} else {
						$( this ).val( 'show' );
					}
					displayFilters( $( this ).val() );
				}
			);

			$( '#kleistad_zoek' ).on( 'keyup',
				function( e ) {
					if ( 13 === e.keyCode ) {
						zoekRecepten( false );
					}
				}
			);

			$( '#kleistad_zoek_icon' ).on( 'click',
				function() {
					zoekRecepten( false );
				}
			);

			$( '#kleistad_sorteer' ).on( 'change',
				function() {
					zoekRecepten( false );
				}
			);

			$( 'body' ).on( 'click', '.kleistad_filter',
				function() {
					zoekRecepten( false );
				}
			);

			$( 'body' ).on( 'click', '.kleistad_meer',
				function() {
					var filter;
					var name = $( this ).attr( 'name' );

					if ( 'meer' === $( this ).val() ) {
						filter = $( this ).parent().parent(); // Checkbox -> Label -> List element.
					} else {
						filter = $( 'input[name=' + name + '][value=meer]' ).parent().parent();
					}
					filter.toggle();
					filter.nextAll().toggle();
				}
			);
        }
    );

} )( jQuery );
