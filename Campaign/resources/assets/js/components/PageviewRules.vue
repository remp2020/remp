<template>
    <div>
        <div class="input-group m-t-20">
            <span class="input-group-addon pageview-rules-addon"><i class="zmdi zmdi-eye"></i></span>
            <div>
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <label class="fg-label">Display banner</label>
                    </div>

                    <div class="col-md-8 col-sm-8 col-xs-10 pageview-rules-wrapper">
                        <div class="row">

                            <div class="input-group fg-float m-t-10 radio">
                                <label class="m-l-15">
                                    Always
                                    <input v-model="displayBanner"
                                           type="radio"
                                           name="pageview_rules[display_banner]"
                                           value="always">
                                    <i class="input-helper"></i>
                                </label>
                            </div>

                            <div class="col-md-4 col-sm-4" style="margin-left: -15px;">
                                <div class="input-group fg-float m-t-10 radio">
                                    <label class="m-l-15">
                                        Every
                                        <input v-model="displayBanner"
                                               type="radio"
                                               name="pageview_rules[display_banner]"
                                               value="every">
                                        <i class="input-helper"></i>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-2 col-sm-2 p-l-0">
                                <input v-model="displayBannerEvery"
                                       type="text"
                                       name="pageview_rules[display_banner_every]"
                                       class="form-control fg-input every-input"
                                       id="num">
                            </div>

                            <div class="col-md-4 col-sm-4 p-l-0" style="height: 38px; line-height: 38px;">
                                page views
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="input-group fg-float m-t-20">
            <div class="col-md-5 col-sm-5 p-r-0" style="margin-left: -15px;">
                <div class="input-group fg-float m-t-10 checkbox">
                    <label class="m-l-15">
                        Display to user
                        <input v-model="displayTimes"
                               type="checkbox"
                               name="pageview_rules[display_times]">
                        <i class="input-helper"></i>
                    </label>
                </div>
            </div>

            <div class="col-md-2 col-sm-2 p-l-0" style="margin-left: -10px;">
                <input v-model="displayNTimes"
                       type="text"
                       name="pageview_rules[display_n_times]"
                       class="form-control fg-input every-input"
                       id="times_num">
            </div>

            <div class="col-md-4 col-sm-4 p-l-0" style="height: 38px; line-height: 38px;">
                times, then stop.
            </div>
        </div>

        <div class="input-group fg-float m-t-10 checkbox">
            <label class="m-l-15">
                Display once per session
                <input v-model="oncePerSessionVal" value="1" name="once_per_session" type="checkbox">
                <i class="input-helper"></i>
            </label>
        </div>
    </div>
</template>

<style scoped>
    .pageview-rules-wrapper {
        max-width: 340px;
    }
    .input-group .input-group-addon.pageview-rules-addon {
        vertical-align: top;
        padding-top: 10px;
    }
    .every-input {
        height: 38px;
        text-align: center;
    }
</style>

<script type="text/javascript">
    export default {
        props: [
            "pageviewRules",
            "oncePerSession"
        ],
        data() {
            return {
                displayBanner: 'always',
                displayBannerEvery: 2,
                displayTimes: false,
                displayNTimes: 2,
                oncePerSessionVal: false,
            };
        },
        created: function () {
            if (this.pageviewRules.display_banner !== undefined) {
                this.displayBanner = this.pageviewRules.display_banner;
            }
            if (this.pageviewRules.display_banner_every !== undefined) {
                this.displayBannerEvery = this.pageviewRules.display_banner_every;
            }
            if (this.pageviewRules.display_times !== undefined) {
                this.displayTimes = this.pageviewRules.display_times;
            }
            if (this.pageviewRules.display_n_times !== undefined) {
                this.displayNTimes = this.pageviewRules.display_n_times;
            }

            this.oncePerSessionVal = this.oncePerSession;
            this.updatePageviewRules();
        },
        watch: {
            displayBanner: function () {
                this.updatePageviewRules();
            },
            displayBannerEvery: function () {
                this.updatePageviewRules();
            },
            displayTimes: function () {
                this.updatePageviewRules();
            },
            displayNTimes: function () {
                this.updatePageviewRules();
            },
            oncePerSessionVal: function () {
                this.updatePageviewRules();
            },
        },
        methods: {
            updatePageviewRules: function () {
                this.$emit('pageviewRulesModified', {
                    rules: {
                        display_banner: this.displayBanner,
                        display_banner_every: this.displayBannerEvery,
                        display_times: this.displayTimes,
                        display_n_times: this.displayNTimes
                    },
                    oncePerSession: this.oncePerSessionVal
                });
            }
        }
    }
</script>
