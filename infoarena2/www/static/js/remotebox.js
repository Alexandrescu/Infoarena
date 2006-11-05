/**
 * DHTML for RemoteBox macro 
 * (c) 2006 info-arena
 */ 
var RemoteBox_Url = '';

function RemoteBox_Init() {
    var container = $('remotebox');
    if (!container || !RemoteBox_Url) {
        // no remotebox in this page
        return;
    }

    // visual clue to indicate that remotebox is loading
    container.innerHTML = '<div class="loading"> <img src="/static/images/indicator.gif" />Se incarca ...</div>';

    var d = doSimpleXMLHttpRequest(RemoteBox_Url);

    var ready = function(data) {
        if (data && data.responseText) {
            container.innerHTML = data.responseText;
        }
    }

    var error = function(error) {
        container.innerHTML = '<div class="macro_error">Continutul nu a putut fi descarcat. Incercati din nou.</div>';
    }

    d.addCallbacks(ready, error);
}

connect(window, 'onload', RemoteBox_Init);

