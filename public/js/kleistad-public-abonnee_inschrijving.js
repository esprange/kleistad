(function ($) {
    'use strict';

    $(document).ready(function () {
        $('input[name=abonnement_keuze]').change(function () {
            if (this.value === 'beperkt') {
                $('#kleistad_dag').css('visibility', 'visible');
            } else {
                $('#kleistad_dag').css('visibility', 'hidden');
            }
        });
        $(".kleistad_datum").datepicker({
            dateFormat: "dd-mm-yy"
        });

    });

})(jQuery);
