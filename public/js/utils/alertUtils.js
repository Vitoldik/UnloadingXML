'use strict'

const types = {
    error: '#d34747',
    success: '#57c445',
    default: 'black'
}

function displayAlert(type, title, message) {
    const color = types[type] || types.default

    iziToast.show({
        title: title,
        message: message,
        color: color,
        titleColor: 'white',
        messageColor: 'white',
        progressBarColor: '#25aae1',
    })
}
