<template>
    <div>
        <div class="input-group">
            <span class="input-group-addon hidden-xs"><i class="zmdi zmdi-link"></i></span>
            <v-select
                v-model="urlFilterType"
                :options.sync="urlFilterOptions"
                placeholder="Url filter"
                title="Url filter"
                :name="'url_filter'"
            >
            </v-select>
        </div>
        <div v-if="this.urlFilterType !== 'everywhere'" class="input-group urls-input-group" style="margin-top: 20px;">
            <span class="input-group-addon hidden-xs"></span>
            <div>
                <div class="row">
                    <div class="col-xs-10 col-sm-11">
                        <url
                            v-for="(url, i) in urlPatternList"
                            :key="url.uid"
                            :index="i"
                            :url="url"
                        ></url>
                    </div>
                    <div class="col-xs-2 col-sm-1">
                        <span class="btn btn-sm palette-Cyan bg waves-effect" v-on:click.prevent="addUrl">
                            <i class="zmdi zmdi-plus-circle"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <input v-if="this.urlFilterType === 'everywhere'" type="hidden" name="url_patterns">
    </div>
</template>


<script type="text/javascript">
    import vSelect from "remp/js/components/vSelect.vue";
    import Url from "./Url";

    export default {
        components: {
            vSelect,
            Url
        },
        props: [
            "urlFilterTypes",
            "urlFilter",
            "urlPatterns"
        ],
        data() {
            return {
                urlPatternList: [],
                urlFilterType: null
            };
        },
        created: function () {
            this.urlFilterType = this.urlFilter;

            if (this.urlPatterns) {
                for (let ii = 0; ii < this.urlPatterns.length; ii++) {
                    this.urlPatternList.push({
                        uid: this.generateUid(),
                        url: this.urlPatterns[ii]
                    })
                }
            } else {
                this.addUrl();
            }
        },
        methods: {
            addUrl() {
                this.urlPatternList.push({
                    uid: this.generateUid(),
                    url: ""
                })
            },
            removeUrl(index) {
                this.urlPatternList.splice(index, 1);

                if (!this.urlPatternList.length) {
                    this.addUrl();
                }

            },
            generateUid() {
                return Math.random().toString(36).substr(2, 9);
            }
        },
        computed: {
            urlFilterOptions() {
                let options = [];

                for (let value in this.urlFilterTypes) {
                    options.push({
                        "value": value,
                        "label": this.urlFilterTypes[value]
                    })
                }

                return options;
            }
        }
    }
</script>

<style scoped>
    .input-group {
        width: 100%;
    }

    .btn {
        float: right;
    }
</style>
