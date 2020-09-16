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
                                            Only traffic from front-page<br/>
                                            <small v-if="frontPageReferer">({{frontPageReferer}})</small>
                                        </template>
                                        <template v-else>
                                            <strike title="Front-page URL is not specified in the configuration.">Only traffic from front-page</strike>
                                        </template>
                                    </label>
                                </div>

                                <div class="checkbox m-b-15">
                                    <label>
                                        <input v-model="newGraph"
                                               type="checkbox">
                                        <i class="input-helper"></i>
                                        <template>
                                            Snapshots graph
                                        </template>
                                    </label>
                                </div>

                                <br />

                                <button v-if="!this.publicAccess" class="btn btn-sm btn-info waves-effect" @click="navigateToSettings">
                                    Configuration
                                </button>
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
    const publicAccess = window.location.href.indexOf('/public') !== -1;

    const optionsModalModule = {
        name: 'options-modal',
        inject: ['dashboardOptions'],
        mounted() {
            let referer = this.dashboardOptions['dashboard_frontpage_referer'];
            let propertyReferers = this.dashboardOptions['dashboard_frontpage_referer_of_properties']

            if (referer) {
                this.enableFrontpageFiltering = true
                this.frontPageReferer = this.dashboardOptions['dashboard_frontpage_referer']
            } else if (propertyReferers && propertyReferers.length > 0) {
                this.enableFrontpageFiltering = true
                this.frontPageReferer = propertyReferers.join(', ')
            }
        },
        data() {
            return {
                newGraph: this.$store.state.settings.newGraph,
                compareWith: this.$store.state.settings.compareWith,
                onlyTrafficFromFrontPage: this.$store.state.settings.onlyTrafficFromFrontPage,
                enableFrontpageFiltering: false,
                frontPageReferer: null,
                publicAccess: publicAccess
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
            },
            navigateToSettings() {
                window.location.href = route('settings.index') + '#dashboard';
            }
        }
    };

    export default optionsModalModule;
</script>
