/* global strtotime, timetostr, strtodate */

( function( $ ) {
    'use strict';

	var lesDatums  = [], startDatum = new Date(), eindDatum = new Date();

	/**
	 * Maak het lijstje van de datums, gesorteerd van laag naar hoog, zichtbaar in het formulier.
	 */
	function listLesDatums() {
		var datums = [], lesDatumsLijst = '';
		if ( 0 === lesDatums.length ) {
			return;
		}
		lesDatums.forEach(
			function( item ) {
				datums.push( strtodate( item ) );
			}
		);
		datums.sort(
			function( a, b ) {
				return a - b;
			}
		);
		datums.forEach(
			function( item ) {
				lesDatumsLijst += '<li>' + $.datepicker.formatDate( 'dd-mm-yy', item ) + '</li>';
			}
		);
		$( '#kleistad_lesdatums_lijst' ).html( lesDatumsLijst );
	}

	/**
	 * Voeg een datum toe als die nog niet in de lijst zit.
	 *
	 * @param datum Datum in mm-dd-yy formaat.
	 */
	function expandLesDatums( datum ) {
		if ( 0 > $.inArray( datum, lesDatums ) ) {
			lesDatums.push( datum );
		}
	}

	/**
	 * Pas de lijst aan als de start of eind datum wijzigt.
	 */
	function updateLesDatums() {
		lesDatums = lesDatums.filter(
			function( item ) {
				var datum = strtodate( item );
				return datum <= eindDatum && datum >= startDatum;
			}
		);
		expandLesDatums( $.datepicker.formatDate( 'dd-mm-yy', startDatum ) );
		if ( eindDatum !== startDatum ) {
				expandLesDatums( $.datepicker.formatDate( 'dd-mm-yy', eindDatum ) );
		}
		$( '#kleistad_lesdatums' ).val( lesDatums.join( ';' ) );
	}

	function setLimits() {
		var dag = 24 * 60 * 60 * 1000;
		$( '#kleistad_eind_datum' ).datepicker( 'option', { minDate: startDatum } );
		if ( eindDatum.getDate() !== startDatum.getDate() ) {
			$( '#kleistad_start_datum' ).datepicker( 'option', { maxDate: eindDatum } );
		}
		$( '#kleistad_lesdatum' ).datepicker( 'option', { minDate: new Date( startDatum.getTime() + dag ), maxDate: new Date( eindDatum.getTime() - dag ) } );
	}

	function setDatepickers() {
		$( '#kleistad_start_datum' ).datepicker(
			'option',
			{
				onSelect: function( datum ) {
					startDatum = strtodate( datum );
					if ( startDatum > eindDatum ) {
						eindDatum = startDatum;
						$( '#kleistad_eind_datum' ).datepicker( 'setDate', eindDatum );
					}
					setLimits();
					updateLesDatums();
					listLesDatums();
				},
				beforeShow: setLimits()
			}
		);

		$( '#kleistad_eind_datum' ).datepicker(
			'option',
			{
				onSelect: function( datum ) {
					eindDatum = strtodate( datum );
					setLimits();
					updateLesDatums();
					listLesDatums();
				},
				beforeShow: setLimits()
			}
		);

		$( '#kleistad_lesdatum' ).datepicker(
			'option',
			{
				onSelect: function( datum ) {
					var index = $.inArray( datum, lesDatums );
					if ( index >= 0 ) {
						lesDatums.splice( index, 1 );
					} else {
						lesDatums.push( datum );
					}
					$( '#kleistad_lesdatums' ).val( lesDatums.join( ';' ) );
					listLesDatums();
				},
				beforeShowDay: function( datum ) {
					var gotDate = $.inArray( $.datepicker.formatDate( 'dd-mm-yy', datum ), lesDatums );
					if ( gotDate >= 0 ) {
						return [ true, 'kleistad-state-highlight' ];
					}
					return [ true, '' ];
				},
				beforeShow: setLimits(),
				showOn: 'button',
				buttonText: 'Lesdatum &plusmn;'
			}
		);
	}

	function setTimespinners() {
		$( '#kleistad_start_tijd' ).timespinner(
			'option',
			{
				stop: function() {
					var startTijd = strtotime( $( this ).val() );
					var eindTijd  = strtotime( $( '#kleistad_eind_tijd' ).val() );
					if ( startTijd + 60 > eindTijd ) {
						$( '#kleistad_eind_tijd' ).val( timetostr( Math.min( startTijd + 60, 24 * 60 ) ) );
					}
				}
			}
		);

		$( '#kleistad_eind_tijd' ).timespinner(
			'option',
			{
				stop: function() {
					var startTijd = strtotime( $( '#kleistad_start_tijd' ).val() );
					var eindTijd  = strtotime( $( this ).val() );
					if ( startTijd > eindTijd - 60 ) {
						$( '#kleistad_start_tijd' ).val( timetostr( Math.max( eindTijd - 60, 0 ) ) );
					}
				}
			}
		);
	}

	function onLoad() {
		if ( $( '#kleistad_start_datum' ).length ) {  // Een willekeurig element om te bepalen of het formulier getoond wordt.
			startDatum = strtodate( $( '#kleistad_start_datum' ).val() );
			eindDatum  = strtodate( $( '#kleistad_eind_datum' ).val() );
			lesDatums  = $( '#kleistad_lesdatums' ).val().split( ';' );
			setLimits();
			listLesDatums();
			setDatepickers();
			setTimespinners();
			if ( $( '#kleistad_start_datum' ).prop( 'disabled' ) ) {
				$( '#kleistad_lesdatum' ).next( 'button' ).prop( 'disabled', true );
			}
		}
	}

	$( document ).ajaxComplete(
		function() {
			onLoad();
		}
	);

	$(
		function() {
			onLoad();
		}
	);

} )( jQuery );
