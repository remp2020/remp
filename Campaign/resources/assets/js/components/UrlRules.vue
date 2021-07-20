<template>
    <div>
        <div class="input-group">
            <span class="input-group-addon hidden-xs"><i class="zmdi zmdi-link"></i></span>
            <label :for="id" class="fg-label">{{ label }}</label>
            <v-select
                v-model="urlFilterType"
                :options.sync="urlFilterOptions"
                placeholder="Url filter"
                :title="title"
                :name="filterName"
                :id="id"
            >
            </v-select>
        </div>
        <div v-if="this.urlFilterType !== 'everywhere'" class="input-group urls-input-group" style="margin-top: 20px;">
            <span class="input-group-addon hidden-xs"></span>
            <div>
                <div class="row">
                    <div class="col-sx-12">
                        <small class="help-block" style="padding-left: 15px;">
                            {{ hint }}
                        </small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-10 col-sm-11">
                        <url
                            v-for="(url, i) in urlPatternList"
                            :key="url.uid"
                            :index="i"
                            :url="url"
                            :patternsName="patternsName"
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

        <input v-if="this.urlFilterType === 'everywhere'" type="hidden" :name="patternsName">
    </div>
</template>


<script type="text/javascript">
    import vSelect from "@remp/js-commons/js/components/vSelect.vue";
    import Url from "./Url";

    export default {
        components: {
            vSelect,
            Url
        },
        props: [
            "label",
            "hint",
            "title",
            "id",
            "filterName",
            "patternsName",
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

            if (this.urlPatterns && this.urlPatterns.length > 0) {
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
        },
        watch: {
            urlFilterType: function() {
                this.$parent.urlFilter = this.urlFilterType;
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
