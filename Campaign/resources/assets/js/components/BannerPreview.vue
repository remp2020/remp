<style type="text/css">
    @import url('https://fonts.googleapis.com/css?family=Noto+Sans');

    /* transitions */

    #preview-close.hidden {
        display: none;
    }

    .fade-enter-active, .fade-leave-active {
        transition: opacity .5s
    }
    .fade-enter, .fade-leave-to /* .fade-leave-active in <2.1.8 */ {
        opacity: 0
    }

    .bounce-enter-active {
        animation: bounce linear 0.5s;
        animation-iteration-count: 1;
        transform-origin: 50% 50%;
    }
    @keyframes bounce{
        0% { transform: translate(0px,0px) }
        15% { transform: translate(0px,-25px) }
        30% { transform: translate(0px,0px) }
        45% { transform: translate(0px,-15px) }
        60% { transform: translate(0px,0px) }
        75% {  transform: translate(0px,-5px) }
        100% { transform: translate(0px,0px)  }
    }

    .shake-enter-active{
        animation: shake linear 0.5s;
        animation-iteration-count: 1;
        transform-origin: 50% 50%;
    }
    @keyframes shake{
        0% { transform: translate(0px,0px) }
        10% { transform: translate(-10px,0px) }
        20% { transform: translate(10px,0px) }
        30% { transform: translate(-10px,0px) }
        40% { transform: translate(10px,0px) }
        50% { transform: translate(-10px,0px) }
        60% { transform: translate(10px,0px) }
        70% { transform: translate(-10px,0px) }
        80% { transform: translate(10px,0px) }
        90% { transform: translate(-10px,0px) }
        100% { transform: translate(0px,0px) }
    }

    .fade-in-down-enter-active {
        animation: fadeInDown ease 0.5s;
        animation-iteration-count: 1;
        transform-origin: 50% 50%;
        animation-fill-mode:forwards; /*when the spec is finished*/
    }

    @keyframes fadeInDown{
        0% { opacity: 0;  transform: translate(0px,-25px) }
        100% { opacity: 1; transform: translate(0px,0px) }
    }

    .preview-image {
        opacity: 0.3;
    }
    .preview-box {
        position: absolute;
    }
</style>

<template>
    <a v-bind:href="targetUrl" v-if="show" v-bind:style="[
        linkStyles,
        _position,
        dimensionOptions[dimensions]
    ]">
        <transition appear v-bind:name="transition">
            <div class="preview-box" v-bind:style="[
                boxStyles,
                dimensionOptions[dimensions],
                customBoxStyles
            ]">
                <a class="preview-close" href="javascript://" v-bind:class="[{hidden: !closeable}]" v-on:click="show = false" v-bind:style="closeStyles">&#x1f5d9;</a>
                <p v-html="text" class="preview-text" v-bind:style="[
            _textAlign,
            textStyles
        ]"></p>
            </div>
        </transition>
    </a>
</template>

<script>
    export default {
        name: 'banner-preview',
        props: [
            "positionOptions",
            "dimensionOptions",
            "alignmentOptions",
            "textAlign",
            "transition",
            "position",
            "dimensions",
            "show",
            "textColor",
            "fontSize",
            "backgroundColor",
            "targetUrl",
            "closeable",
            "text",
        ],
        computed: {
            _textAlign: function() {
                  return this.alignmentOptions[this.textAlign] ? this.alignmentOptions[this.textAlign].style : {};
            },
            _position: function() {
                return this.positionOptions[this.position] ? this.positionOptions[this.position].style : {};
            },
            linkStyles: function() {
                return {
                    textDecoration: 'none',
                    position: 'absolute',
                    overflow: 'hidden',
                    zIndex: 0,
                }},
            textStyles: function() {
                return {
                    color: this.textColor,
                    fontSize: this.fontSize + "px",
                    display: 'table-cell',
                    wordBreak: 'break-all',
                    verticalAlign: 'middle',
                    padding: '5px 10px'
                }
            },
            boxStyles: function() {
                return {
                    backgroundColor: this.backgroundColor,
                    fontFamily: 'Noto Sans, sans-serif',
                    color: 'white',
                    whiteSpace: 'pre-line',
                    display: 'table',
                    overflow: 'hidden',
                    position: 'relative'
                }},
            closeStyles: function() {
                return {
                    color: this.textColor,
                    position: 'absolute',
                    top: '5px',
                    right: '10px',
                    fontSize: '15px',
                    padding: '5px',
                    textDecoration: 'none',
                }},
            customBoxStyles: function() {
                return {}
            },
        },
    }
</script>