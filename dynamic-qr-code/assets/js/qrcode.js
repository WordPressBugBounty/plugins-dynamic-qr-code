(function($){
    'use strict';
    $(document).ready(function()
    {
        if (!$.ajax) { return null; }

        $('#a-std').click( function() {
            $('#a-enh').removeClass('nav-tab-active');
            $('#a-std').addClass('nav-tab-active');
            $('#div-enh').hide();
            $('#div-std').show();
        });
        $('#a-enh').click( function() {
            $('#a-std').removeClass('nav-tab-active');
            $('#a-enh').addClass('nav-tab-active');
            $('#div-std').hide();
            $('#div-enh').show();
        });

    });
})(jQuery);
