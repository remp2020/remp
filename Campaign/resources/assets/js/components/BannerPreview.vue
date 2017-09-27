<template>
    <div>
        <html-preview v-if="template == 'html'"
                v-bind:alignmentOptions="alignmentOptions"
                v-bind:dimensionOptions="dimensionOptions"
                v-bind:positionOptions="positionOptions"
                v-bind:show="previewShow"

                v-bind:textAlign="htmlTemplate.textAlign"
                v-bind:dimensions="htmlTemplate.dimensions"
                v-bind:textColor="htmlTemplate.textColor"
                v-bind:fontSize="htmlTemplate.fontSize"
                v-bind:backgroundColor="htmlTemplate.backgroundColor"
                v-bind:text="htmlTemplate.text"

                v-bind:position="position"
                v-bind:targetUrl="targetUrl"
                v-bind:closeable="closeable"
                v-bind:transition="transition"
                v-bind:displayType="displayType"
                v-bind:forcedPosition="'absolute'"
        ></html-preview>

        <medium-rectangle-preview v-if="template == 'medium_rectangle'"
                v-bind:alignmentOptions="alignmentOptions"
                v-bind:positionOptions="positionOptions"
                v-bind:show="previewShow"

                v-bind:headerText="mediumRectangleTemplate.headerText"
                v-bind:mainText="mediumRectangleTemplate.mainText"
                v-bind:buttonText="mediumRectangleTemplate.buttonText"
                v-bind:width="mediumRectangleTemplate.width"
                v-bind:height="mediumRectangleTemplate.height"
                v-bind:backgroundColor="mediumRectangleTemplate.backgroundColor"
                v-bind:textColor="mediumRectangleTemplate.textColor"
                v-bind:buttonBackgroundColor="mediumRectangleTemplate.buttonBackgroundColor"
                v-bind:buttonTextColor="mediumRectangleTemplate.buttonTextColor"

                v-bind:position="position"
                v-bind:targetUrl="targetUrl"
                v-bind:closeable="closeable"
                v-bind:transition="transition"
                v-bind:displayType="displayType"
                v-bind:forcedPosition="'absolute'"
        ></medium-rectangle-preview>

        <bar-preview v-if="template == 'bar'"
                v-bind:alignmentOptions="alignmentOptions"
                v-bind:positionOptions="positionOptions"
                v-bind:show="previewShow"

                v-bind:mainText="barTemplate.mainText"
                v-bind:buttonText="barTemplate.buttonText"
                v-bind:backgroundColor="barTemplate.backgroundColor"
                v-bind:textColor="barTemplate.textColor"
                v-bind:buttonBackgroundColor="barTemplate.buttonBackgroundColor"
                v-bind:buttonTextColor="barTemplate.buttonTextColor"

                v-bind:position="position"
                v-bind:targetUrl="targetUrl"
                v-bind:closeable="closeable"
                v-bind:transition="transition"
                v-bind:displayType="displayType"
                v-bind:forcedPosition="'absolute'"
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