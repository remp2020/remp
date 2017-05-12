<style type="text/css">
    .preview-box {
        color: white;
        white-space: pre-line;
        display: table;
        overflow: hidden;
    }
    .preview-text {
        display: table-cell;
        word-break:break-all;
        vertical-align: middle;
        padding: 5px 10px;
    }
</style>

<template id="banner-preview-template">
    <a v-bind:href="targetUrl" v-if="show">
        <transition appear v-bind:name="transition">
            <div class="preview-box" v-bind:style="[
                    positionOptions[position].style,
                    dimensionOptions[dimensions],
                    boxStyles
                ]">
                <p class="preview-text" v-bind:style="[
                    alignmentOptions[textAlign].style,
                    textStyles
                ]">@{{ text }}</p>
            </div>
        </transition>
    </a>
</template>