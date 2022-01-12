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
					let dagdeel = $( 'input[name="dagdeel"]:checked' ).val();
					let beschikbaar;
					if ( 'undefined' === typeof dagdeel ) {
						beschikbaar = 'undefined' !== typeof beschikbareData.find( o => o.datum === $.datepicker.formatDate( 'yy-mm-dd', datum ) );
					} else {
						beschikbaar = 'undefined' !== typeof beschikbareData.find( o => ( o.datum === $.datepicker.formatDate( 'yy-mm-dd', datum ) ) && ( o.dagdeel === dagdeel.toLowerCase() ) );
					}
					return [ beschikbaar, beschikbaar ? '' : 'ui-state-disabled' ];
				}
			}
		);
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
					'.input[name="dagdeel"]',
					function () {
						haalPlandata();
					}
				)
				.on(
					'change',
					'#kleistad_plandatum',
					function () {
						let datum     = $( this ).datepicker( 'getDate' ),
							$dagdelen = $( 'div[class^="kleistad-dagdeel"]' );
						haalPlandata();
						if ( 'undefined' !== typeof datum ) {
							let beschikbaar = beschikbareData.filter( o => o.datum === $.datepicker.formatDate( 'yy-mm-dd', datum ) );
							if ( beschikbaar.length ) {
								$dagdelen.hide();
								$( 'input[name="dagdeel"]' ).attr( 'checked', false );
								beschikbaar.forEach(
									function( item ) {
										$( '.kleistad-dagdeel-' + item.dagdeel ).show().prop( 'checked', 1 === beschikbaar.length );
									}
								)
							} else {
								$dagdelen.show();
							}
						}
					}
				)
		}
	);

} )( jQuery );
