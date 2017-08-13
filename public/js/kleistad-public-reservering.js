(function( $ ) {
	'use strict';

    $(document).ready(function () {
        $('.kleistad_form_popup').each(function () {
            $(this).dialog({
                autoOpen: false,
                height: 500,
                width: 360,
                modal: true
            });
        });

        $('.kleistad_reserveringen').each(function () {
            var oven_id = $(this).data('oven_id');
            var maand = $(this).data('maand');
            var jaar = $(this).data('jaar');
            kleistad_show(oven_id, maand, jaar);
        });

        $('.kleistad_verdeel').change(function () {
            kleistad_verdeel(this);
        });

        $("body").on("click", '.kleistad_periode', function () {
            var oven_id = $(this).data('oven_id');
            var maand = $(this).data('maand');
            var jaar = $(this).data('jaar');
            kleistad_show(oven_id, maand, jaar);
        });

        $("body").on("click", '.kleistad_box', function () {
            $('#kleistad_oven').dialog('open');
            kleistad_form($(this).data('form'));
            return false;
        });

        $("body").on("click", '.kleistad_muteer', function () {
            kleistad_muteer(1);
        });

        $("body").on("click", '.kleistad_verwijder', function () {
            kleistad_muteer(-1);
        });

        $("body").on("click", '.kleistad_sluit', function () {
            $('#kleistad_oven').dialog('close');
        });
    });

    function kleistad_form(form_data) {
        $('#kleistad_oven_id').val(form_data.oven_id);
        $('#kleistad_wanneer').text(form_data.dag + '-' + form_data.maand + '-' + form_data.jaar);
        $('#kleistad_temperatuur').val(form_data.temperatuur);
        $('#kleistad_soortstook').val(form_data.soortstook);
        $('#kleistad_dag').val(form_data.dag);
        $('#kleistad_maand').val(form_data.maand);
        $('#kleistad_jaar').val(form_data.jaar);
        $('#kleistad_gebruiker_id').val(form_data.gebruiker_id);
        $('#kleistad_programma').val(form_data.programma);
        /* $('#kleistad_opmerking').val(form_data.opmerking); */
        $('#kleistad_stoker').html(form_data.gebruiker);
        $('#kleistad_1e_stoker').val(form_data.gebruiker_id);

        var stoker_ids = $('[name=kleistad_stoker_id]').toArray();
        var stoker_percs = $('[name=kleistad_stoker_perc]').toArray();

        var i = 0;
        for (i = 0; i < 5; i++) {
            stoker_ids[i].value = form_data.verdeling[i].id;
            stoker_percs[i].value = form_data.verdeling[i].perc;
        }
        if (form_data.gereserveerd == 1) {
            $('#kleistad_muteer').text('Wijzig');
            if (form_data.verwijderbaar == 1) {
                $('#kleistad_tekst').text('Wil je de reservering wijzigen of verwijderen ?');
                $('#kleistad_verwijder').show();
            } else {
                $('#kleistad_tekst').text('Wil je de reservering wijzigen ?');
                $('#kleistad_verwijder').hide();
            }
        } else {
            $('#kleistad_tekst').text('Wil je de reservering toevoegen ?');
            $('#kleistad_muteer').text('Voeg toe');
            $('#kleistad_verwijder').hide();
        }
    }

    function kleistad_verdeel(element) {
        var stoker_percs = $('[name=kleistad_stoker_perc]').toArray();
        var stoker_ids = $('[name=kleistad_stoker_id]').toArray();

        var i;
        var sum = 0;
        for (i = 1; i < 5; i++) { //stoker_percs.length; i++) {
            if (stoker_ids[i].value == '0') {
                stoker_percs[i].value = 0;
            }
            sum += +stoker_percs[i].value;
        }
        if (sum > 100) {
            element.value = element.value - (sum - 100);
            sum = 100;
        } else {
            element.value = +element.value;
        }
        stoker_percs[0].value = 100 - sum;
    }

    function kleistad_falen(message) {
        // rapporteer falen
        alert(message);
    }

    function kleistad_show(oven_id, maand, jaar) {
        jQuery.ajax({
            url: kleistad_data.base_url + '/show/',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', kleistad_data.nonce);
            },
            data: {
                maand: maand,
                jaar: jaar,
                oven_id: oven_id
            }
        }).done(function (data) {
            $('#reserveringen' + data.oven_id).html(data.html);
        }).fail(function (jqXHR, textStatus, errorThrown) {
            if ('undefined' != typeof jqXHR.responseJSON.message) {
                kleistad_falen(jqXHR.responseJSON.message);
                return;
            }
            kleistad_falen(kleistad_data.error_message);
        });
    }

    function kleistad_muteer(wijzigen) {
        $('#kleistad_oven').dialog('close');
        var stoker_percs = $('[name=kleistad_stoker_perc]').toArray();
        var stoker_ids = $('[name=kleistad_stoker_id]').toArray();
        var verdeling = {};

        // forceer dat de 1e stoker = de gebruiker...
        //verdeling[0] = {id: +$('#kleistad_gebruiker_id').val(), perc: +stoker_percs[0].value};
        for (var i = 0; i < 5; i++) { //stoker_ids.length; i++) {
            verdeling[i] = {id: +stoker_ids[i].value, perc: +stoker_percs[i].value};
        }

        $.ajax({
            url: kleistad_data.base_url + '/reserveer/',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', kleistad_data.nonce);
            },
            data: {
                dag: $('#kleistad_dag').val(),
                maand: $('#kleistad_maand').val(),
                jaar: $('#kleistad_jaar').val(),
                oven_id: $('#kleistad_oven_id').val() * wijzigen,
                temperatuur: $('#kleistad_temperatuur').val(),
                soortstook: $('#kleistad_soortstook').val(),
                gebruiker_id: $('#kleistad_gebruiker_id').val(),
                programma: $('#kleistad_programma').val(),
                verdeling: JSON.stringify(verdeling) /* , opmerking: $('#kleistad_opmerking').val() */
            }
        }).done(function (data) {
            $('#reserveringen' + data.oven_id).html(data.html);
        }).fail(function (jqXHR, textStatus, errorThrown) {
            if ('undefined' != typeof jqXHR.responseJSON.message) {
                kleistad_falen(jqXHR.responseJSON.message);
                return;
            }
            kleistad_falen(kleistad_data.error_message);
        });
    }
    
})( jQuery );
