<template>
    <div>
        <h4 class="m-t-25">Dashboard Configuration</h4>
        <a href="#chart-options" class="btn btn-sm btn-info waves-effect" data-toggle="modal">
            <i class="zmdi zmdi-settings"></i> More options
        </a>
        <options-modal name="chart-options"></options-modal>
    </div>
</template>

<style scoped>
</style>

<script>
    import OptionsModal from './OptionsModal.vue'

    let props = {
    };

    export default {
        components: { OptionsModal },
        name: 'options',
        props: props,
        data() {
            return {
            }
        },
        computed: {
            settings() {
                return this.$store.state.settings
            }
        }
    }
</script>