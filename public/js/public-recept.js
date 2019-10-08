/* global kleistadData */

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
        window.sessionStorage.setItem( 'recept_filter_status', status );
    }

    function zoekRecepten( refresh ) {
		var filter,
			storage_filter = window.sessionStorage.getItem( 'recept_filter' );
		if ( storage_filter && refresh ) {
			filter = JSON.parse( storage_filter );
			filter.terms.forEach( function( item ) {
				$( '#kleistad_filters input[name="term"][value="' + item + '"]' ).prop( 'checked' );
			});
            filter.auteurs.forEach( function( item ) {
                $( '#kleistad_filters input[name="auteur"][value="' + item + '"]' ).prop( 'checked' );
            });
            $( '#kleistad_zoek' ).val( filter.zoeker );
            $( '#kleistad_sorteer' ).val( filter.sorteer );
		} else {
			filter = {
				zoeker:  $( '#kleistad_zoek' ).val(),
				sorteer: $( '#kleistad_sorteer' ).val(),
				terms:   [],
				auteurs: []
			};
            $( '#kleistad_filters input[name="term"]:checked' ).each( function() {
				filter.terms.push( $( this ).val() );
            });
            $( '#kleistad_filters input[name="auteur"]:checked' ).each( function() {
				filter.auteurs.push( $( this ).val() );
			});
			window.sessionStorage.setItem( 'recept_filter', JSON.stringify( filter ) );
		}
        $.ajax(
            {
                beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
                },
                data: filter,
                method: 'GET',
                url: kleistadData.base_url + '/recept/'
            }
        ).done(
            function( data ) {
                $( '#kleistad_recepten' ).html( data.content );
                $( '#kleistad_filters input[name="term"]' ).each( function() {
                    if ( -1 !== $.inArray( $( this ).val(), data.terms ) ) {
                        $( this ).prop( 'checked', true );
                        $( this ).next().css( { visibility: 'visible' } );
                        $( this ).parent().css( { fontWeight: 'bold' } );
                    }
                });
                $( '#kleistad_filters input[name="auteur"]' ).each( function() {
                    if ( -1 !== $.inArray( $( this ).val(), data.auteurs ) ) {
                        $( this ).prop( 'checked', true );
                        $( this ).next().css( { visibility: 'visible' } );
                        $( this ).parent().css( { fontWeight: 'bold' } );
                    }
                });
                if ( ! window.sessionStorage.getItem( 'recept_filter_status' ) ) {
                    window.sessionStorage.setItem( 'recept_filter_status', 'hide' );
                }
                displayFilters( window.sessionStorage.getItem( 'recept_filter_status' ) );
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
