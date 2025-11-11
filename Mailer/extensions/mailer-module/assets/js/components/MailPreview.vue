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
        textContent: {
            type: String,
            default: null
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
            this.rerenderIframe(false);
        },
        textContent() {
            this.rerenderIframe(true);
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
            let finalContent = "";
            let layout = textLayout ? this.textLayout : this.htmlLayout;
            let content = textLayout ? this.textContent : this.htmlContent;

            content = content
                .replace("{{ content|raw }}", '')
                .replace("{{ content }}", '')
                .replace(new RegExp('\{%.*?%\}', 'g'), '');

            if (!layout) {
                finalContent += content;
            } else {
                finalContent += layout
                    .replace("{{ content|raw }}", content)
                    .replace("{{ content }}", content)
                    .replace(new RegExp('\{%.*?%\}', 'g'), '');
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
                // Reset height first to get accurate scrollHeight measurement
                this.iframe.style.height = 'auto';
                let frameHeight = this.iframe.contentWindow.document.body.scrollHeight;
                if (textLayout) {
                    this.iframe.style.height = (frameHeight) + 'px';
                } else {
                    this.iframe.style.height = (frameHeight + 100) + 'px';
                }
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

<style scoped>
:global(.previewFrame) {
    width: 100%;
    height: 100%;
    border: none;
}
</style>
