function jsSosDynQrCode_StatMode( value ) {
    let selId = document.getElementById('$CTRL_ID$');
    let selKey = document.getElementById('$CTRL_KEY$');
    if ( value == '$MODE_ID$' ) {
        selKey.style.display = 'none';
        selId.style.display = 'inline';
    } else if ( value == '$MODE_KEY$' ) {
        selId.style.display = 'none';
        selKey.style.display = 'inline';
    }
}