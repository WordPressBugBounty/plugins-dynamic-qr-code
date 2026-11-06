function jsSosDqcDeleteLog( id, v ) {
    let field = self.document.getElementById(id);
    if (field) {
        if ( self.confirm("Do you confirm to delete this log entry?") ) {
            field.value = v;
        } else {
            field.value = 0;
        }
        return field.value > 0;
    } else {
        alert('The browser cannot find the associated HTML control.');
        return false;
    }
}
