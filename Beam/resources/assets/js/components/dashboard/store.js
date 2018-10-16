import Vuex from 'vuex'
import createPersistedState from 'vuex-persistedstate'

export default new Vuex.Store({
    state: {
        settings: {
            compareWith: 'average'
        }
    },
    mutations: {
        changeSettings(state, newSettings) {
            state.settings = Object.assign({}, state.settings, newSettings)
        }
    },
    plugins: [createPersistedState()],
})