function looksLikeIpAddress(token) {
    if (/^(\d{1,3}\.){3}\d{1,3}$/.test(token)) {
        return true;
    }
    // "::" or 8 groups, so that e.g. a "08:55:53" time column is not taken for IPv6
    return /^[0-9a-f:.]+$/i.test(token) && (token.includes('::') || token.split(':').length === 8);
}

export function ipRangeKey(range) {
    return range.ip_from + '|' + range.ip_to;
}

export function formatIpRange(range) {
    return range.ip_to ? range.ip_from + ' - ' + range.ip_to : range.ip_from;
}

export function parseIpRangeList(text) {
    const result = {ranges: [], duplicates: 0, ignored: []};
    const seen = new Set();

    for (const rawLine of text.split('\n')) {
        const line = rawLine.trim();
        if (line === '') {
            continue;
        }

        const tokens = line.split(/[\s;,"'\-–—]+/).filter(looksLikeIpAddress);
        if (tokens.length === 0) {
            result.ignored.push(line);
            continue;
        }

        const range = {ip_from: tokens[0], ip_to: tokens[1] || null};
        const key = ipRangeKey(range);
        if (seen.has(key)) {
            result.duplicates++;
            continue;
        }
        seen.add(key);
        result.ranges.push(range);
    }

    return result;
}
