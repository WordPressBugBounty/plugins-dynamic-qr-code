function jsSosFallbackCopy2Clipboard( value ) {
    let ta = document.createElement("textarea");
    ta.value = value;

    ta.style.top = "-100";
    ta.style.left = "-100";
    ta.style.position = "fixed";
    ta.style.display = "none";

    document.body.appendChild(ta);
    ta.focus();
    ta.select();

    try {
        if ( document.execCommand('copy') ) {
            alert("Text Copied to clipboard:\n" + value);
        } else  {
            alert("Your browser didn't copy the content.");
        }
    } catch (err) {
        alert("Your browser didn't copy the content. Problem:\n" + err);
    }

    document.body.removeChild(ta);
}

function jsSosCopy2Clipboard( value ) {
   if (navigator.clipboard) {
        navigator.clipboard.writeText(value).then(function () {
            alert("Text Copied to clipboard:\n" + value);
        }, function (err) {
            alert("Your browser didn't copy the content. Problem:\n" + err);
        });
    } else {
        jsSosFallbackCopy2Clipboard(value);
    }
    document.activeElement.blur();
}
