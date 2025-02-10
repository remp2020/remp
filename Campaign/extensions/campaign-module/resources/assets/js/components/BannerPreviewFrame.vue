<style>
    #bannerPreviewFrameWrap {
        background: white;
    }

    #iframe-preview {
        width: 100%;
        height: 100%;
        border: 0;
    }
</style>

<template>
    <div style="height: 100%" ref="bannerPreviewFrameRef" id="bannerPreviewFrameWrap"></div>
</template>

<script>
import Vue from "vue";
import BannerPreview from "./BannerPreview.vue";
import {registerStripHtmlFilter} from "../vueFilters";

export default {
    props: BannerPreview.props,
    mounted: function() {
        let iframe = document.createElement("iframe");
        iframe.setAttribute("id", "iframe-preview");
        this.$refs.bannerPreviewFrameRef.appendChild(iframe)

        // create iframe body
        // preview does not display in Firefox only blinks and disappears without next lines of code
        iframe.contentWindow.document.open();

        let html = iframe.contentWindow.document.createElement("html");
        let head = iframe.contentWindow.document.createElement("head");
        let body = iframe.contentWindow.document.createElement("body");

        // Added div because VUE doesn't support mounting app on body or html tags
        body.appendChild(iframe.contentWindow.document.createElement("div"));
        html.appendChild(head);
        html.appendChild(body);

        iframe.contentWindow.document.appendChild(html);

        iframe.contentWindow.document.close();

        registerStripHtmlFilter(Vue);
        new Vue({
            el: iframe.contentWindow.document.body.firstChild,
            render: h => h(BannerPreview, {
                props: this.$props,
            }),
        });

        // copy styles to iframe
        let styles = document.querySelectorAll("head style, head link[rel='stylesheet']")
        styles.forEach(li => {
            iframe.contentWindow.document.head.append(li.cloneNode(true))
        });
    },
};
</script>
