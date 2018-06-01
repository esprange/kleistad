( function( $ ) {
    'use strict';

    $( document ).ready(
        function() {

            $( '#kleistad_abo_pauze' ).click(
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_pauze' ).toggle( this.checked );
                }
            );

            $( '#kleistad_abo_einde' ).click(
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_einde' ).toggle( this.checked );
                }
            );

            $( '#kleistad_abo_start' ).click(
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_start' ).toggle( this.checked );
                }
            );

            $( '#kleistad_abo_wijziging' ).click(
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_wijziging' ).toggle( this.checked );
                }
            );

            $( '#kleistad_abo_betaalwijze' ).click(
                function() {
					$( '.kleistad_abo_veld' ).hide();
					$( '.kleistad_abo_betaalwijze' ).toggle( this.checked );
                }
            );

            $( '#kleistad_pauze_maanden' ).spinner({
                min:1,
                max:3
                }
            );

            $( '#kleistad_confirm' ).dialog({
                resizable: false,
                height: 190,
                autoOpen: false,
                width: 330,
                modal: true,
                buttons: [
                    {
                        id: 'btnJa',
                        text: 'Ja',
                        click: function() {
                                $( '#kleistad_abonnee_wijziging' ).submit();
                        }
                    },
                    {
                        id: 'btnNee',
                        text: 'Nee',
                        click: function() {
                            $( '.kleistad_abo_optie' ).prop( { checked: false, disabled: false } );
                            $( '.kleistad_abo_veld' ).hide();
                            $( this ).dialog( 'close' );
                        }
                    }
                ],
                open: function() {
                    $( '#btnNee' ).focus();
                    }
                }
            );

            $( '#kleistad_check_abonnee_wijziging' ).click(
                function() {
                    if ( $( '#kleistad_abo_einde' ).prop( 'checked' ) ) {
                        $( '#kleistad_confirm' ).html( '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Weet je zeker dat je jouw abonnement wilt beëindigen ?</p>' ).dialog( 'open' );
                    }
                    if ( $( '#kleistad_abo_pauze' ).prop( 'checked' ) ) {
                        $( '#kleistad_confirm' ).html( '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Weet je zeker dat je jouw abonnement wilt pauzeren ?</p>' ).dialog( 'open' );
                    }
                    if ( $( '#kleistad_abo_start' ).prop( 'checked' ) ) {
                        $( '#kleistad_confirm' ).html( '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Weet je zeker dat je jouw abonnement wilt hervatten ?</p>' ).dialog( 'open' );
                    }
                    if ( $( '#kleistad_abo_wijziging' ).prop( 'checked' ) ) {
                        $( '#kleistad_confirm' ).html( '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Weet je zeker dat je jouw abonnement wilt wijzigen ?</p>' ).dialog( 'open' );
                    }
                    if ( $( '#kleistad_abo_betaalwijze' ).prop( 'checked' ) ) {
                        $( '#kleistad_confirm' ).html( '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>Weet je zeker dat je de betaalwijze van jouw abonnement wilt wijzigen ?</p>' ).dialog( 'open' );
                    }
                    return false;
                }
            );
        }
    );

} )( jQuery );
