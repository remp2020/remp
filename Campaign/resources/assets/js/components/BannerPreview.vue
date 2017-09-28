<style type="text/css">
    @import '../../css/banner.css';
</style>

<template>
    <div>
        <html-preview v-if="template == 'html'"
                :alignmentOptions="alignmentOptions"
                :dimensionOptions="dimensionOptions"
                :positionOptions="positionOptions"
                :show="previewShow"
                :uuid="uuid"
                :campaignUuid="campaignUuid"

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
                :forcedPosition="'absolute'"
        ></html-preview>

        <medium-rectangle-preview v-if="template == 'medium_rectangle'"
                :alignmentOptions="alignmentOptions"
                :positionOptions="positionOptions"
                :show="previewShow"
                :uuid="uuid"
                :campaignUuid="campaignUuid"

                :headerText="mediumRectangleTemplate.headerText"
                :mainText="mediumRectangleTemplate.mainText"
                :buttonText="mediumRectangleTemplate.buttonText"
                :backgroundColor="mediumRectangleTemplate.backgroundColor"
                :textColor="mediumRectangleTemplate.textColor"
                :buttonBackgroundColor="mediumRectangleTemplate.buttonBackgroundColor"
                :buttonTextColor="mediumRectangleTemplate.buttonTextColor"

                :position="position"
                :targetUrl="targetUrl"
                :closeable="closeable"
                :transition="transition"
                :displayType="displayType"
                :forcedPosition="'absolute'"
        ></medium-rectangle-preview>

        <bar-preview v-if="template == 'bar'"
                :alignmentOptions="alignmentOptions"
                :positionOptions="positionOptions"
                :show="previewShow"
                :uuid="uuid"
                :campaignUuid="campaignUuid"

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
                :forcedPosition="'absolute'"
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
        mounted: function(){
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });
        },
        data: () => ({
            previewShow: true,
        }),
        watch: {
            'transition': function () {
                let vm = this;
                setTimeout(function() { vm.previewShow = false }, 100);
                setTimeout(function() { vm.previewShow = true }, 800);
            }
        },
    }
</script>