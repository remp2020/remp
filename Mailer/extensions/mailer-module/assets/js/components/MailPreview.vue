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
        mailTypeId: {
            type: [Number, String],
            default: null
        },
        mailLayoutId: {
            type: [Number, String],
            default: null
        }
    },
    created() {
        this._debounceTimer = null;
        this._abortController = null;
    },
    data() {
        return {
            iframe: null,
            previewUrl: null,
        }
    },
    watch: {
        htmlContent() {
            this.scheduleRender(false);
        },
        textContent() {
            this.scheduleRender(true);
        },
    },
    methods: {
        scheduleRender: function (textLayout = false) {
            clearTimeout(this._debounceTimer);
            this._debounceTimer = setTimeout(() => {
                this.fetchRenderedPreview(textLayout);
            }, 400);
        },
        fetchRenderedPreview: async function (textLayout = false) {
            if (this._abortController) {
                this._abortController.abort();
            }
            this._abortController = new AbortController();

            try {
                const response = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new URLSearchParams({
                        htmlContent: this.htmlContent || '',
                        textContent: this.textContent || '',
                        mailLayoutId: this.mailLayoutId || '',
                        mailTypeId: this.mailTypeId || '',
                    }),
                    signal: this._abortController.signal,
                });
                const data = await response.json();

                let finalContent = textLayout ? (data.text || '') : (data.html || '');
                this.writeToIframe(finalContent, textLayout);
            } catch (_e) {
                if (this._abortController?.signal.aborted) {
                    return;
                }
            }
        },
        writeToIframe: function (content, textLayout = false) {
            if (!this.iframe) {
                this.iframe = window.document.createElement('iframe');
                this.iframe.classList.add('previewFrame');
                let wrapEl = window.document.getElementById('previewFrameWrap');

                wrapEl.innerHTML = "";

                wrapEl.appendChild(this.iframe);
            }

            this.iframe.addEventListener('load', () => {
                const body = this.iframe.contentWindow.document.body;
                body.style.overflow = 'hidden';

                if (textLayout) {
                    body.style.whiteSpace = 'pre-wrap';
                }

                const setHeight = () => {
                    this.iframe.style.height = Math.max(600, body.scrollHeight) + 'px';
                };

                setHeight();

                const ro = new this.iframe.contentWindow.ResizeObserver(setHeight);
                ro.observe(body);
            }, { once: true });

            this.iframe.srcdoc = content;
        }
    },
    mounted: function () {
        let self = this;

        this.previewUrl = this.$el.parentElement?.dataset?.previewUrl || null;
        this.scheduleRender();

        $('body').on('preview:change.mailPreview', function (_e, data) {
            self.scheduleRender(data?.textarea);
        });
    },
    beforeUnmount: function () {
        $('body').off('preview:change.mailPreview');
        clearTimeout(this._debounceTimer);
        if (this._abortController) {
            this._abortController.abort();
        }
    }
}
</script>

<style scoped>
:global(.previewFrame) {
    width: 100%;
    border: none;
    display: block;
}
</style>
