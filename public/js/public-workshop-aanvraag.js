/**
 * Workshop aanvraag Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  7.0.0
 * @package Kleistad
 */

/* global kleistadData */

( function( $ ) {
	'use strict';

	/**
	 * Array van beschikbaarheid objecten.
	 *
	 * @type {*[]}   beschikbareData
	 * @type {array} beschikbareData.dagdelen
	 */
	let beschikbareData = [];

	/**
	 * Haal de mogelijke plandata voor de komende drie maanden op.
	 */
	function haalPlandata() {
		$.ajax(
			{
				url: kleistadData.base_url + '/aanvraag/',
				method: 'GET',
				beforeSend: function ( xhr) {
					xhr.setRequestHeader( 'X-WP-Nonce', kleistadData.nonce );
				}
			}
		).done(
			/**
			 * Plaats de ontvangen data in de tabel.
			 *
			 * @param {array} data
			 * @param {array} data.plandata
			 */
			function (data) {
				beschikbareData = Object.values( data.plandata );
				verwerkPlandata();
			}
		).fail(
			function ( jqXHR) {
				if ( 'undefined' !== typeof jqXHR.responseJSON.message ) {
					window.alert( jqXHR.responseJSON.message );
					return;
				}
				window.alert( kleistadData.error_message );
			}
		);
	}

	/**
	 * Verwerk de plandata in de datum picker.
	 */
	function verwerkPlandata() {
		$( '#kleistad_plandatum' ).datepicker(
			'option',
			{
				minDate: new Date( beschikbareData[ 0 ].datum ),
				maxDate: new Date( beschikbareData[ beschikbareData.length - 1 ].datum ),
				beforeShowDay: function( datum ) {
					let beschikbaar = 'undefined' !== typeof beschikbareData.find( o => o.datum === $.datepicker.formatDate( 'yy-mm-dd', datum ) );
					return [ beschikbaar, beschikbaar ? '' : 'ui-state-disabled' ];
				}
			}
		)
	}

	/**
	 * Document ready.
	 */
	$(
		function() {
			haalPlandata();

			$( '.kleistad-shortcode' )
				.on(
					'change',
					'#kleistad_plandatum',
					function () {
						let datum       = $( this ).datepicker( 'getDate' ),
							beschikbaar = beschikbareData.filter( o => o.datum === $.datepicker.formatDate( 'yy-mm-dd', datum ) );
						$( 'div[class^="kleistad-dagdeel"]' ).hide();
						$( 'input[name="dagdeel"]' ).prop( 'checked', false );
						beschikbaar.forEach(
							function( item ) {
								let $moment = $( '.kleistad-dagdeel-' + item.dagdeel );
								$moment.
									show().
									find( 'input[name="dagdeel"]' ).
									prop( 'checked', 1 === $moment.length && 1 === beschikbaar.length );
							}
						)
					}
				)
				.on(
					'keydown',
					'#kleistad_plandatum',
					function( event ) {
						event.preventDefault();
					}
				)
		}
	);

} )( jQuery );
