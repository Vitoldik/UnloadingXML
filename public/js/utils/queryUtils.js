function removeParamFromUrl(url, parameter) {
    return url
        .replace(new RegExp('[?&]' + parameter + '=[^&#]*(#.*)?$'), '$1')
        .replace(new RegExp('([?&])' + parameter + '=[^&]*&'), '$1');
}

function updateQueryString(uri, key, value) {
    const re = new RegExp("([?&])" + key + "=[^&#]*", "i")
    if (re.test(uri)) {
        return uri.replace(re, '$1' + key + "=" + value)
    } else {
        const matchData = uri.match(/^([^#]*)(#.*)?$/)
        const separator = /\?/.test(uri) ? "&" : "?"
        return matchData[0] + separator + key + "=" + value /*+ (matchData[1] || '')*/
    }
}

function getCurrentPage() {
    const page = Number.parseInt(urlParams.get('page'))

    return page ?? 1
}