import Vuex from 'vuex'
import rison from 'rison'
import createPersistedState from 'vuex-persistedstate'

export default new Vuex.Store({
    state: {
        settings: {
            compareWith: 'average',
            newGraph: false,
            onlyTrafficFromFrontPage: false
        }
    },
    mutations: {
        changeSettings(state, newSettings) {
            state.settings = Object.assign({}, state.settings, newSettings)
        }
    },
    // Store state in URL fragment (assuming dashboard configuration state is kept short)
    plugins: [createPersistedState({
        getState: function(key, storage, value) {
            try {
                if (window.location.hash) {
                    return rison.decode(window.location.hash.substring(1))
                } else {
                    return undefined
                }
            } catch (err) {}
            return undefined
        },
        setState: function(key, state, storage) {
            window.location.hash = rison.encode(state)
        }
    })],
})