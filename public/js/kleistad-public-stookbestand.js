(function ($) {
    'use strict';

    $(document).ready(function () {

        $(".kleistad_datum").each(function () {
            $(this).datepicker({
                dateFormat: "dd-mm-yy"
            });
        });
        
    });

})(jQuery);
