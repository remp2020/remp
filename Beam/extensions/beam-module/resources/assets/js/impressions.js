import {dispatchBeamEvent} from "./remplib";
export default function(config) {
    const typeIdSeparator = "_=_=_=_"; // something that should not be present in BEAM element ID or type
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

    // plan sending impression data every 5 seconds
    setInterval(() => {
        try {
            sendPayload(preparePayload());
        } catch (e) {
            console.log("REMP - error while preparing and sending impressions payload", e);
        }
    }, 5000);

    // or send impression data using Beacon
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

    function getItemUniqueId(el, config) {
        const id = !!config.itemElementIdFn ? config.itemElementIdFn(el) : el.id;
        if (!id) {
            console.warn("Impressions - unable to return element ID", el, config);
            return null;
        }
        if (id.includes(typeIdSeparator)) {
            throw new Error(`Impression tracking cannot function properly, element ID ${id} contains type-id separator ${typeIdSeparator}. Please define custom separator`);
        }
        const type = !!config.itemElementTypeFn ? config.itemElementTypeFn(el) : config.type;
        if (!type) {
            console.warn("Impressions - unable to return element type", el, config);
            return null;
        }
        if (type.includes(typeIdSeparator)) {
            throw new Error(`Impression tracking cannot function properly, element type ${type} contains type-id separator ${typeIdSeparator}. Please define custom separator`);
        }

        // we join item ID and type here to create unique ID (ID should be unique per type)
        return type + typeIdSeparator + id;
    }

    function retrieveItemTypeAndId(uniqueId) {
        return uniqueId.split(typeIdSeparator);
    }

    function createIntersectionObserver(observerData) {
        let minVisibleDurationMs = globalItemMinVisibleDurationMs;
        if (observerData.config.itemMinVisibleDuration) {
            minVisibleDurationMs = observerData.config.itemMinVisibleDuration;
        }

        return new IntersectionObserver(
            (entries, intersectionObserver) => {
                entries.forEach((entry) => {
                    const itemUniqueId = getItemUniqueId(entry.target, observerData.config);
                    if (!itemUniqueId) {
                        return;
                    }

                    if (entry.isIntersecting) {
                        if (!observerData.seenIds.has(itemUniqueId) && !observerData.seenTimers.has(itemUniqueId)) {
                            const timerId = setTimeout(() => {
                                observerData.seenTimers.delete(itemUniqueId);
                                observerData.seenIds.add(itemUniqueId);
                                intersectionObserver.unobserve(entry.target);
                            }, minVisibleDurationMs);
                            observerData.seenTimers.set(itemUniqueId, timerId);
                        }
                    } else {
                        if (observerData.seenTimers.has(itemUniqueId)) {
                            clearTimeout(observerData.seenTimers.get(itemUniqueId));
                            observerData.seenTimers.delete(itemUniqueId);
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
                const itemUniqueId = getItemUniqueId(post, observerData.config)

                if (!observerData.observedIds.has(itemUniqueId)) {
                    observerData.observedIds.add(itemUniqueId);
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
                const seenIdsToReport = setDifference(observerData.seenIds, observerData.sentIds);

                // compute block
                const observedBlock = !!observerData.config.blockFn ?
                    observerData.config.blockFn(observerData.config) :
                    observerData.config.block;

                // sort eids to type buckets
                const payloadTypes = new Map();
                for (const itemUniqueId of seenIdsToReport) {
                    const [itemType, itemId] = retrieveItemTypeAndId(itemUniqueId);
                    if (!payloadTypes.has(itemType)) {
                        payloadTypes.set(itemType, []);
                    }
                    payloadTypes.get(itemType).push(itemId);
                }

                for (const [type, itemIds] of payloadTypes.entries()) {
                    payload.d.push({
                        bl: observedBlock,
                        tp: type,
                        eid: itemIds,
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