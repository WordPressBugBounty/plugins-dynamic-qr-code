function jsSosFallbackCopy2Clipboard( value ) {
    let ta = document.createElement("textarea");
    ta.value = String(value ?? "");
    ta.setAttribute("readonly", "");

    ta.style.position = "fixed";
    ta.style.top = "0";
    ta.style.left = "0";
    ta.style.width = "1px";
    ta.style.height = "1px";
    ta.style.opacity = "0";
    //ta.style.display = "none";

    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    ta.setSelectionRange(0, ta.value.length);

    let copied = false;
    try {
        copied = document.execCommand("copy");
    } catch (err) {
        console.error("Errore copia:", err);
    }

    document.body.removeChild(ta);

    if (copied) {
        alert("Text copied to clipboard:\n" + value);
    } else {
        alert("Your browser didn't copy the content.");
    }


}

function jsSosCopy2Clipboard( value ) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(value).then(function () {
            alert("Text copied to clipboard:\n" + value);
        }, function (err) {
            alert("Your browser didn't copy the content. Problem:\n" + err);
        });
    } else {
        jsSosFallbackCopy2Clipboard(value);
    }
    document.activeElement.blur();
}

