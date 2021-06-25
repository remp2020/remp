<template>
    <div id="previewFrameWrap" class="previewFrameWrap"></div>
</template>

<script>
    export default {
        name: 'mail-preview',
        data() {
            return {
                iframe: null
            }
        },
        methods: {
            rerenderIframe: function () {
                let finalContent = "";
                let layout = this.$parent.htmlLayout;
                let content = this.$parent.htmlContent.replace(new RegExp('\{%.*?%\}', 'g'), '').replace("{{ content|raw }}", '');
                if (!layout) {
                    finalContent += content;
                } else {
                    finalContent += layout.replace("{{ content|raw }}", content).replace(new RegExp('\{%.*?%\}', 'g'), '');
                }

                this.iframe = window.document.createElement('iframe');
                this.iframe.classList.add('previewFrame');
                let wrapEl = window.document.getElementById('previewFrameWrap');

                wrapEl.innerHTML = "";

                wrapEl.appendChild(this.iframe);

                this.iframe.contentWindow.document.open('text/html', 'replace');
                this.iframe.contentWindow.document.write(finalContent);
                this.iframe.contentWindow.document.close();

                // auto height iframe on content
                this.iframe.style.height = (this.iframe.contentWindow.document.body.scrollHeight + 100) + 'px';
            }
        },
        mounted: function () {
            let self = this;

            this.rerenderIframe();

            $('body').on('preview:change', function (e) {
                self.rerenderIframe();
            })
        }
    }
</script>

<style>
    .previewFrame {
        width: 100%;
        height: 100%;
        border: none;
    }
</style>
