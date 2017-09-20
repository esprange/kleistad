(function ($) {
    'use strict';

    function kleistad_form(form_data) {
        var row, aantal, stoker_ids, stoker_percs;

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

        aantal = $('[name=kleistad_stoker_id]').length; 
        while ( aantal > 2) {
            row = $('#kleistad_stoker_row').next();
            row.remove();
            aantal--;
        }
        $('[name=kleistad_stoker_id]').val('0');
        $('[name=kleistad_stoker_perc]').val('0');
        
        while (aantal < Math.max(form_data.verdeling.length, 5)) {
            row = $('#kleistad_stoker_row').next().clone(true);
            $('#kleistad_stoker_row').after(row);
            aantal++;
        }
        stoker_ids = $('[name=kleistad_stoker_id]').toArray();
        stoker_percs = $('[name=kleistad_stoker_perc]').toArray();
        
        form_data.verdeling.forEach(function (item, index) {
            stoker_ids[index].value = item.id;
            stoker_percs[index].value = item.perc;
        });        

        if (form_data.gereserveerd === 1) {
            $('#kleistad_muteer').text('Wijzig');
            if (form_data.verwijderbaar === 1) {
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
        var stoker_percs = $('[name=kleistad_stoker_perc]').toArray(),
            stoker_ids = $('[name=kleistad_stoker_id]').toArray(),
            sum = 0;
        stoker_ids.forEach(function (item, index) {
            if (index > 0) {
                if (item.value === '0') {
                    stoker_percs[index].value = 0;
                }
                sum += +stoker_percs[index].value;
            }
        });
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
        $.ajax({
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
            if ('undefined' !== typeof jqXHR.responseJSON.message) {
                kleistad_falen(jqXHR.responseJSON.message);
                return;
            }
            kleistad_falen(kleistad_data.error_message);
        });
    }

    function kleistad_muteer(wijzigen) {
        var stoker_percs = $('[name=kleistad_stoker_perc]').toArray(),
            stoker_ids = $('[name=kleistad_stoker_id]').toArray(),
            verdeling = [];
        stoker_ids.forEach(function (item, index) {
            if (stoker_percs[index].value !== '0') {
                verdeling.push({ id: +item.value, perc: +stoker_percs[index].value });
            }
        });
        $('#kleistad_oven').dialog('close');

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
            if ('undefined' !== typeof jqXHR.responseJSON.message) {
                kleistad_falen(jqXHR.responseJSON.message);
                return;
            }
            kleistad_falen(kleistad_data.error_message);
        });
    }

    $(document).ready(function () {
        $('.kleistad_form_popup').each(function () {
            $(this).dialog({
                autoOpen: false,
                height: 550,
                width: 360,
                modal: true
            });
        });

        $('.kleistad_reserveringen').each(function () {
            var oven_id = $(this).data('oven_id'),
                maand = $(this).data('maand'),
                jaar = $(this).data('jaar');
            kleistad_show(oven_id, maand, jaar);
        });

        $('.kleistad_verdeel').change(function () {
            kleistad_verdeel(this);
        });

        $('body').on('click', '.kleistad_periode', function () {
            var oven_id = $(this).data('oven_id'),
                maand = $(this).data('maand'),
                jaar = $(this).data('jaar');
            kleistad_show(oven_id, maand, jaar);
        });

        $('body').on('click touchstart', '.kleistad_box', function () {
            $('#kleistad_oven').dialog('open');
            kleistad_form($(this).data('form'));
            return false;
        });

        $('body').on('click', '.kleistad_muteer', function () {
            kleistad_muteer(1);
        });

        $('body').on('click', '.kleistad_verwijder', function () {
            kleistad_muteer(-1);
        });

        $('body').on('click', '.kleistad_sluit', function () {
            $('#kleistad_oven').dialog('close');
        });

        $('body').on('click touchstart', '#kleistad_stoker_toevoegen', function () {
            var row = $('#kleistad_stoker_row').next().clone(true);
            $('[name=kleistad_medestoker_row]').last().after(row);
            $('[name=kleistad_stoker_perc]').last().val(0);
            return false;
        });

    });

})(jQuery);
