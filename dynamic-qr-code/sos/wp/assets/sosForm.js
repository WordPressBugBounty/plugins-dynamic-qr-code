function jsSosCombobox( value, id ) {
    let field = self.document.getElementById( id );
    if (field) {
        field.value = value;
        if (value === "") {
            field.focus();
        }
    } else {
        alert("A javascript problem occurred in your browser.");
    }
}
/*
function jsSosChecklist( id, me ) {
    let field = self.document.getElementById( id );
    if (field) {
        let values = field.value.split( ";" ).filter(element => element);
        if ( me.checked && !values.includes(me.value) ) {
            values.push(me.value);
        } else if ( !me.checked && values.includes(me.value) ) {
            values = values.filter( function(e, i, a) { return e !== me.value; }, me.value );
        }
        field.value = values.join( ";" );
    } else {
        alert("A javascript problem occurred in your browser.");
    }
}
*/