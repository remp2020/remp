<template>
    <div style="display: inline-block" :class="classes">
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

    let props = {
        classes: {
            type: Array,
            default: []
        }
    };

    export default {
        components: { OptionsModal },
        props: props,
        name: 'options',
        data() {
            return {
                showModal: false,
            }
        }
    }
</script>