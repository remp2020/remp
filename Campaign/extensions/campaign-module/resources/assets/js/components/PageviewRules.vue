<template>
    <div>
        <div class="input-group m-t-20">
            <span class="input-group-addon pageview-rules-addon"><i class="zmdi zmdi-eye"></i></span>
            <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <label class="fg-label">Display banner</label>
                        <button type="button" v-if="prioritizeBannersSamePosition" v-on:click="displayBannerInfoButtonClicked" class="btn btn-link waves-effect" style="color: black; padding: 5px; font-size: 16px"><i class="zmdi zmdi-info-outline"></i></button>
                    </div>

                    <div class="col-xs-12 pageview-rules-wrapper">
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

                            <div class="col-sm-12" style="margin-left: -15px;">
                                <div class="input-group fg-float radio flex-input-group">
                                    <label class="m-l-15 m-10">
                                        Every
                                        <input v-model="displayBanner"
                                               type="radio"
                                               name="pageview_rules[display_banner]"
                                               value="every">
                                        <i class="input-helper"></i>
                                    </label>
                                    <input v-model="displayBannerEvery"
                                           type="text"
                                           name="pageview_rules[display_banner_every]"
                                           class="form-control fg-input every-input inline-flex-input"
                                           id="num">
                                    <span style="margin: 10px"> page views</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>

        <div class="col-sm-12" style="margin-left: -15px;">
            <div class="input-group fg-float m-t-10 checkbox" style="display: flex;">
                <label class="m-l-15" style="margin: 10px">
                    Display to user
                    <input v-model="displayTimes"
                           type="checkbox"
                           name="pageview_rules[display_times]">
                    <i class="input-helper"></i>
                </label>
                <input v-model="displayNTimes"
                       type="text"
                       name="pageview_rules[display_n_times]"
                       class="form-control fg-input every-input inline-flex-input"
                       id="times_num">
                <span style="margin: 10px">times, then stop.</span>
            </div>
        </div>

        <div class="input-group fg-float m-t-10 checkbox">
            <label class="m-l-15">
                Display once per session
                <input v-model="oncePerSessionVal" value="1" name="once_per_session" type="checkbox">
                <i class="input-helper"></i>
            </label>
        </div>

        <div class="input-group m-t-20">
            <span class="input-group-addon pageview-rules-addon"><i class="zmdi zmdi-close-circle"></i></span>
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <label class="fg-label">After banner is CLOSED by user:</label>
                </div>

                <div class="col-md-8 col-sm-8 col-xs-10">
                    <div class="row">

                        <div class="col-sm-12" style="margin-left: -15px;">
                            <div class="input-group fg-float m-t-10 radio input-inline-block">
                                <label class="m-l-15">
                                    Always show
                                    <input v-model="afterBannerClosedDisplay"
                                           type="radio"
                                           name="pageview_rules[after_banner_closed_display]"
                                           value="always">
                                    <i class="input-helper"></i>
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-12" style="margin-left: -15px;">
                            <div class="input-group fg-float m-t-10 radio input-inline-block">
                                <label class="m-l-15">
                                    Never show it again
                                    <input v-model="afterBannerClosedDisplay"
                                           type="radio"
                                           name="pageview_rules[after_banner_closed_display]"
                                           value="never">
                                    <i class="input-helper"></i>
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-12" style="margin-left: -15px;">
                            <div class="input-group fg-float m-t-10 radio input-inline-block">
                                <label class="m-l-15">
                                    Don't show within current session
                                    <input v-model="afterBannerClosedDisplay"
                                           type="radio"
                                           name="pageview_rules[after_banner_closed_display]"
                                           value="never_in_session">
                                    <i class="input-helper"></i>
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-12" style="margin-left: -15px;">
                            <div class="input-group fg-float radio flex-input-group">
                                <label class="m-l-15" style="margin: 10px">
                                    Don't show for the next
                                    <input v-model="afterBannerClosedDisplay"
                                           type="radio"
                                           name="pageview_rules[after_banner_closed_display]"
                                           value="close_for_hours">
                                    <i class="input-helper"></i>
                                </label>
                                <input v-model="afterClosedHours"
                                       type="text"
                                       name="pageview_rules[after_closed_hours]"
                                       class="form-control fg-input every-input inline-flex-input"
                                       id="num">
                                <span style="margin: 10px">hours</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="input-group m-t-20">
          <span class="input-group-addon pageview-rules-addon"><i class="zmdi zmdi-mouse"></i></span>
          <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <label class="fg-label">After banner is CLICKED by user:</label>
            </div>

            <div class="col-md-8 col-sm-8 col-xs-10">
                <div class="row">

                    <div class="col-sm-12" style="margin-left: -15px;">
                        <div class="input-group fg-float m-t-10 radio input-inline-block">
                            <label class="m-l-15">
                                Always show
                                <input v-model="afterBannerClickedDisplay"
                                       type="radio"
                                       name="pageview_rules[after_banner_clicked_display]"
                                       value="always">
                                <i class="input-helper"></i>
                            </label>
                        </div>
                    </div>

                    <div class="col-sm-12" style="margin-left: -15px;">
                        <div class="input-group fg-float m-t-10 radio input-inline-block">
                            <label class="m-l-15">
                                Never show it again
                                <input v-model="afterBannerClickedDisplay"
                                       type="radio"
                                       name="pageview_rules[after_banner_clicked_display]"
                                       value="never">
                                <i class="input-helper"></i>
                            </label>
                        </div>
                    </div>

                    <div class="col-sm-12" style="margin-left: -15px;">
                        <div class="input-group fg-float m-t-10 radio input-inline-block">
                            <label class="m-l-15">
                                Don't show within current session
                                <input v-model="afterBannerClickedDisplay"
                                       type="radio"
                                       name="pageview_rules[after_banner_clicked_display]"
                                       value="never_in_session">
                                <i class="input-helper"></i>
                            </label>
                        </div>
                    </div>

                    <div class="col-sm-12" style="margin-left: -15px;">
                        <div class="input-group fg-float radio flex-input-group">
                            <label class="m-l-15 m-10">
                                Don't show for the next
                                <input v-model="afterBannerClickedDisplay"
                                       type="radio"
                                       name="pageview_rules[after_banner_clicked_display]"
                                       value="close_for_hours">
                                <i class="input-helper"></i>
                            </label>
                            <input v-model="afterClickedHours"
                                   type="text"
                                   name="pageview_rules[after_clicked_hours]"
                                   class="form-control fg-input every-input inline-flex-input"
                                   id="num">
                            <span style="margin: 10px">hours</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
    .inline-flex-input {
        opacity: 1;
        max-width: 50px;
        cursor:text
    }
    .flex-input-group {
        display: flex;
        margin-bottom: 0;
        margin-top: 0;
        align-items: center;
    }
    .input-inline-block {
        display: inline-block;
    }
</style>

<script type="text/javascript">
    export default {
        props: [
            "pageviewRules",
            "oncePerSession",
            "prioritizeBannersSamePosition"
        ],
        data() {
            return {
                displayBanner: 'always',
                displayBannerEvery: 2,
                displayTimes: false,
                displayNTimes: 2,
                oncePerSessionVal: false,
                afterBannerClosedDisplay: 'always',
                afterClosedHours: 2,
                afterBannerClickedDisplay: 'always',
                afterClickedHours: 2,
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
            if (this.pageviewRules.after_banner_closed_display !== undefined) {
                this.afterBannerClosedDisplay = this.pageviewRules.after_banner_closed_display;
            }
            if (this.pageviewRules.after_closed_hours !== undefined) {
                this.afterClosedHours = this.pageviewRules.after_closed_hours;
            }
            if (this.pageviewRules.after_banner_clicked_display !== undefined) {
                this.afterBannerClickedDisplay = this.pageviewRules.after_banner_clicked_display;
            }
            if (this.pageviewRules.after_clicked_hours !== undefined) {
                this.afterClickedHours = this.pageviewRules.after_clicked_hours;
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
            afterBannerClosedDisplay: function () {
                this.updatePageviewRules();
            },
            afterClosedHours: function () {
                this.updatePageviewRules();
            },
            afterBannerClickedDisplay: function () {
                this.updatePageviewRules();
            },
            afterClickedHours: function () {
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
                        display_n_times: this.displayNTimes,
                        after_banner_closed_display: this.afterBannerClosedDisplay,
                        after_closed_hours: this.afterClosedHours,
                        after_banner_clicked_display: this.afterBannerClickedDisplay,
                        after_clicked_hours: this.afterClickedHours
                    },
                    oncePerSession: this.oncePerSessionVal
                });
            },
            displayBannerInfoButtonClicked: function () {
                swal({
                    'html': true,
                    'title': '<i class="zmdi zmdi-format-list-bulleted"></i> Display banner rules',
                    'text': 'Banners placed on the same position are prioritized by following rules:<br><br>'
                            + '<ol>'
                            + '<li>Campaign with more banners has higher priority.</li>'
                            + '<li>Campaign with more recent updates has higher priority.</li>'
                            + '</ol>'
                            + 'Due to the rules above there may occur a configuration that banner <strong>should be displayed</strong> (e.g. every n-th pageview) '
                            + 'but another banner from different campaign has higher priority and therefore <strong>the one with higher priority will be displayed</strong>.<br>'
                            + 'In this case banner with <strong>lower priority</strong> will be displayed <strong>at the earliest possible pageview</strong> which may create mismatch '
                            + 'between configuration and the display itself.'
                });
            }
        },
    }
</script>
