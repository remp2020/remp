<style type="text/css" scoped>
    @import url('../../../sass/transitions.scss');

    .preview-admin-close {
        position: fixed;
        top: 14px;
        right: 30px;
        font-size: 14px;
        line-height: 18px;
        text-decoration: none;
        color: #ff2e00;
        background-color: #fff;
        padding: 2px;
        z-index: 100001;
    }

    .overlay-with-two-btn-signature-background {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        display: -webkit-box;
        display: -moz-box;
        display: -ms-flexbox;
        display: -webkit-flex;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100000;
    }

    #banner-preview .overlay-with-two-btn-signature-background {
        position: absolute;
    }

    .overlay-with-two-btn-signature {
        font-size: 18px;
        line-height: 1.33em;
        color: #181818;
        text-align: left;
        max-width: 690px;
        margin: auto;
        padding: 5px 18px;
        background-color: white;
    }
    .overlay-with-two-btn-signature p {
        margin: 0.66em 0;
    }
    .overlay-with-two-btn-signature .buttons {
        display: -ms-flexbox;
        display: flex;
        -ms-flex-direction: row;
        flex-direction: row;
        -ms-flex-pack: justify;
        justify-content: space-between;
        -ms-flex-align: stretch;
        align-items: stretch;
    }
    .overlay-with-two-btn-signature .btn {
        -ms-flex: 1 1;
        flex: 1 1;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        text-decoration: none;
        border: 2px solid #ccc;
        border-radius: 5px;
        color: initial;
        padding: 7px 10px;
        text-align: center;
        margin: 2px 0;
        font-size: 110%;
    }
    .overlay-with-two-btn-signature .btn .item {
        width: 100%;
        align-self: center;
        white-space: normal;
    }
    .overlay-with-two-btn-signature .btn .desc {
        font-size: 75%;
        font-weight: 200;
    }
    .overlay-with-two-btn-signature .btn-primary {
        background-color: #46B863;
        border-color: #46B863;
        color: white;
        border-bottom-color: rgba(0, 0, 0, 0.25);
    }
    .overlay-with-two-btn-signature .btn-primary:hover, .overlay-with-two-btn-signature .btn-primary:focus {
        border-bottom-color: rgba(0, 0, 0, 0.5);
        background-color: #46B863;
    }
    .overlay-with-two-btn-signature .btn-secondary {
        background-color: #3CB6D6;
        border-color: #3CB6D6;
        color: white;
        border-bottom-color: rgba(0, 0, 0, 0.25);
    }
    .overlay-with-two-btn-signature .btn-secondary:hover, .overlay-with-two-btn-signature .btn-secondary:focus {
        border-bottom-color: rgba(0, 0, 0, 0.5);
        background-color: #3CB6D6;
    }
    .overlay-with-two-btn-signature .close-button {
        font-size: 80%;
        margin-top: 0;
        text-align: center;
        -ms-flex: none;
        flex: none;
    }
    .overlay-with-two-btn-signature .close-button a {
        color: #999;
        text-decoration: underline;
        cursor: pointer;
    }
    .overlay-with-two-btn-signature .signature {
        width: 260px;
        font-size: 80%;
        line-height: 1.33;
        display: inline-block;
    }
    .overlay-with-two-btn-signature .signature img {
        width: 40%;
    }
    .overlay-with-two-btn-signature .signature p {
        margin-top: 0;
    }
    @media only screen and (max-width: 688px) {
        .overlay-with-two-btn-signature {
            font-size: 16px;
        }
    }
    @media only screen and (max-width: 629px) {
        .overlay-with-two-btn-signature {
            padding: 2px 5px;
        }
        .overlay-with-two-btn-signature .buttons {
            display: block;
        }
        .overlay-with-two-btn-signature .btn {
            width: initial;
            display: block;
            min-width: initial;
            font-size: 100%;
        }
        .overlay-with-two-btn-signature .spacer {
            display: none;
        }
    }

</style>

<template>
    <div role="dialog">
        <a href="#"
           class="preview-admin-close"
           v-on:click.stop="$parent.closed"
           v-on:keydown.enter.space="$parent.closed"
           v-if="isVisible && !closeable && adminPreview">CLOSE BANNER</a>
        <transition appear name="fade">
            <div class="overlay-with-two-btn-signature-background" v-if="isVisible">

                <transition appear v-bind:name="transition">

                    <div class="overlay-with-two-btn-signature serif">

                        <div class="text-before-buttons" v-html="$parent.injectSnippets(textBeforeMultiLine)"></div>

                        <div class="buttons sans-serif">
                            <a class="btn btn-primary" tabindex="0" role="button"
                               v-bind:href="$parent.injectSnippets(targetUrl)"
                               v-on:click.stop="$parent.clicked($event, !$parent.url)"
                               v-on:keydown.enter.space.stop="$parent.clicked($event, !$parent.url)"
                               data-param-rtm_keyword="btn-primary">
                                <span class="item title" v-html="$parent.injectSnippets(textBtnPrimary)"></span>
                                <span class="item desc" v-if="hasTextBtnPrimaryMinor" v-html="$parent.injectSnippets(textBtnPrimaryMinor)"></span>
                            </a>

                            <div class="spacer" v-if="hasSecondaryButton">&nbsp;</div>

                            <a class="btn btn-secondary" v-if="hasSecondaryButton"
                               v-bind:href="$parent.injectSnippets(targetUrlSecondary)"
                               data-param-rtm_keyword="btn-secondary">
                                <span class="item title" v-html="$parent.injectSnippets(textBtnSecondary)"></span>
                                <span class="item desc" v-if="hasTextBtnSecondaryMinor" v-html="$parent.injectSnippets(textBtnSecondaryMinor)"></span>
                            </a>
                        </div>

                        <p class="close-button sans-serif">
                            <a href="#"
                               v-bind:class="[{hidden: !closeable}]"
                               v-bind:title="closeText || 'Close banner'"
                               v-bind:aria-label="closeText || 'Close banner'"
                               v-on:click.stop="$parent.closed"
                               v-on:keydown.enter.space.stop="$parent.closed"
                            ><span>{{ closeText }}</span></a>
                        </p>

                        <div class="text-after-buttons" v-html="$parent.injectSnippets(textAfterMultiLine)"></div>

                        <div class="signature">
                            <img v-bind:src="$parent.injectSnippets(signatureImageUrl)" alt="Signature" />
                            <p v-html="$parent.injectSnippets(textSignatureMultiLine)"></p>
                        </div>

                    </div>

                </transition>

            </div>

        </transition>
    </div>
</template>

<script>
    export default {
        name: 'overlay-two-buttons-signature-preview',
        props: [
            'alignmentOptions',
            'show',
            'uuid',
            'campaignUuid',

            'textBefore',
            'textAfter',
            'textBtnPrimary',
            'textBtnPrimaryMinor',
            'textBtnSecondary',
            'textBtnSecondaryMinor',
            'targetUrlSecondary',
            'signatureImageUrl',
            'textSignature',

            'targetUrl',
            'closeable',
            'closeText',
            'transition',
            'displayType',
            'adminPreview'
        ],
        data: function() {
            return {
                visible: true,
                closeTracked: false,
                clickTracked: false,
            }
        },
        methods: {
            multiLine: function(text){
                if (text) {
                    text = text.replace(/(?:\r\n|\r|\n)/g, '<br>')
                    text = text.replace(/((<br>){2,})/g, '<br><br>');
                    return '<p>' + text.split('<br><br>').join('</p><p>') + '</p>';
                } else {
                    return '<p></p>';
                }
            }
        },
        computed: {
            isVisible: function() {
                return this.show && this.visible;
            },
            hasSecondaryButton: function() {
                return this.textBtnSecondary && this.targetUrlSecondary;
            },
            hasTextBtnPrimaryMinor: function() {
                return !!this.textBtnPrimaryMinor;
            },
            hasTextBtnSecondaryMinor: function() {
                return !!this.textBtnSecondaryMinor;
            },
            textBeforeMultiLine: function () {
                return this.multiLine(this.textBefore)
            },
            textAfterMultiLine: function () {
                return this.multiLine(this.textAfter)
            },
            textSignatureMultiLine: function () {
                return this.multiLine(this.textSignature)
            }
        }
    }
</script>
