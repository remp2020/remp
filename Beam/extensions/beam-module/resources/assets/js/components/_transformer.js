export default {
    transformKeys: (obj) => {
        Object.keys(obj).forEach(function(key) {
            obj["_" + key] = obj[key] === null ? undefined : obj[key];
        });
        return obj;
    },
}
