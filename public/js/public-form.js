/**
 * Kleistad javascript functies voor formulieren.
 *
 * @author Eric Sprangers.
 * @since  6.0.0
 * @package Kleistad
 * noinspection EqualityComparisonWithCoercionJS
 */

/* global kleistadData */

/**
 * Jquery part
 */
( function( $ ) {
	'use strict';

	let currentTab = 0,
		firstTab   = 0;

	/**
	 * Initialiseer een multiform tab.
	 */
	function initTab() {
		let $tab = $( '.kleistad-tab' );
		let html = '<div style="overflow:auto;float:right;margin-top: 20px;">' +
			'<button type="button" class="kleistad-button" id="kleistad_tab_prev" >Terug</button>&nbsp;' +
			'<button type="button" class="kleistad-button" id="kleistad_tab_next" >Verder</button>' +
			'<button type="submit" class="kleistad-button" id="kleistad_submit" >Verzenden</button>' +
			'</div><div style="text-align: center;margin-top: 40px;">';
		$tab.each(
			function() {
				html += '<span class="kleistad-stap"></span>';
			}
		);
		html += '</div>';
		$tab.last().after( html );
		showTab( 0 );
	}

	/**
	 * Valideer de tab van een multistep form
	 */
	function validateTab() {
		let $stap = $( '.kleistad-stap' );
		let valid = true;
		$( '.kleistad-tab' ).eq( currentTab ).find( '[required]' ).each(
			function() {
				if ( ! ( ( null === this.offsetParent  ) ? this.checkValidity() : this.reportValidity() ) ) {
					valid = false;
				}
			}
		)
		$stap.eq( currentTab ).toggleClass( 'gereed', valid );
		$( '#kleistad_submit' ).val( $( '#kleistad_submit_value' ).val() );
		return valid;
	}

	/**
	 * Toon de tab en buttons van een multistep form
	 *
	 * @param {int} direction
	 */
	function showTab( direction ) {
		let $tab   = $( '.kleistad-tab' ),
			$stap  = $( '.kleistad-stap' ),
			maxTab = $tab.length,
			show   = false;
		if ( ! $tab[0] ) {
			return;
		}
		$tab.eq( currentTab ).hide();
		do {
			currentTab += direction;
			$tab.eq( currentTab ).children().each(
				function() {
					if ( 'none' !== ( $( this ).css( 'display' ) ) ) {
						show = true;
					}
				}
			);
			$stap.eq( currentTab ).toggle( show );
			if ( 0 === direction && ! show ) {
				currentTab++;
				firstTab++;
			}
		} while ( ! show && currentTab < maxTab );
		$tab.eq( currentTab ).show().css( 'display', 'flex' );
		$( '#kleistad_tab_prev' ).toggle( firstTab !== currentTab );
		$( '#kleistad_tab_next' ).toggle( $tab.length - 1 !== currentTab );
		$( '#kleistad_submit' ).toggle( $tab.length - 1 === currentTab );
		$stap.removeClass( 'actief' );
		$stap.eq( currentTab ).addClass( 'actief' );
	}

	/**
	 * Zoek de postcode op via de server.
	 */
	$.fn.lookupPostcode = function( postcode, huisnr, callback ) {
		if ( '' !== postcode && '' !== huisnr ) {
			$.ajax(
				{
					url: kleistadData.base_url + '/adres/',
					method: 'GET',
					data: {
						postcode: postcode,
						huisnr:   huisnr
					}
				}
			).done(
				function( data ) { // Als er niets gevonden kan worden dan code 204, data is dan undefined.
					if ( 'undefined' !== typeof data ) {
						callback( data );
					}
				}
			); // Geen verdere actie ingeval van falen.
		}
	};

	/**
	 * Submit een Kleistad formulier via Ajax call
	 *
	 * @param { jQuery } $shortcode
	 * @param { array} data
	 */
	function submitForm( $shortcode, data ) {
		/**
		 *  Bij een submit de spinner tonen.
		 */
		$( '#kleistad_wachten' ).addClass( 'kleistad-wachten' ).show();
		$.ajax(
			{
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				},
				contentType: false,
				data:        data,
				method:      'POST',
				processData: false,
				url:         kleistadData.base_url + '/formsubmit/'
			}
		).done(
			/**
			 * Toggle de wacht zandloper
			 *
			 * @param data
			 */
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
				$( '#kleistad_berichten' ).html( kleistadData.error_message );
			}
		);
	}

	/**
	 * Toon een confirmatie popup dialoog.
	 *
	 * @param {String} tekst Te tonen tekst. Als leeg dan wordt er geen popup getoond.
	 * @param callback Uit te voeren actie indien ok.
	 */
	$.fn.askConfirm = function( tekst, callback ) {
		if ( tekst.length ) {
			$( '#kleistad_bevestigen' ).text( tekst[1] ).dialog(
				{
					modal: true,
					zIndex: 10000,
					autoOpen: true,
					width: 'auto',
					resizable: false,
					title: tekst[0],
					open: function() {
						$( '.ui-dialog' ).find( '.ui-button' ).addClass( 'kleistad-button' ).removeClass( 'ui-button' );
					},
					buttons: [
					{
						text: 'Ja',
						click: function() {
							$( this ).dialog( 'close' );
							callback();
							return true;
						}
					},
					{
						text: 'Nee',
						click: function() {
							$( this ).dialog( 'close' );
							return false;
						}
					}
					]
				}
			);
			return false;
		}
		callback();
		return true;
	}

	/**
	 * Wordt aangeroepen nadat de webpage geladen is.
	 */
	$(
		function()
		{
			/**
			 * Tab voor multiforms
			 */
			initTab();

			/**
			 * Definieer de bank selectie.
			 */
			let $bank = $( '#kleistad_bank' );
			if ( $bank[0] ) {
				$bank.iconselectmenu().iconselectmenu( 'menuWidget' ).addClass( 'ui-menu-icons kleistad-bank' );
			}

			/**
			 * Definieer de bevestig dialoog.
			 */
			$( '#kleistad_bevestigen' ).dialog(
				{
					autoOpen: false,
					modal: true
				}
			);

			$( '.kleistad-shortcode' )
			/**
			 * Leg voor de submit actie vast welke button de submit geïnitieerd heeft.
			 */
			.on(
				'click',
				'button[type="submit"]',
				function( event ) {
					$( this ).closest( 'form' ).data( 'clicked', { id: event.target.id, value: event.target.value } );
					return true;
				}
			)
			/**
			 * Submit het formulier, als er een formulier is.
			 *
			 * @property {function} $.fn.shortcode
			 */
			.on(
				'submit',
				'form',
				function( event ) {
					let $form = $( this );
					// noinspection JSUnresolvedFunction .
					let shortcodeData = $.fn.shortcode( $form );
					let formData      = new FormData( this );
					let clicked       = $form.data( 'clicked' );
					let confirm       = $( '#' + clicked.id ).data( 'confirm' );
					let tekst         = 'undefined' === typeof confirm ? [] : confirm.split( '|' );
					formData.append( 'form_actie', clicked.value );
					Object.keys( shortcodeData ).forEach(
						function( item ) {
							formData.append( item, shortcodeData[item] );
						}
					);
					event.preventDefault();
					/**
					 * Als er een tekst is om eerst te confirmeren dan de popup tonen.
					 */
					return $().askConfirm(
						tekst,
						function() {
							submitForm( $form.closest( '.kleistad-shortcode' ), formData );
						}
					);
				}
			)
			/**
			 * Verwerk een klik op een multi step formulier.
			 */
			.on(
				'click',
				'#kleistad_tab_next',
				function() {
					if ( ! validateTab() ) {
						return false;
					}
					showTab( +1 );
					return true;
				}
			)
			.on(
				'click',
				'#kleistad_tab_prev',
				function() {
					validateTab();
					showTab( -1 );
				}
			)
			/**
			 * Dit is voor de multi step formulieren, het bevestiging scherm.
			 */
			.on(
				'change spinchange',
				'input, select, textarea',
				function() {
					let naam = $( this ).attr( 'name' );
					$( '#' + ( 'bevestig_' + naam ).replace( /[^a-z_]/g, '' ) ).html(
						$( this ).is( ':radio' ) ?
							$( 'input[name=' + naam + ']:checked' ).val() :
							$( this ).val()
					)
				}
			)
			/**
			 * Controle voor gelijke email adressen
			 */
			.on(
				'input',
				'#kleistad_emailadres_controle',
				function() {
					this.setCustomValidity( $( this ).val().toUpperCase() === $( '#kleistad_emailadres' ).val().toUpperCase() ? '' : 'E-mailadressen zijn niet gelijk' );
				}
			)
			/**
			 * Vul adresvelden in
			 */
			.on(
				'change',
				'#kleistad_huisnr, #kleistad_pcode',
				function () {
					let pcode = $( '#kleistad_pcode' );
					pcode.val( pcode.val().toUpperCase().replace( /\s/g, '' ) );
					$().lookupPostcode(
						pcode.val(),
						$( '#kleistad_huisnr' ).val(),
						/**
						 * Anonieme functie
						 *
						 * @param {object} data
						 * @param {string} data.straat
						 * @param {string} data.plaats
						 */
						function (data) {
							$( '#kleistad_straat' ).val( data.straat ).trigger( 'change' );
							$( '#kleistad_plaats' ).val( data.plaats ).trigger( 'change' );
						}
					);
				}
			)
			/**
			 * Wijzig de button tekst bij betaling dan wel aanmelding.
			 */
			.on(
				'change',
				'input[name=betaal]:radio',
				function () {
					$( '#kleistad_submit' ).html( ( 'ideal' === $( this ).val() ) ? 'betalen' : 'verzenden' );
				}
			);

		}
	);

} )( jQuery );
