/**
 * Generieke Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

/* global kleistadData, strtotime, timetostr */
/* exported detectTap */

let detectTap;

( function( $ ) {
	'use strict';

	/**
	 * Verwijder een class mbv een wildcard pattern.
	 */
	$.fn.removeClassWildcard = function( pattern ) {
		$( this ).removeClass(
			function( index, className ) {
				return ( className.match( new RegExp( '(^|\\s)' + pattern + '\\S+', 'g' ) ) || [] ).join( ' ' );
			}
		);
		return this;
	};

	/**
	 * Definieer de tabellen.
	 */
	function defineDatatables() {
		let $datatable = $( '.kleistad-datatable' );
		if ( ! $datatable[0] ) {
			return;
		}
		if ( ! $.fn.DataTable.isDataTable( '.kleistad-datatable' ) ) {
			// noinspection JSCheckFunctionSignatures .
			$datatable.on(
				'init.dt',
				function() {
					$datatable.show();
				}
			).dataTable(
				{
					language: {
						url: '//cdn.datatables.net/plug-ins/1.10.19/i18n/Dutch.json'
					},
					deferRender: true
				}
			);
		}
	}

	/**
	 * Definieer de datum velden.
	 */
	function defineDatums() {
		let $datum = $( '.kleistad-datum' );
		if ( $datum[0] && ! $datum.is( ':data("ui-datepicker")' ) ) {
			$datum.datepicker(
				{
					closeText: 'Sluiten',
					prevText: '←',
					nextText: '→',
					currentText: 'Vandaag',
					monthNames: [ 'januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december' ],
					monthNamesShort: [ 'jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec' ],
					dayNames: [ 'zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag' ],
					dayNamesShort: [ 'zon', 'maa', 'din', 'woe', 'don', 'vri', 'zat' ],
					dayNamesMin: [ 'zo', 'ma', 'di', 'wo', 'do', 'vr', 'za' ],
					weekHeader: 'Wk',
					dateFormat: 'dd-mm-yy',
					firstDay: 1,
					isRTL: false,
					showMonthAfterYear: false,
					yearSuffix: ''
				}
			);
		}
	}

	/**
	 * Definieer de timespinners.
	 */
	function defineTimespinners() {
		let $tijd = $( '.kleistad-tijd' );
		if ( $tijd[0] ) {
			$.widget(
				'ui.timespinner',
				$.ui.spinner,
				{
					options: {
						step: 15,
						page: 60,
						max: 60 * 23 + 45,
						min: 0,
						stop: function() {
							$( this ).change();
						},
						start: function() {
							return ( ! $( this ).attr( 'readonly' ) );
						}
					},
					_parse: function( value ) {
						return strtotime( value );
					},
					_format: function( value ) {
						return timetostr( value );
					}
				}
			);
			$tijd.timespinner();
		}
	}

	/**
	 * Definieer selecties met icons.
	 */
	function defineSelectMenus() {
		let $selectMenu = $( '.kleistad-selectmenu' );
		if ( $selectMenu[0] ) {
			$.widget(
				'custom.iconselectmenu',
				$.ui.selectmenu,
				{
					_renderItem: function( ul, item ) {
						let li  = $( '<li>' ),
						wrapper = $( '<div>', { text: item.label } );
						$(
							'<span>',
							{
								style: item.element.attr( 'data-style' ),
								'class': 'ui-icon ' + item.element.attr( 'data-class' )
							}
						)
						.appendTo( wrapper );
						return li.append( wrapper ).appendTo( ul );
					}
				}
			);
		}
	}

	/**
	 * Initieer de dynamische velden.
	 */
	function onLoad() {
		defineDatatables();
		defineDatums();
		defineTimespinners();
		defineSelectMenus();
	}

	/**
	 * Doe een vervolg actie na een ajax response (data).
	 *
	 * @param { jQuery } $shortcode
	 * @param { array } data
	 */
	$.fn.vervolg = function vervolg( $shortcode, data ) {
		if ( 'status' in data ) {
			$( '#kleistad_berichten' ).html( data.status );
		} else {
			$( '#kleistad_berichten' ).html( '' );
		}
		if ( 'content' in data ) {
			$shortcode.html( data.content );
			window.scrollTo( 0, 0 );
		}
		if ( 'file_uri' in data ) {
			window.open( data.file_uri, '_blank' );
		}
		if ( 'redirect_uri' in data ) {
			window.location.replace( data.redirect_uri );
		}
	};

	/**
	 * Get the selected item using Ajax.
	 *
	 * @param { jQuery } $shortcode
	 * @param { array} data
	 * @param { String } path naar het endpoint
	 */
	function getContent( $shortcode, data, path ) {
		$( '#kleistad_wachten' ).addClass( 'kleistad-wachten' ).show();
		// noinspection JSUnresolvedVariable .
		$.ajax(
			{
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				data: data,
				method: 'GET',
				url: kleistadData.base_url + '/' + path + '/'
			}
		).done(
			function( data ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad-wachten' );
				// noinspection JSUnresolvedFunction .
				$.fn.vervolg( $shortcode, data );
			}
		).fail(
			function( jqXHR ) {
				$( '#kleistad_wachten' ).removeClass( 'kleistad-wachten' );
				if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
					window.console.log( jqXHR.responseJSON.message );
				}
				// noinspection JSUnresolvedVariable .
				$( '#kleistad_berichten' ).html( kleistadData.error_message );
			}
		);
	}

	/**
	 * Verzamel de relevante gegevens van de shortcode.
	 *
	 * @param { jQuery} $element
	 */
	$.fn.shortcode = function shortcode( $element ) {
		let $shortcode    = $element.closest( '.kleistad-shortcode' );
		let shortcodeData = { tag:   $shortcode.data( 'tag' ) };
		if ( 'undefined' !== typeof $shortcode.data( 'atts' ) ) {
			shortcodeData.atts = JSON.stringify( $shortcode.data( 'atts' ) );
		}
		if ( 'undefined' !== typeof $element.data( 'id' ) ) {
			shortcodeData.id = $element.data( 'id' );
		}
		if ( 'undefined' !== typeof $element.data( 'actie' ) ) {
			shortcodeData.actie = $element.data( 'actie' );
		} else {
			shortcodeData.actie = '-';
		}
		return shortcodeData;
	};

	/**
	 * Wordt aangeroepen na elke ajax call.
	 */
	$( document ).ajaxComplete(
		function() {
			onLoad();
		}
	);

	/**
	 * Wordt aangeroepen nadat de webpage geladen is.
	 */
	$(
		function()
		{
			onLoad();

			$( '.kleistad-shortcode' )
			/**
			 * Als er op een edit anchor is geklikt, doe dan een edit actie.
			 */
			.on(
				'click',
				'.kleistad-edit-link',
				function() {
					let $anchor = $( this );
					// noinspection JSUnresolvedFunction .
					let shortcodeData = $.fn.shortcode( $anchor );
					getContent( $anchor.closest( '.kleistad-shortcode' ), shortcodeData, 'getitem' );
					return true;
				}
			)
			/**
			 * Als er op een terug anchor is geklikt
			 */
			.on(
				'click',
				'.kleistad-terug-link',
				function() {
					let $button = $( this );
					// noinspection JSUnresolvedFunction .
					let shortcodeData = $.fn.shortcode( $button );
					getContent( $button.closest( '.kleistad-shortcode' ), shortcodeData, 'getitems' );
					return true;
				}
			)
			/**
			 * Als er op een download button link is geklikt
			 */
			.on(
				'click',
				'.kleistad-download-link',
				function() {
					let $button = $( this );
					// noinspection JSUnresolvedFunction .
					let shortcodeData = $.fn.shortcode( $button );
					$( 'input,select' ).each(
						function() {
							shortcodeData[ $( this ).attr( 'name' ) ] = $( this ).val();
						}
					);
					getContent( $button.closest( '.kleistad-shortcode' ), shortcodeData, 'download' );
					return true;
				}
			)
			/**
			 * Voor de ondersteuning van touch events
			 */
			.on(
				'touchstart',
				function() {
					detectTap = true;
				}
			)
			.on(
				'touchmove',
				function() {
					detectTap = false;
				}
			);
		}
	);

} )( jQuery );
