import Vuex from 'vuex'
import rison from 'rison'
import createPersistedState from 'vuex-persistedstate'

export default new Vuex.Store({
    state: {
        settings: {
            compareWith: 'average',
            newGraph: true,
            onlyTrafficFromFrontPage: false
        }
    },
    mutations: {
        changeSettings(state, newSettings) {
            state.settings = Object.assign({}, state.settings, newSettings)
        }
    },
    // Store state in URL query string (assuming dashboard configuration state is kept short)
    plugins: [createPersistedState({
        getState: function(key, storage, value) {
            let url = new URL(window.location.href);
            try {
                return rison.decode(url.searchParams.get('_'));
            } catch (err) {}
            return undefined
        },
        setState: function(key, state, storage) {
            let url = new URL(window.location.href);
            url.searchParams.set('_', rison.encode(state));
            window.history.pushState({}, document.title, decodeURIComponent(url.toString()));
        }
    })],
})
