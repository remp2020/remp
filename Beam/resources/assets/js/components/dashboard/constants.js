export const GRAPH_COLORS = [
    "#E63952",
    "#00C7DF",
    "#FFC34A",
    "#DEDEDE",
    "#CDE092",
    "#3B40b6",
]

// empirically defined values
export const CONVERSIONS_COLORING_THRESHOLD = {
    low: 3,
    medium: 8,
    high: 13
}

// empirically defined values
export const CONVERSION_RATE_COLORING_THRESHOLD = {
    low: 3.0,
    medium: 5.0,
    high: 7.0
}

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