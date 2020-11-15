/* Function for adding parameters to the url as true */
function addParamsToURL(param, value = true, url = null) {
    if (url === null) {
        var url = window.location.href;
    }

    if (url.indexOf("?") > -1) {
        url += "&" + param + "=" + value;
    } else {
        url += "?" + param + "=" + value;
    }
    return url;
}

function removeParamsFromURL(param, value = true, url = null) {
    if (url === null) {
        var url = window.location.href;
    }

    /* Replacing the GET in case it's the starting value */
    url = url.replace("?" + param + "=" + value, "");

    /* Replacing the GET in case it's the starting value and has intermediate values */
    url = url.replace("?" + param + "=" + value + "&", "?");

    /* Replacing the GET in case it's an intermediate value */
    url = url.replace("&" + param + "=" + value, "");

    return url;
}
