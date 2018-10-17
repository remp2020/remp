<template>
    <div class="modal-mask">
        <div class="modal-wrapper">

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
                this.$emit('close')
            },
            close() {
                this.$emit('close')
            }
        }
    }
</script>