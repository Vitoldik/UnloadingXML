'use strict'

// Пагинация
const urlParams = new URLSearchParams(window.location.search)
const paginationContainer = document.querySelector('.pagination-container')
// Поиск
const searchContainer = document.querySelector('.search-container')
const searchInput = document.querySelector('#searchInput')
const searchColumnButton = document.querySelector('.btn-search-column')
// Сортировка
const sortContainer = document.querySelector('.sort-container')
const sortTypeButton = document.querySelector('.btn-sort-type')
const sortColumnButton = document.querySelector('.btn-sort-column')
// Фильтрация
const filterContainer = document.querySelector('.filter-container')
const filterColumnButton = document.querySelector('.btn-filter-price')
const priceMinInput = document.querySelector('#minPrice')
const priceMaxInput = document.querySelector('#maxPrice')

paginationContainer.addEventListener('click', (e) => {
    if (e.target.nodeName === 'A' && e.target.classList.contains('page-link')) {
        e.preventDefault()

        const buttonText = e.target.textContent
        let next

        if (!isNaN(buttonText)) {
            next = buttonText
        } else {
            if (buttonText === 'Следующая') {
                next = getCurrentPage() + 1
            } else {
                next = Math.max(getCurrentPage() - 1, 1)
            }
        }

        window.location.href = updateQueryString(document.URL, "page", next)
    }
})

//  Поиск
let searchColumn = ''

searchContainer.addEventListener('click', (e) => {
    e.preventDefault()

    if (e.target.classList.contains('dropdown-item')) {
        const parentElement = e.target.parentElement.parentElement

        if (!parentElement)
            return

        if (parentElement.classList.contains('search-column-dropdown-menu')) {
            searchColumn = setActiveColumn(e, searchColumnButton)
            return
        }
    }

    if (e.target.classList.contains('btn-search')) {
        const input = searchInput.value

        if (!input) {
            displayAlert('error', 'Ошибка при поиске!', 'Поле поиска пустое')
            return
        }

        if (!searchColumn) {
            displayAlert('error', 'Ошибка при поиске!', 'Колонка для поиска не указана!')
            return
        }

        let processedUrl = updateQueryString(document.URL, "searchColumn", searchColumn)
        processedUrl = updateQueryString(processedUrl, "searchText", input)
        window.location.href = removeParamFromUrl(processedUrl, 'page')
        return
    }

    if (e.target.classList.contains('btn-search-reset')) {
        let processedUrl = removeParamFromUrl(document.URL, "searchColumn")
        processedUrl = removeParamFromUrl(processedUrl, "searchText")
        window.location.href = removeParamFromUrl(processedUrl, 'page')
    }
})

// Сортировка
let sortType = ''
let sortColumn = ''
let sortLocale = {
    'ASC': 'По возрастанию',
    'DESC': 'По убыванию'
}

sortContainer.addEventListener('click', (e) => {
    e.preventDefault()

    if (e.target.classList.contains('dropdown-item')) {
        const parentElement = e.target.parentElement.parentElement

        if (!parentElement)
            return

        if (parentElement.classList.contains('sort-type-dropdown-menu')) {
            sortTypeButton.textContent = e.target.textContent
            sortType = e.target.dataset.sortType
            return
        }

        if (parentElement.classList.contains('sort-column-dropdown-menu')) {
            sortColumn = setActiveColumn(e, sortColumnButton)
            return
        }
    }

    if (e.target.classList.contains('btn-sort')) {
        if (!sortType) {
            displayAlert('error', 'Ошибка сортировки!', 'Тип сортировки не указан!')
            return
        }

        if (!sortColumn) {
            displayAlert('error', 'Ошибка сортировки!', 'Колонка для сортировки не указана!')
            return
        }

        let processedUrl = updateQueryString(document.URL, "sortType", sortType)
        processedUrl = updateQueryString(processedUrl, "sortColumn", sortColumn)
        window.location.href = removeParamFromUrl(processedUrl, 'page')
        return
    }

    if (e.target.classList.contains('btn-sort-reset')) {
        sortType = ''
        sortColumn = ''
        let processedUrl = removeParamFromUrl(document.URL, "sortType")
        processedUrl = removeParamFromUrl(processedUrl, "sortColumn")
        window.location.href = removeParamFromUrl(processedUrl, 'page')
    }
})

// Фильтрация
let filterColumn = ''

filterContainer.addEventListener('click', (e) => {
    e.preventDefault()

    if (e.target.classList.contains('dropdown-item')) {
        const parentElement = e.target.parentElement.parentElement

        if (!parentElement)
            return

        if (parentElement.classList.contains('filter-price-dropdown-menu')) {
            filterColumn = setActiveColumn(e, filterColumnButton)
        }

        return
    }

    if (e.target.classList.contains('btn-filter')) {
        if (!filterColumn) {
            displayAlert('error', 'Ошибка сортировки!', 'Колонка цены не указана!')
            return
        }

        const minPrice = priceMinInput.value
        const maxPrice = priceMaxInput.value

        if (!minPrice) {
            displayAlert('error', 'Ошибка сортировки!', 'Поле "минимальная цена" не заполнено!')
            return
        }

        if (!maxPrice) {
            displayAlert('error', 'Ошибка сортировки!', 'Поле "максимальная цена" не заполнено!')
            return
        }

        let processedUrl = updateQueryString(document.URL, "minPrice", minPrice)
        processedUrl = updateQueryString(processedUrl, "maxPrice", maxPrice)
        processedUrl = updateQueryString(processedUrl, "priceColumn", filterColumn)
        window.location.href = removeParamFromUrl(processedUrl, 'page')
        return
    }

    if (e.target.classList.contains('btn-filter-reset')) {
        filterColumn = ''
        let processedUrl = removeParamFromUrl(document.URL, "minPrice")
        processedUrl = removeParamFromUrl(processedUrl, "maxPrice")
        processedUrl = removeParamFromUrl(processedUrl, "priceColumn")
        window.location.href = removeParamFromUrl(processedUrl, 'page')
    }
})

// При перезагрузке страницы ставим параметры из url
document.addEventListener('DOMContentLoaded', (e) => {
    // Поиск
    if (urlParams.has('searchColumn')) {
        const column = urlParams.get('searchColumn')
        searchColumnButton.textContent = column
        searchColumn = column
    }

    if (urlParams.has('searchText')) {
        searchInput.value = urlParams.get('searchText')
    }

    // Сортировка
    if (urlParams.has('sortType')) {
        sortTypeButton.textContent = sortLocale[urlParams.get('sortType')]
    }

    if (urlParams.has('sortColumn')) {
        sortColumnButton.textContent = urlParams.get('sortColumn')
    }

    // Фильтрация
    if (urlParams.has('minPrice')) {
        priceMinInput.value = urlParams.get('minPrice')
    }

    if (urlParams.has('maxPrice')) {
        priceMaxInput.value = urlParams.get('maxPrice')
    }

    if (urlParams.has('priceColumn')) {
        filterColumnButton.textContent = urlParams.get('priceColumn')
    }
})

// Вспомогательные методы
function setActiveColumn(e, element) {
    const column = e.target.textContent
    element.textContent = column
    return column
}