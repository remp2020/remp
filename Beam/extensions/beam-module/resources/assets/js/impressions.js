import {dispatchBeamEvent} from "./remplib";
export default function(config) {
    const observed  = [];

    // return public methods
    const functionInterface =  {
        reset
    }

    if (!config.tracker.impressions || !config.tracker.impressions.enabled) {
        // do nothing
        return functionInterface;
    }

    const reportUrl = config.tracker.url + "/track/impressions";
    const globalItemMinVisibleDurationMs = config.tracker.impressions.itemMinVisibleDuration || 2000;

    for (const observeConfig of config.tracker.impressions.observe) {
        if (!observeConfig.type && !observeConfig.itemElementTypeFn) {
            throw new Error("Impressions - invalid configuration, either 'type' or 'itemElementTypeFn' attribute has to be specified for observed block", observeConfig);
        }
        if (!observeConfig.block && !observeConfig.blockFn) {
            throw new Error("Impressions - invalid configuration, either 'block' or 'blockFn' attribute has to be specified for observed block", observeConfig);
        }

        const observerData = {
            config: observeConfig,
            seenIds: new Set(), // items confirmed as seen
            seenTimers: new Map(), // items waiting to be confirmed as seen
            seenIdTypes: new Map(),
            lastSeenIdsSize: 0,
            sentIds: new Set(),
            observedIds: new Set(),
        };
        const intersectionObserver = createIntersectionObserver(observerData);
        const mutationObserver = createMutationObserver(intersectionObserver, observerData);

        if (!mutationObserver) {
            // if mutation observer is not created (e.g. container doesn't exist), do not track
            continue;
        }

        observed.push({
            intersectionObserver,
            mutationObserver,
            ...observerData
        });
    }

    // first report after 2.5s, later after 5s
    setTimeout(async () => {
        try {
            await sendPayload(preparePayload());
        } catch (e) {
            console.log("REMP - error while preparing and sending impressions payload", e);
        }
        setInterval(() => {
            try {
                sendPayload(preparePayload());
            } catch (e) {
                console.log("REMP - error while preparing and sending impressions payload", e);
            }
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

    function createIntersectionObserver(observerData) {
        let minVisibleDurationMs = globalItemMinVisibleDurationMs;
        if (observerData.config.itemMinVisibleDuration) {
            minVisibleDurationMs = observerData.config.itemMinVisibleDuration;
        }

        const itemElementIdFn = !!observerData.config.itemElementIdFn ?
            observerData.config.itemElementIdFn :
            (el) => el.id;

        const observedType = observerData.config.type;

        return new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    const itemId = itemElementIdFn(entry.target);
                    const itemType = !!observerData.config.itemElementTypeFn ?
                        observerData.config.itemElementTypeFn(entry.target) :
                        observedType;

                    if (entry.isIntersecting) {
                        if (!observerData.seenIds.has(itemId) && !observerData.seenTimers.has(itemId)) {
                            const timerId = setTimeout(() => {
                                observerData.seenTimers.delete(itemId);
                                observerData.seenIds.add(itemId);
                                observerData.seenIdTypes.set(itemId, itemType);
                                observer.unobserve(entry.target);
                            }, minVisibleDurationMs);
                            observerData.seenTimers.set(itemId, timerId);
                        }
                    } else {
                        if (observerData.seenTimers.has(itemId)) {
                            clearTimeout(observerData.seenTimers.get(itemId));
                            observerData.seenTimers.delete(itemId);
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

    function createMutationObserver(intersectionObserver, observerData) {
        const observeNewItems = () => {
            document.querySelectorAll(observerData.config.itemsQuerySelector).forEach((post) => {
                const elId = observerData.config.itemElementIdFn(post);
                if (!observerData.observedIds.has(elId)) {
                    observerData.observedIds.add(elId);
                    intersectionObserver.observe(post);
                }
            });
        };

        observeNewItems();
        const mutationObserver = new MutationObserver(() => {
            observeNewItems();
        });
        let container = document.body;
        if (observerData.config.containerQuerySelector) {
            container = document.querySelector(observerData.config.containerQuerySelector);
        }
        if (!container) {
            // if container is not present, do not return observer
            return null;
        }

        mutationObserver.observe(container, { childList: true, subtree: true });
        return mutationObserver;
    }

    function preparePayload() {
        const payload = {
            d: [],
            rpid: remplib.getRempPageviewID(),
        };

        for (const observerData of observed) {
            if (observerData.seenIds.size !== observerData.lastSeenIdsSize) {
                observerData.lastSeenIdsSize = observerData.seenIds.size;
                const seenToReport = setDifference(observerData.seenIds, observerData.sentIds);

                // compute block
                const observedBlock = !!observerData.config.blockFn ?
                    observerData.config.blockFn(observerData.config) :
                    observerData.config.block;

                // sort eids to type buckets
                const payloadTypes = new Map();
                for (const itemId of seenToReport) {
                    const itemType = observerData.seenIdTypes.get(itemId);
                    if (!payloadTypes.has(itemType)) {
                        payloadTypes.set(itemType, []);
                    }
                    payloadTypes.get(itemType).push(itemId);
                }

                for (const [type, ids] of payloadTypes.entries()) {
                    payload.d.push({
                        bl: observedBlock,
                        tp: type,
                        eid: ids,
                    })
                }

                observerData.sentIds = new Set(observerData.seenIds);
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
        if (observed) {
            for (const o of observed) {
                o.intersectionObserver.disconnect();
                o.mutationObserver.disconnect();
            }
            // remove all elements
            while(observed.length > 0) {
                observed.pop();
            }
        }
    }

    return functionInterface;
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