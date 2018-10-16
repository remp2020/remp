<template>
    <div ref="modal" class="modal" v-bind:id="name" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Graph options</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <p class="c-black f-500 m-b-5">Compare graph data with:</p>
                            <input type="radio" id="average" value="average" v-model="compareWith">
                            <label for="average">Average of last 4 weeks</label> <br />
                            <input type="radio" id="last_week" value="last_week" v-model="compareWith">
                            <label for="last_week">Previous week</label><br />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" @click="save" class="btn btn-info" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
</style>

<script>
    let props = {
        name: {
            type: String,
            required: true
        }
    };

    export default {
        name: 'options-modal',
        props: props,
        data() {
            return {
                compareWith: this.$store.state.settings.compareWith
            }
        },
        methods: {
            save() {
                this.$store.commit('changeSettings', {
                    compareWith: this.compareWith
                })
            }
        }
    }
</script>