/* global strtotime, timetostr, strtodate */

( function( $ ) {
    'use strict';

	var lesDatums  = [], startDatum = new Date(), eindDatum = new Date();

	/**
	 * Maak het lijstje van de datums, gesorteerd van laag naar hoog, zichtbaar in het formulier.
	 */
	function listLesDatums() {
		var scroll = $( '#kleistad_lesdatums_lijst' ).scrollTop(),
			datums  = [], lesDatumsLijst = '';
		if ( 0 === lesDatums.length ) {
			return;
		}
		lesDatums.forEach( function( item ) {
			datums.push( strtodate( item ) );
		} );
		datums.sort( function( a, b ) {
			return a - b;
		} );
		datums.forEach( function( item ) {
			lesDatumsLijst += $.datepicker.formatDate( 'dd-mm-yy', item ) + '<br/>';
		} );
		$( '#kleistad_lesdatums_lijst' ).html( lesDatumsLijst ).scrollTop( scroll );
	}

	/**
	 * Voeg een datum toe als die nog niet in de lijst zit.
	 *
	 * @param string datum Datum in mm-dd-yy formaat.
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
		lesDatums = lesDatums.filter( function( item ) {
			var datum = strtodate( item );
			return datum <= eindDatum && datum >= startDatum;
		} );
		expandLesDatums( $.datepicker.formatDate( 'dd-mm-yy', startDatum ) );
		if ( eindDatum !== startDatum ) {
			expandLesDatums( $.datepicker.formatDate( 'dd-mm-yy', eindDatum ) );
		}
		$( '#kleistad_lesdatums' ).val( lesDatums.join( ';' ) );
	}

	function setLimits() {
		var dag   = 24 * 60 * 60 * 1000;
		$( '#kleistad_eind_datum' ).datepicker( 'option', { minDate: startDatum } );
		$( '#kleistad_start_datum' ).datepicker( 'option', { maxDate: eindDatum } );
		$( '#kleistad_lesdatum' ).datepicker( 'option', { minDate: new Date( startDatum.getTime() + dag ), maxDate: new Date( eindDatum.getTime() - dag ) } );
	}

    $( document ).ready(
        function() {
			if ( $( '#kleistad_cursus_beheer_form' ).length ) {
				startDatum = strtodate( $( '#kleistad_start_datum' ).val() );
				eindDatum  = strtodate( $( '#kleistad_eind_datum' ).val() );
				lesDatums  = $( '#kleistad_lesdatums' ).val().split( ';' );
				setLimits();
				listLesDatums();

				$( '#kleistad_start_tijd' ).change(
					function() {
						var startTijd = strtotime( $( this ).val() );
						var eindTijd  = strtotime( $( '#kleistad_eind_tijd' ).val() );
						if ( startTijd + 60 > eindTijd ) {
							$( '#kleistad_eind_tijd' ).val( timetostr( Math.min( startTijd + 60, 24 * 60 ) ) );
						}
					}
				);

				$( '#kleistad_eind_tijd' ).change(
					function() {
						var startTijd = strtotime( $( this ).val() );
						var eindTijd  = strtotime( $( '#kleistad_eind_tijd' ).val() );
						if ( startTijd > eindTijd - 60 ) {
							$( '#kleistad_start_tijd' ).val( timetostr( Math.max( eindTijd - 60, 0 ) ) );
						}
					}
				);

				$( '#kleistad_start_datum' ).datepicker(
					{
						onSelect: function( datum ) {
							startDatum = strtodate( datum );
							setLimits();
							updateLesDatums();
							listLesDatums();
						},
						beforeShow: function() {
							setLimits();
						}
					}
				);

				$( '#kleistad_eind_datum' ).datepicker(
					{
						onSelect: function( datum ) {
							eindDatum = strtodate( datum );
							setLimits();
							updateLesDatums();
							listLesDatums();
						},
						beforeShow: function() {
							setLimits();
						}
					}
				);

				$( '#kleistad_lesdatum' ).datepicker(
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
						beforeShowDay: function( datumTekst ) {
							var gotDate = $.inArray( $.datepicker.formatDate( 'dd-mm-yy', datumTekst ), lesDatums );
							if ( gotDate >= 0 ) {
								return [ true, 'ui-state-highlight' ];
							}
							return [ true, '' ];
						},
						beforeShow: function() {
							setLimits();
						},
						showOn: 'button',
						buttonText: 'Lesdatum + / -'
					}
				);

			} else {
				$( '#kleistad_cursus_toevoegen' ).click(
					function() {
						window.location.href = $( this ).val();
					}
				);
			}

		}
    );

} )( jQuery );
