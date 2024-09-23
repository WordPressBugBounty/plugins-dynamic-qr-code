let dyn_server_dt = null;
let dyn_client_timer = null;
let dyn_client_clock = null;
(function($){
    'use strict';
    $(document).ready(function()
    {
        if (!$.ajax) { return null; }

        dyn_client_clock = setInterval( jsDynQrClock, 1000 )

    });
})(jQuery);

function jsDynSetServerDateTime( value ) {
    dyn_server_dt = value;
}

function jsDynQrClock() {
    if ( dyn_server_dt == null ) {
        return;
    }
    if ( dyn_client_timer == null ) {
        dyn_client_timer = self.document.getElementById('timer');
        if ( dyn_client_timer == null ) {
            clearInterval( dyn_client_clock );
            alert('Your browser cannot find the timer element.');
            return;
        }
    }
    dyn_server_dt.setSeconds(dyn_server_dt.getSeconds() + 1);
    dyn_client_timer.innerHTML = dyn_server_dt.toDateString() + ' ' + dyn_server_dt.toLocaleTimeString();
}

const jsDynCypherObj = {
    base: 0,
    min: 0
};
let hidDynCypher = null;
function jsDynInitializeCypher(id, base, min) {
    jsDynCypherObj.base = base;
    jsDynCypherObj.min = min;
    let min64 = jsDynGetB64Len(min) + base;
    hidDynCypher = self.document.getElementById(id);
    let value = hidDynCypher.value;
    let curr64 = jsDynGetB64Len(value) + base;
    let ctrl = document.createElement('input');
    ctrl.setAttribute('type', 'number');
    ctrl.setAttribute('class', 'small-text');
    ctrl.setAttribute('min', min64);
    ctrl.setAttribute('step', 4);
    ctrl.setAttribute('value', curr64);
    ctrl.setAttribute('onchange', "jsDynSetCypher(this);");
    let container = self.document.getElementById(id + '_span');
    container.appendChild(ctrl);
}
function jsDynSetCypher(ctrl) {
    let value = jsDynGetBitLen( ctrl.value - jsDynCypherObj.base );
    if ( value < jsDynCypherObj.min ) {
        value = jsDynCypherObj.min;
    }
    let value64 = jsDynGetB64Len(value) + jsDynCypherObj.base; // manually inserted values can be freak
    if ( ctrl.value != value64 ) {
        ctrl.value = value64;
    }
    hidDynCypher.value = value;
}
function jsDynGetB64Len(value) {
    return 4 * Math.ceil( 2 * value / 3 );
}
function jsDynGetBitLen( value ) {
    return Math.floor( 3 * value / 8 );
}
