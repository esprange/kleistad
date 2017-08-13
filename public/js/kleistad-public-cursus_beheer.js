(function ($) {
    'use strict';

    $(document).ready(function () {
//        $(document).tooltip();

        $('.kleistad_rapport').DataTable({
            language: {
                    "sProcessing": "Bezig...",
                    "sLengthMenu": "_MENU_ resultaten weergeven",
                    "sZeroRecords": "Geen resultaten gevonden",
                    "sInfo": "_START_ tot _END_ van _TOTAL_ resultaten",
                    "sInfoEmpty": "Geen resultaten om weer te geven",
                    "sInfoFiltered": " (gefilterd uit _MAX_ resultaten)",
                    "sInfoPostFix": "",
                    "sSearch": "Zoeken:",
                    "sEmptyTable": "Geen resultaten aanwezig in de tabel",
                    "sInfoThousands": ".",
                    "sLoadingRecords": "Een moment geduld aub - bezig met laden...",
                    "oPaginate": {
                            "sFirst": "Eerste",
                            "sLast": "Laatste",
                            "sNext": "Volgende",
                            "sPrevious": "Vorige"
                    },
                    "oAria": {
                            "sSortAscending":  ": activeer om kolom oplopend te sorteren",
                            "sSortDescending": ": activeer om kolom aflopend te sorteren"
                    }
            },
            pageLength: 5,
            order: [0, 'desc'],
            columnDefs: [
                {"visible": false, "targets": [0]}
              ]

        });


        $(".kleistad_tijd").each(function () {
            $(this).timeEntry({
                show24Hours: true,
                spinnerImage: ""
            });
        });

        $(".kleistad_datum").each(function () {
            $(this).datepicker({
                dateFormat: "dd-mm-yy"
            });
        });

        $('#kleistad_cursus').dialog({
                    autoOpen: false,
                    height: 550,
                    width: 750,
                    modal: true,
            open: function (event, ui) {
                $('#kleistad_cursus_tabs').tabs({active: 0});
            }
        });

        $('body').on('hover', '.kleistad_cursus_info', function () {
            $(this).css('cursor', 'pointer');
            $(this).toggleClass('kleistad_hover');
        });

        $('body').on('click', '.kleistad_cursus_info', function () {
            $('#kleistad_cursus').dialog('open');
            var cursus = $(this).data('cursus');
            var wachtlijst = $(this).data('wachtlijst');
            var ingedeeld = $(this).data('ingedeeld');
            $('input[name="cursus_id"]').val(cursus.id);
            $('#kleistad_cursus_naam').val(cursus.naam);
            $('#kleistad_cursus_docent').val(cursus.docent);
            $('#kleistad_cursus_start_datum').val(cursus.start_datum);
            $('#kleistad_cursus_eind_datum').val(cursus.eind_datum);
            $('#kleistad_cursus_start_tijd').val(cursus.start_tijd.substr(0, 5));
            $('#kleistad_cursus_eind_tijd').val(cursus.eind_tijd.substr(0, 5));
            $('#kleistad_cursuskosten').val(cursus.cursuskosten);
            $('#kleistad_inschrijfkosten').val(cursus.inschrijfkosten);
            $('#kleistad_inschrijfslug').val(cursus.inschrijfslug);
            $('#kleistad_indelingslug').val(cursus.indelingslug);
            $('#kleistad_draaien').prop("checked", String(cursus.technieken).indexOf('Draaien') >= 0);
            $('#kleistad_handvormen').prop("checked", String(cursus.technieken).indexOf('Handvormen') >= 0);
            $('#kleistad_boetseren').prop("checked", String(cursus.technieken).indexOf('Boetseren') >= 0);
            $('#kleistad_techniekkeuze').prop("checked", cursus.techniekkeuze > 0);
            $('#kleistad_vol').prop("checked", cursus.vol > 0);
            $('#kleistad_vervallen').prop("checked", cursus.vervallen > 0);
            $('#kleistad_wachtlijst').children().remove().end();
            $.each(wachtlijst, function (key, value) {
                $('#kleistad_wachtlijst').append(new Option(value['naam'], JSON.stringify(value), true, true));
            });
            $('#kleistad_indeling').children().remove().end();
            $.each(ingedeeld, function (key, value) {
                var option = new Option(value['naam'], JSON.stringify(value), true, true);
                option.style.backgroundColor = 'lightgreen';
                option.style.fontWeight = 700; // bold
                $('#kleistad_indeling').append(option);
            });
        });

        $('body').on('click', '#kleistad_cursus_toevoegen', function () {
            $('#kleistad_cursus').dialog('open');
            $('input[name="cursus_id"]').removeAttr('value');
            $('#kleistad_cursus_naam').removeAttr('value');
            $('#kleistad_cursus_docent').removeAttr('value');
            $('#kleistad_cursus_start_datum').removeAttr('value');
            $('#kleistad_cursus_eind_datum').removeAttr('value');
            $('#kleistad_cursus_start_tijd').removeAttr('value');
            $('#kleistad_cursus_eind_tijd').removeAttr('value');
            $('#kleistad_cursuskosten').prop('defaultValue');
            $('#kleistad_inschrijfkosten').prop('defaultValue');
            $('#kleistad_inschrijfslug').prop('defaultValue');
            $('#kleistad_indelingslug').prop('defaultValue');
            $('#kleistad_draaien').prop("checked", false);
            $('#kleistad_handvormen').prop("checked", false);
            $('#kleistad_boetseren').prop("checked", false);
            $('#kleistad_techniekkeuze').prop("checked", false);
            $('#kleistad_vol').prop("checked", false);
            $('#kleistad_vervallen').prop("checked", false);
            $('#kleistad_wachtlijst').children().remove().end();
            $('#kleistad_indeling').children().remove().end();
        });

        $('body').on('click', '[name="kleistad_submit_cursus_beheer"]', function () {
            var options = $('#kleistad_indeling option');
            var cursisten = $.map(options, function (option) {
                var element = JSON.parse(option.value);
                return Number(element['id']);
            });
            $('#kleistad_indeling_lijst').val(JSON.stringify(cursisten));
        });

        $('body').on('click', '#kleistad_wissel_indeling', function () {
            var ingedeeld = $('#kleistad_indeling option:selected');
            var wachtend = $('#kleistad_wachtlijst option:selected');
            if (ingedeeld.length) {
                var element = JSON.parse(ingedeeld.val());
                if (element['ingedeeld'] === 0) {
                    return !ingedeeld.remove().appendTo('#kleistad_wachtlijst');
                }
            }
            if (wachtend.length) {
                return !wachtend.remove().appendTo('#kleistad_indeling');
            }
            return false;
        });

        $('body').on('click', '#kleistad_wachtlijst', function () {
            $('#kleistad_indeling option:selected').prop('selected', false);
            $('#kleistad_cursist_technieken').empty();
            $('#kleistad_cursist_opmerking').empty();
            var cursist = $('option:selected', this);
            if (cursist.length) {
                kleistad_toon_cursist(cursist);
            }
        });

        $('body').on('click', '#kleistad_indeling', function () {
            $('#kleistad_wachtlijst option:selected').prop('selected', false);
            $('#kleistad_cursist_technieken').empty();
            $('#kleistad_cursist_opmerking').empty();
            var cursist = $('option:selected', this);
            if (cursist.length) {
                kleistad_toon_cursist(cursist);
            }
        });

    });

    function kleistad_toon_cursist(cursist) {
        var element = JSON.parse(cursist.val());
        var opmerking = element['opmerking'];
        var technieken = element['technieken'];

        if (technieken !== null) {
            var techniektekst = '<p>Gekozen technieken : ';
            $.each(technieken, function (key, value) {
                techniektekst += value + ' ';
            });
            techniektekst += '</p>';
            $('#kleistad_cursist_technieken').html(techniektekst);
        }
        if (opmerking.length > 0) {
            $('#kleistad_cursist_opmerking').html('<p>Opmerking : ' + opmerking + '</p>');
        }
    }

})(jQuery);
