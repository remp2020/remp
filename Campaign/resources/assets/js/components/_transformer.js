export default {
    transformKeys: (obj) => {
        Object.keys(obj).forEach(function(key) {
            obj["_" + key] = obj[key];
        });
        return obj;
    },
}