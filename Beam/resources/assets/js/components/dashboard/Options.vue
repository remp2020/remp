<template>
    <div>
        <h4 class="m-t-25">Dashboard Configuration</h4>
        <button class="btn btn-sm btn-info waves-effect" @click="showModal=true">
            <i class="zmdi zmdi-settings"></i> More options
        </button>

        <div v-if="showModal">
            <transition name="modal">
                <options-modal v-on:close="showModal=false"></options-modal>
            </transition>
        </div>

    </div>
</template>

<style scoped>
</style>

<script>
    import OptionsModal from './OptionsModal.vue'

    export default {
        components: { OptionsModal },
        name: 'options',
        data() {
            return {
                showModal: false,
            }
        },
        computed: {
            settings() {
                return this.$store.state.settings
            }
        },
    }
</script>