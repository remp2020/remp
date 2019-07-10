<template>
    <div class="modal-mask">
        <div class="modal-wrapper">

            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Dashboard options</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <p class="c-black f-500">Compare graph data with:</p>

                                <div class="radio m-b-15">
                                    <label>
                                        <input value="average" type="radio" v-model="compareWith">
                                        <i class="input-helper"></i>
                                        Average of last 4 weeks
                                    </label>
                                </div>

                                <div class="radio m-b-15">
                                    <label>
                                        <input value="last_week" type="radio" v-model="compareWith">
                                        <i class="input-helper"></i>
                                        Previous week
                                    </label>
                                </div>

                                <br />

                                <div class="checkbox m-b-15">
                                    <label>
                                        <input
                                                :disabled="!enableFrontpageFiltering"
                                                v-model="onlyTrafficFromFrontPage"
                                                type="checkbox">
                                        <i class="input-helper"></i>
                                        <template v-if="enableFrontpageFiltering">
                                            Only traffic from front-page
                                        </template>
                                        <template v-else>
                                            <strike title="Front-page URL is not specified in the configuration, please add it to Beam environmental variables.">Only traffic from front-page</strike>
                                        </template>
                                    </label>
                                </div>

                                <div class="checkbox m-b-15">
                                    <label>
                                        <input v-model="newGraph"
                                               type="checkbox">
                                        <i class="input-helper"></i>
                                        <template>
                                            Point graph [EXPERIMENTAL]
                                        </template>
                                    </label>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" @click="close" class="btn btn-default">Close</button>
                        <button type="button" @click="save" class="btn btn-info">OK</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
    .modal-mask {
        position: fixed;
        z-index: 9998;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, .5);
        display: table;
        transition: opacity .3s ease;
    }

    .modal-wrapper {
        display: table-cell;
        vertical-align: middle;
    }
</style>

<script>
    export default {
        name: 'options-modal',
        inject: ['enableFrontpageFiltering'],
        data() {
            return {
                newGraph: this.$store.state.settings.newGraph,
                compareWith: this.$store.state.settings.compareWith,
                onlyTrafficFromFrontPage: this.$store.state.settings.onlyTrafficFromFrontPage,
            }
        },
        methods: {
            save() {
                this.$store.commit('changeSettings', {
                    compareWith: this.compareWith,
                    newGraph: this.newGraph,
                    onlyTrafficFromFrontPage: this.onlyTrafficFromFrontPage
                })
                this.$emit('close')
            },
            close() {
                this.$emit('close')
            }
        }
    }
</script>