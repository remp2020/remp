<template>
    <div id="previewFrameWrap" class="previewFrameWrap"></div>
</template>

<script>
    export default {
        name: 'mail-preview',
        props: {
            htmlContent: {
                type: String,
                required: true
            },
            htmlLayout: {
                type: String,
                default: null
            },
            textLayout: {
                type: String,
                default: null
            }
        },
        data() {
            return {
                iframe: null,
            }
        },
        watch: {
            htmlContent() {
                this.rerenderIframe();
            },
            htmlLayout() {
                this.rerenderIframe();
            },
            textLayout() {
                this.rerenderIframe();
            }
        },
        methods: {
            rerenderIframe: function (textLayout = false) {
                this.showHtmlPreview = !textLayout;
                let finalContent = "";
                let layout = textLayout ? this.textLayout : this.htmlLayout;
                let content = this.htmlContent.replace(new RegExp('\{%.*?%\}', 'g'), '').replace("{{ content|raw }}", '');

                if (!layout) {
                    finalContent += content;
                } else {
                    finalContent += layout.replace("{{ content|raw }}", content).replace(new RegExp('\{%.*?%\}', 'g'), '');
                }

                if (!this.iframe) {
                    this.iframe = window.document.createElement('iframe');
                    this.iframe.classList.add('previewFrame');
                    let wrapEl = window.document.getElementById('previewFrameWrap');

                    wrapEl.innerHTML = "";

                    wrapEl.appendChild(this.iframe);
                }

                this.iframe.contentWindow.document.open('text/html', 'replace');
                this.iframe.contentWindow.document.write(finalContent);
                this.iframe.contentWindow.document.close();

                if (textLayout) {
                    this.iframe.contentWindow.document.body.style.whiteSpace = 'pre-wrap';
                }

                this.$nextTick(() => {
                    // auto height iframe on content
                    this.iframe.style.height = (this.iframe.contentWindow.document.body.scrollHeight + 100) + 'px';
                });
            }
        },
        mounted: function () {
            let self = this;

            this.rerenderIframe();

            $('body').on('preview:change', function (e, data) {
                self.rerenderIframe(data?.textarea);
            })
        }
    }
</script>

<style>
    .previewFrame {
        width: 100%;
        height: 100%;
        border: none;
        all: initial;
    }
</style>
