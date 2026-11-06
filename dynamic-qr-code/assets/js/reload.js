function jsSosDqcAddEvent(fn) {
    if (window.addEventListener) {
        window.addEventListener('load', fn);
    } else if (window.attachEvent) {
        window['eload' + fn] = fn;
        window['load' + fn] = function (event) {
            window['eload' + fn](event);
        }
        window.attachEvent('onload', window['load' + fn]);
    } else {
        var _win_onload_ = window.onload;
        window.onload = function(event) {
            if ( _win_onload_ ) {
                _win_onload_(event);
                _win_onload_ = null;
            }
            fn(event);
        };
    }
}
