/* global kleistadData */

( function( $ ) {
    'use strict';

	var receptFilter;

	function leesFilters( initieel ) {
		if ( window.sessionStorage.getItem( 'recept_filter' ) && initieel ) {
			receptFilter = JSON.parse( window.sessionStorage.getItem( 'recept_filter' ) );
			receptFilter.terms.forEach( function( item ) {
				$( '#kleistad_filters input[name="term"][value="' + item + '"]' ).prop( 'checked' );
			});
			receptFilter.auteurs.forEach( function( item ) {
				$( '#kleistad_filters input[name="auteur"][value="' + item + '"]' ).prop( 'checked' );
			});
			$( '#kleistad_zoek' ).val( receptFilter.zoeker );
			$( '#kleistad_sorteer' ).val( receptFilter.sorteer );
		} else {
			receptFilter = {
				zoeker:  $( '#kleistad_zoek' ).val(),
				sorteer: $( '#kleistad_sorteer' ).val(),
				terms:   [],
				auteurs: []
			};
			$( '#kleistad_filters input[name="term"]:checked' ).each( function() {
				receptFilter.terms.push( $( this ).val() );
			});
			$( '#kleistad_filters input[name="auteur"]:checked' ).each( function() {
				receptFilter.auteurs.push( $( this ).val() );
			});
			window.sessionStorage.setItem( 'recept_filter', JSON.stringify( receptFilter ) );
		}
	}

	function displayFilters( status ) {
        if ( 'show' === status ) {
            $( '#kleistad_filters' ).css( { width: '30%', display: 'block' } );
            $( '#kleistad_recept_overzicht' ).css( { marginLeft: '30%' } );
            $( '#kleistad_filter_btn' ).html( '- verberg filters' );
        } else {
            $( '#kleistad_filters' ).css( { display: 'none' } );
            $( '#kleistad_recept_overzicht' ).css( { marginLeft: '0%' } );
            $( '#kleistad_filter_btn' ).html( '+ filter resultaten' );
        }
    }

    function zoekRecepten() {
        $.ajax(
            {
                beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
                },
                data: receptFilter,
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
			leesFilters( true );
			zoekRecepten();

			$( '#kleistad_filter_btn' ).on( 'click',
				function() {
					var tonen = 'hide' === window.sessionStorage.getItem( 'recept_filter_status' ) ? 'show' : 'hide';
					window.sessionStorage.setItem( 'recept_filter_status', tonen );
					displayFilters( tonen );
				}
			);

			$( '#kleistad_zoek' ).on( 'keyup',
				function( e ) {
					if ( 13 === e.keyCode ) {
						leesFilters();
						zoekRecepten();
					}
				}
			);

			$( '#kleistad_zoek_icon' ).on( 'click',
				function() {
					leesFilters();
					zoekRecepten();
				}
			);

			$( '#kleistad_sorteer' ).on( 'change',
				function() {
					leesFilters();
					zoekRecepten();
				}
			);

			$( '#kleistad_recepten' )
			.on( 'click', '.kleistad_filter',
				function() {
					leesFilters();
					zoekRecepten();
				}
			)
			.on( 'click', '.kleistad_meer',
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
