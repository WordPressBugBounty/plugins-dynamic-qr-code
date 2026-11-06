(function($){
    'use strict';
    $(document).ready(function()
    {
        let show_it = true;

        $('#icoToggleExIm').click(
            function() {
                if ( show_it === true ) {
                    $('#tabExIm').show();
                    $("#icoToggleExIm")
                        .removeClass("dashicons-arrow-down-alt2")
                        .addClass("dashicons-arrow-up-alt2")
                        .attr("title", "hide");
                    show_it = false;
                } else {
                    $('#tabExIm').hide();
                    $("#icoToggleExIm")
                        .removeClass("dashicons-arrow-up-alt2")
                        .addClass("dashicons-arrow-down-alt2")
                        .attr("title", "show");
                    show_it = true;
                }
            }
        );

    });
})(jQuery);
