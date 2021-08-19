/**
 * Cursus beheer Kleistad javascript functies.
 *
 * @author Eric Sprangers.
 * @since  5.2.0
 * @package Kleistad
 */

/* global strtotime, timetostr, strtodate */

( function( $ ) {
	'use strict';

	let
		lesDatums  = [],
		startDatum = new Date(),
		eindDatum  = new Date();

	/**
	 * Maak het lijstje van de datums, gesorteerd van laag naar hoog, zichtbaar in het formulier.
	 */
	function listLesDatums() {
		let datums = [], lesDatumsLijst = '';
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
				let datum = strtodate( item );
				return datum <= eindDatum && datum >= startDatum;
			}
		);
		expandLesDatums( $.datepicker.formatDate( 'dd-mm-yy', startDatum ) );
		if ( eindDatum !== startDatum ) {
			expandLesDatums( $.datepicker.formatDate( 'dd-mm-yy', eindDatum ) );
		}
		$( '#kleistad_lesdatums' ).val( lesDatums.join( ';' ) );
	}

	/**
	 * Stel opnieuw de limieten in.
	 */
	function setLimits() {
		const dag = 24 * 60 * 60 * 1000;
		$( '#kleistad_eind_datum' ).datepicker( 'option', { minDate: startDatum } );
		if ( eindDatum.getDate() !== startDatum.getDate() ) {
			$( '#kleistad_start_datum' ).datepicker( 'option', { maxDate: eindDatum } );
		}
		$( '#kleistad_lesdatum' ).datepicker( 'option', { minDate: new Date( startDatum.getTime() + dag ), maxDate: new Date( eindDatum.getTime() - dag ) } );
	}

	/**
	 * Stel de start en einddatum in bij een wijziging van een van beide.
	 */
	function setDatepickers() {
		$( '#kleistad_start_datum, #kleistad_eind_datum' ).datepicker(
			'option',
			{
				onSelect: function( datum ) {
					if ( 'kleistad_start_datum' === $( this ).attr( 'id' ) ) {
						startDatum = strtodate( datum );
						if ( startDatum > eindDatum ) {
							eindDatum = startDatum;
							$( '#kleistad_eind_datum' ).datepicker( 'setDate', eindDatum );
						}
					} else {
						eindDatum = strtodate( datum );
					}
					setLimits();
					updateLesDatums();
					listLesDatums();
				},
				beforeShow: function() {
					setLimits();
				}
			}
		);

		/**
		 * Voeg extra lesdatums toe of verwijder deze.
		 */
		$( '#kleistad_lesdatum' ).datepicker(
			'option',
			{
				onSelect: function( datum ) {
					let index = $.inArray( datum, lesDatums );
					if ( index >= 0 ) {
						lesDatums.splice( index, 1 );
					} else {
						lesDatums.push( datum );
					}
					$( '#kleistad_lesdatums' ).val( lesDatums.join( ';' ) );
					listLesDatums();
				},
				beforeShowDay: function( datum ) {
					let gotDate = $.inArray( $.datepicker.formatDate( 'dd-mm-yy', datum ), lesDatums );
					if ( gotDate >= 0 ) {
						return [ true, 'kleistad-state-highlight' ];
					}
					return [ true, '' ];
				},
				beforeShow: function() {
					setLimits();
				},
				showOn: 'button',
				buttonText: 'Lesdatum &plusmn;'
			}
		);
	}

	/**
	 * Zorg dat de starttijd altijd eerder is dan de eindtijd.
	 */
	function setTimespinners() {
		const
			$start_tijd = $( '#kleistad_start_tijd' ),
			$eind_tijd  = $( '#kleistad_eind_tijd' );
		$start_tijd.timespinner(
			'option',
			{
				stop: function() {
					let
						startTijd = strtotime( $( this ).val() ),
						eindTijd  = strtotime( $eind_tijd.val() );
					if ( startTijd + 60 > eindTijd ) {
						$eind_tijd.val( timetostr( Math.min( startTijd + 60, 24 * 60 ) ) );
					}
				}
			}
		);

		$eind_tijd.timespinner(
			'option',
			{
				stop: function() {
					let
						startTijd = strtotime( $start_tijd.val() ),
						eindTijd  = strtotime( $( this ).val() );
					if ( startTijd > eindTijd - 60 ) {
						$start_tijd.val( timetostr( Math.max( eindTijd - 60, 0 ) ) );
					}
				}
			}
		);
	}

	/**
	 * Initialisatie.
	 */
	function onLoad() {
		const
			$start_datum = $( '#kleistad_start_datum' ),
			$eind_datum  = $( '#kleistad_eind_datum' );
		if ( $start_datum.length ) {  // Een willekeurig element om te bepalen of het formulier getoond wordt.
			startDatum = strtodate( $start_datum.val() );
			eindDatum  = strtodate( $eind_datum.val() );
			lesDatums  = $( '#kleistad_lesdatums' ).val().split( ';' );
			setLimits();
			listLesDatums();
			setDatepickers();
			setTimespinners();
			if ( $start_datum.prop( 'disabled' ) ) {
				$( '#kleistad_lesdatum' ).next( 'button' ).prop( 'disabled', true );
			}
		}
	}

	/**
	 * Bij de terugkeer na een post.
	 */
	$( document ).ajaxComplete(
		function() {
			onLoad();
		}
	);

	/**
	 * Document ready.
	 */
	$(
		function() {
			onLoad();
		}
	);

} )( jQuery );
