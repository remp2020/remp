<style type="text/css">
    @import '../../css/banner.css';
</style>

<template>
    <div class="remp-banner">
        <html-preview v-if="template == 'html'"
                :alignmentOptions="alignmentOptions"
                :dimensionOptions="dimensionOptions"
                :positionOptions="positionOptions"
                :show="visible"
                :uuid="uuid"
                :campaignUuid="campaignUuid"
                :forcedPosition="forcedPosition"

                :textAlign="htmlTemplate.textAlign"
                :dimensions="htmlTemplate.dimensions"
                :textColor="htmlTemplate.textColor"
                :fontSize="htmlTemplate.fontSize"
                :backgroundColor="htmlTemplate.backgroundColor"
                :text="htmlTemplate.text"

                :position="position"
                :targetUrl="targetUrl"
                :closeable="closeable"
                :transition="transition"
                :displayType="displayType"
        ></html-preview>

        <medium-rectangle-preview v-if="template == 'medium_rectangle'"
                :alignmentOptions="alignmentOptions"
                :positionOptions="positionOptions"
                :show="visible"
                :uuid="uuid"
                :campaignUuid="campaignUuid"
                :forcedPosition="forcedPosition"

                :headerText="mediumRectangleTemplate.headerText"
                :mainText="mediumRectangleTemplate.mainText"
                :buttonText="mediumRectangleTemplate.buttonText"
                :width="mediumRectangleTemplate.width"
                :height="mediumRectangleTemplate.height"
                :backgroundColor="mediumRectangleTemplate.backgroundColor"
                :textColor="mediumRectangleTemplate.textColor"
                :buttonBackgroundColor="mediumRectangleTemplate.buttonBackgroundColor"
                :buttonTextColor="mediumRectangleTemplate.buttonTextColor"

                :position="position"
                :targetUrl="targetUrl"
                :closeable="closeable"
                :transition="transition"
                :displayType="displayType"
        ></medium-rectangle-preview>

        <bar-preview v-if="template == 'bar'"
                :alignmentOptions="alignmentOptions"
                :positionOptions="positionOptions"
                :show="visible"
                :uuid="uuid"
                :campaignUuid="campaignUuid"
                :forcedPosition="forcedPosition"

                :mainText="barTemplate.mainText"
                :buttonText="barTemplate.buttonText"
                :backgroundColor="barTemplate.backgroundColor"
                :textColor="barTemplate.textColor"
                :buttonBackgroundColor="barTemplate.buttonBackgroundColor"
                :buttonTextColor="barTemplate.buttonTextColor"

                :position="position"
                :targetUrl="targetUrl"
                :closeable="closeable"
                :transition="transition"
                :displayType="displayType"
        ></bar-preview>
    </div>
</template>


<script>
    import HtmlPreview from "./previews/Html.vue";
    import MediumRectanglePreview from "./previews/MediumRectangle.vue";
    import BarPreview from "./previews/Bar.vue";

    const props = [
        "name",
        "targetUrl",
        "position",
        "transition",
        "closeable",
        "displayDelay",
        "closeTimeout",
        "targetSelector",
        "displayType",
        "template",
        "uuid",
        "campaignUuid",
        "forcedPosition",
        "show",

        "variables",

        "mediumRectangleTemplate",
        "barTemplate",
        "htmlTemplate",

        "alignmentOptions",
        "dimensionOptions",
        "positionOptions",
    ];

    export default {
        components: {
            HtmlPreview,
            MediumRectanglePreview,
            BarPreview,
        },
        name: 'banner-preview',
        props: props,
        created: function() {
            this.$on('values-changed', function(data) {
                for (let item of data) {
                    this[item.key] = item.val;
                }
            });
        },
        mounted: function() {
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });
        },
        data: () => ({
            visible: true,
        }),
        watch: {
            'transition': function() {
                let vm = this;
                setTimeout(function() { vm.visible = false }, 100);
                setTimeout(function() { vm.visible = true }, 800);
            },
            'show': function() {
            	this.visible = this.show;
            }
        },
        computed: {
            url: function() {
                if (this.targetUrl === null) {
                    return null;
                }
                let separator = this.targetUrl.indexOf("?") === -1 ? "?" : "&";
                let url =  this.targetUrl + separator + "utm_source=remp_campaign" +
                    "&utm_medium=" + encodeURIComponent(this.displayType);
                if (this.campaignUuid) {
                    url += "&utm_campaign=" + encodeURIComponent(this.campaignUuid);
                }
                if (this.uuid) {
                    url += "&utm_content=" + encodeURIComponent(this.uuid);
                }
                return url;
            },
        },
        methods: {
            injectVars: function(str) {
                if (!remplib || !remplib.campaign) {
                    return str;
                }
                let re = /\{\{\s?(.*?)\s?\}\}/g;
                let match;

                while (match = re.exec(str)) {
                    let replRegex = new RegExp(match[0], "g");
                    let replVal = '';
                    if (remplib.campaign.variables && remplib.campaign.variables.hasOwnProperty(match[1])) {
                        replVal = remplib.campaign.variables[match[1]].value()
                    } else {
                        throw EvalError("cannot render banner, variable [" + match[1] + "] is missing");
                    }
                    str = str.replace(replRegex, replVal);
                }
                return str;
            },
            closed: function() {
				this.visible = false;
				this.$parent.$emit('values-changed', [
                    {key: "show", val: false}
                ]);
                if (this.closeTracked) {
                    return true;
                }
                this.trackEvent("banner", "close", {
                    "utm_source": "remp_campaign",
                    "utm_medium": this.displayType,
                    "utm_campaign": this.campaignUuid,
                    "utm_content": this.uuid
                });
                this.closeTracked = true;
            },
            clicked: function() {
                if (this.clickTracked) {
                    return true;
                }
                this.trackEvent("banner", "click", {
                    "utm_source": "remp_campaign",
                    "utm_medium": this.displayType,
                    "utm_campaign": this.campaignUuid,
                    "utm_content": this.uuid
                });
                this.clickTracked = true;
                return true;
            },
            trackEvent: function(category, action, fields) {
                if (typeof remplib.tracker === 'undefined') {
                    return;
                }
                remplib.tracker.trackEvent(category, action, fields);
            },
        }
    }
</script>