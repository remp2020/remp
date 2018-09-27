export const GRAPH_COLORS = [
    "#eed075",
    "#eb8459",
    "#50c8c8",
    "#d49bc4",
    "#4c91b8",
    "#3DF16D"
]

export const REFRESH_DATA_TIMEOUT_MS = 30000

export const debounce = (fn, time) => {
    let timeout

    return function() {
        const functionCall = () => fn.apply(this, arguments)

        clearTimeout(timeout)
        timeout = setTimeout(functionCall, time)
    }
}

export const formatInterval = function(value, intervalMinutes) {
    if (value) {
        let start = moment(value)
        let end = start.clone().add(intervalMinutes, 'm')
        return start.format('ll HH:mm') + " - " + end.format('HH:mm')
    }
}