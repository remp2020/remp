import {dispatchBeamEvent} from "./remplib";
export default function(config) {
    let watched  = {};

    const functionInterface =  {
        reset
    }

    if (!config.tracker.impressions || !config.tracker.impressions.enabled) {
        // do nothing
        return functionInterface;
    }

    let reportUrl = config.tracker.url + "/track/impressions";
    let globalItemMinVisibleDurationMs = config.tracker.impressions.itemMinVisibleDuration || 2000;

    const createIntersectionObserver =  (impressionConfig) => {
        const watchedKey = getWatchedKey(impressionConfig)

        let minVisibleDurationMs = globalItemMinVisibleDurationMs;
        if (impressionConfig.itemMinVisibleDuration) {
            minVisibleDurationMs = impressionConfig.itemMinVisibleDuration;
        }

        const itemElementIdFn = !!impressionConfig.itemElementIdFn ?
            impressionConfig.itemElementIdFn :
            (el) => el.id;

        return new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    const postId = itemElementIdFn(entry.target);
                    if (entry.isIntersecting) {
                        if (!watched[watchedKey].seen.has(postId) && !watched[watchedKey].seenTimers.has(postId)) {
                            const timerId = setTimeout(() => {
                                watched[watchedKey].seenTimers.delete(postId);
                                watched[watchedKey].seen.add(postId);
                                observer.unobserve(entry.target);
                            }, minVisibleDurationMs);
                            watched[watchedKey].seenTimers.set(postId, timerId);
                        }
                    } else {
                        if (watched[watchedKey].seenTimers.has(postId)) {
                            clearTimeout(watched[watchedKey].seenTimers.get(postId));
                            watched[watchedKey].seenTimers.delete(postId);
                        }
                    }
                });
            },
            {
                root: null, // null means the viewport
                rootMargin: "0px",
                threshold: 0.5, // Trigger when 50% of the element is visible
            }
        );
    }

    for (const impressionConfig of config.tracker.impressions.watched) {
        const intersectionObserver = createIntersectionObserver(impressionConfig);
        const observed = new Set();

        watched[getWatchedKey(impressionConfig)] = {
            seen: new Set(), // items confirmed as seen
            seenTimers: new Map(), // items waiting to be confirmed as seen
            lastSeenSize: 0,
            sent: new Set(),
            observed: observed,
            type: impressionConfig.type,
            block: impressionConfig.block,
            intersectionObserver: intersectionObserver,
            mutationObserver: createMutationObserver(
                intersectionObserver,
                impressionConfig,
                observed,
            ),
        }
    }

    // first report after 2.5s, later after 5s
    setTimeout(async () => {
        await sendPayload(preparePayload());
        setInterval(() => {
            sendPayload(preparePayload());
        }, 5000);
    }, 2500);

    document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === "hidden") {
            const payload = preparePayload();
            if (payload.d.length === 0) {
                return;
            }
            // the reliable way of sending analytics data when the page is hidden/closed/tab is switched
            const blobPayload = new Blob([JSON.stringify(payload)], { type: 'application/json' });
            navigator.sendBeacon(reportUrl, blobPayload);
        }
    });

    function preparePayload() {
        const payload = {
            d: [],
            rpid: remplib.getRempPageviewID(),
        };

        for (const [_, watchedData] of Object.entries(watched)) {
            if (watchedData.seen.size !== watchedData.lastSeenSize) {
                watchedData.lastSeenSize = watchedData.seen.size;
                const seenToReport = setDifference(watchedData.seen, watchedData.sent);

                payload.d.push({
                    bl: watchedData.block,
                    tp: watchedData.type,
                    eid: Array.from(seenToReport),
                })
                watchedData.sent = new Set(watchedData.seen);
            }
        }

        return payload;
    }

    async function sendPayload(payload) {
        if (payload.d.length === 0) {
            return; // no need to report
        }

        const response = await fetch(reportUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status} ${response.statusText}`);
        }

        dispatchBeamEvent(payload);
    }

    function reset() {
        if (watched) {
            for (const [watchedKey, watchedData] of Object.entries(watched)) {
                watchedData.intersectionObserver.disconnect();
                watchedData.mutationObserver.disconnect();
            }
            watched = {};
        }
    }

    return functionInterface;
}

function getWatchedKey(impressionConfig) {
    return impressionConfig.block + "_" + impressionConfig.type;
}

function createMutationObserver(intersectionObserver, impressionConfig, observed) {
    const observeNewItems = () => {
        document.querySelectorAll(impressionConfig.itemsQuerySelector).forEach((post) => {
            const elId = impressionConfig.itemElementIdFn(post);
            if (!observed.has(elId)) {
                observed.add(elId);
                intersectionObserver.observe(post);
            }
        });
    };

    observeNewItems();
    const mutationObserver = new MutationObserver(() => {
        observeNewItems();
    });
    let container = document.body;
    if (impressionConfig.containerQuerySelector) {
        container = document.querySelector(impressionConfig.containerQuerySelector);
    }

    mutationObserver.observe(container, { childList: true, subtree: true });
    return mutationObserver;
}

function setDifference(setA, setB) {
    if (!(setA instanceof Set) || !(setB instanceof Set)) {
        throw new TypeError("Both arguments have to be of type Set");
    }
    const difference = new Set();
    setA.forEach(element => {
        if (!setB.has(element)) {
            difference.add(element);
        }
    });
    return difference;
}
