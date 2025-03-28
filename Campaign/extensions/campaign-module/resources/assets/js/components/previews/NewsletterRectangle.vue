<style type="text/css" scoped>
@import url('../../../sass/transitions.scss');

.newsletter-rectangle-preview-close {
    position: absolute;
    display: block;
    top: 0;
    right: 0;
    text-decoration: none;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    min-width: 40px;
    height: 40px;
    letter-spacing: 0.05em;
    line-height: 40px;
    padding-right: 3px;
    text-align: right;
    cursor: pointer;
}

a.newsletter-rectangle-preview-close::after {
    content: "\00a0\00d7\00a0";
    font-size: 24px;
    vertical-align: sub;
    font-weight: normal;
    line-height: 40px;
    display: inline-block;
}

.newsletter-rectangle-form {
    position: relative;
    margin: 0 auto;
    padding: 20px;
    font-size: 14px;
    line-height: 20px;
    max-width: 420px;
    overflow: hidden;
}

.newsletter-rectangle-title {
    font-size: 18px;
    line-height: 26px;
    font-weight: bold;
    margin-bottom: 15px;
    margin-top: 18px;
}

.newsletter-rectangle-text {
    margin-bottom: 15px;
    white-space: pre-line;
    font-size: 16px;
}

.newsletter-rectangle-form-label {
    display: none;
}

.newsletter-rectangle-form-inputs {
    margin: 20px 0;
    border: none;
    padding: 0;
}

.newsletter-rectangle-form-input {
    width: 100%;
    height: 50px;
    line-height: 22px;
    border: 0;
    border-radius: 3px;
    font-size: 16px;
    padding: 0 10px;
    color: #000000;
}

.newsletter-rectangle-form-button {
    display: block;
    width: 100%;
    margin-top: 10px;
    border: 0;
    height: 50px;
    line-height: 50px;
    border-radius: 3px;
    font-size: 15px;
}

.newsletter-rectangle-disclaimer {
    font-size: 11px;
    text-align: center;
    margin: 10px 20px;
    font-weight: 300;
    line-height: 16px;
}

.newsletter-rectangle-disclaimer a {
    text-decoration: underline;
    white-space: nowrap;
}

.newsletter-rectangle-failure-message {
    text-align: left;
}

.compact .newsletter-rectangle-title, .compact .newsletter-rectangle-text  {
    margin-bottom: 10px;
}

.compact .newsletter-rectangle-form-inputs {
    margin: 10px 0;
}

.compact .newsletter-rectangle-form-input, .compact .newsletter-rectangle-form-button {
    height: 40px;
}
.compact .newsletter-rectangle-form-button {
    line-height: 40px;
    margin-top: 7px;
}

.compact.newsletter-rectangle-form {
    padding: 15px;
}

.newsletter-rectangle-form-button.newsletter-rectangle-failure {
    color: #b00c28 !important;
}

.newsletter-rectangle-form-button.newsletter-rectangle-doing-ajax::after {
    content: '\2022\2022\2022';
}

</style>

<template>
    <div
        role="alert"
        class="newsletter-rectangle-preview"
        v-if="isVisible"
        v-bind:style="[containerStyles, _position]"
    >
        <transition appear v-bind:name="transition">

            <form class="newsletter-rectangle-form" method="POST"
                  v-bind:style="[boxStyles, formStyles]"
                  v-bind:action="endpoint"
                  v-bind:class="boxClass"
                  v-on:submit="_formSubmit"
            >
                <a class="newsletter-rectangle-preview-close"
                   role="button"
                   tabindex="0"
                   v-bind:class="[{hidden: !closeable}]"
                   v-bind:style="[linkStyles]"
                   v-bind:title="closeText || 'Close banner'"
                   v-bind:aria-label="closeText || 'Close banner'"
                   v-on:click.stop="$parent.closed"
                   v-on:keydown.enter.space="$parent.closed"
                >{{ closeText }}</a>

                <div class="newsletter-rectangle-title" role="heading" aria-level="1"
                     v-html="$parent.injectSnippets(title)"></div>
                <div class="newsletter-rectangle-text"  role="heading" aria-level="2"
                     v-html="$parent.injectSnippets(text)"></div>

                <fieldset class="newsletter-rectangle-form-inputs">
                    <label class="newsletter-rectangle-form-label" for="newsletter-rectangle-form-email">Email</label>
                    <input class="newsletter-rectangle-form-input"
                           type="email" required
                           placeholder="e-mail"
                           id="newsletter-rectangle-form-email"
                           v-bind:value="_campaignVariable('email')"
                           @keydown="clearLastResponse"
                           v-bind:class="{'newsletter-rectangle-doing-ajax': doingAjax}"
                           v-bind:disabled="doingAjax"
                           v-bind:name="_form('email')">

                    <input type="hidden" v-bind:name="_form('newsletter_id')" v-bind:value="newsletterId">
                    <input type="hidden" v-bind:name="_form('source')" v-bind:value="_source">
                    <input type="hidden" v-bind:name="_form('referer')" v-bind:value="_referer">

                    <input type="hidden" name="rtm_source" value="remp_campaign">
                    <input type="hidden" name="rtm_medium" v-bind:value="displayType">
                    <input type="hidden" name="rtm_campaign" v-if="campaignUuid"  v-bind:value="campaignUuid">
                    <input type="hidden" name="rtm_content" v-if="uuid" v-bind:value="uuid">
                    <input type="hidden" name="banner_variant" v-if="variantUuid" v-bind:value="variantUuid">

                    <input v-for="param in paramsExtra" type="hidden"
                           v-bind:name="param"
                           v-bind:value="_campaignVariable(param)"
                    >

                    <button class="newsletter-rectangle-form-button"
                            v-bind:disabled="doingAjax || subscriptionSuccess !== null"
                            v-bind:class="{
                                'newsletter-rectangle-doing-ajax': doingAjax,
                                'newsletter-rectangle-failure': subscriptionSuccess === false,
                                'newsletter-rectangle-success': subscriptionSuccess === true }"
                            v-bind:style="[buttonStyles]"
                            v-bind:aria-label="(_btnSubmit + (terms ? ', ' + terms : '')) | strip_html"
                            v-html="_btnSubmit"></button>
                </fieldset>
                <div class="newsletter-rectangle-failure-message"></div>
                <div class="newsletter-rectangle-disclaimer" v-html="terms" ></div>

                <div v-html="_formStyles" aria-hidden="true"></div>
            </form>
        </transition>
    </div>
</template>

<script>
export default {
    name: 'newsletter-rectangle-preview',
    props: [
        "alignmentOptions",
        "positionOptions",
        "show",
        "uuid",
        "variantUuid",
        "campaignUuid",
        "forcedPosition",

        "newsletterId",
        "btnSubmit",
        "title",
        "text",
        "success",
        "failure",
        "terms",
        "colorScheme",
        "width",
        "height",

        "endpoint",
        "useXhr",
        "requestBody",
        "requestHeaders",
        "paramsTransposition",
        "paramsExtra",

        "position",
        "offsetVertical",
        "offsetHorizontal",
        "closeable",
        "closeText",
        "transition",
        "displayType",
    ],
    data: function () {
        return {
            visible: true,
            closeTracked: false,
            clickTracked: false,
            subscriptionSuccess: null,
            doingAjax: false,
            failureMessage: "",
        }
    },
    methods: {
        _campaignVariable: function (name) {
            if (!remplib.campaign) {
                return null;
            }
            if (remplib.campaign.variables && remplib.campaign.variables.hasOwnProperty(name)) {
                return remplib.campaign.variables[name].value();
            }
            throw new Error("REMPLIB: unable to display banner, configured extra parameter is missing: " + name);
        },
        _form: function (name){
            if (typeof this.paramsTransposition == 'object' && this.paramsTransposition !== null && this.paramsTransposition.hasOwnProperty(name)){
                return this.paramsTransposition[name];
            }
            return name;
        },
        _formSubmit: function (event){
            let form = event.target;
            let formData = new FormData(form);
            let headers = new Headers;
            let data;
            let settings = {};
            let request;

            if (!this.useXhr){
                this.$parent.clicked(event);
                return true;
            }

            event.preventDefault();
            event.stopPropagation();
            this.$parent.clicked(event,false);

            for (const [key, value] of Object.entries(this.requestHeaders)) {
                headers.set(key, value.toString());
            }

            switch (this.requestBody){
                case 'json':
                    data = JSON.stringify(Object.fromEntries(formData.entries()));
                    headers.set('Content-Type', 'application/json');
                    break;
                case 'form-data':
                    data = formData;
                    break;
                case 'x-www-form-urlencoded':
                    data = new URLSearchParams(formData).toString();
                    headers.set('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                    break;
                default:
                  throw new Error("REMPLIB: unsupported type of requestBody: " + this.requestBody)
            }

            settings = {
                method: 'POST',
                headers: headers,
            };

            request = new Request(this.endpoint);
            settings.body = data;

            this.doingAjax = true;
            this.subscriptionSuccess = null;

            fetch(request, settings).then(response => {

                const customEventName = (response.ok) ? 'rempNewsletterSubscribeSuccess' : 'rempNewsletterSubscribeFailure'
                this.subscriptionSuccess = response.ok;

                response.text().then(function(text) {
                  form.dispatchEvent(new CustomEvent( customEventName, {
                    detail: {
                      'type': 'response',
                      'response': text,
                    },
                    bubbles: true,
                    cancelable: true
                  }));
                });

            }).catch((error) => {
                this.subscriptionSuccess = false;
                form.dispatchEvent(new CustomEvent( 'rempNewsletterSubscribeFailure', {
                    detail: {
                        'type': 'exception',
                        'message': error.message
                    },
                    bubbles: true,
                    cancelable: true
                }));
            }).finally(() => {
                this.doingAjax = false;
            });
        },
        clearLastResponse: function(event){
            this.subscriptionSuccess = null;
        }
    },
    computed: {
        _btnSubmit: function(){
            if (this.doingAjax){
                return '';
            }
            if (this.subscriptionSuccess === true ){
                return this.success;
            }
            if (this.subscriptionSuccess === false){
                return this.failure;
            }
            return this.btnSubmit;
        },
        _source: function(){
            return 'newsletter-rectangle';
        },
        _referer: function (){
            if (window && window.location && window.location.href){
                return window.location.href;
            }
            if (location && location.href){
                return location.href;
            }
        },
        _formStyles: function (){
            return `<style>.newsletter-rectangle-form a {color: ${this.colorScheme.buttonBackgroundColor}</style>`;
        },
        _position: function () {
            if (!this.$parent.customPositioned()) {
                return {};
            }

            if (this.positionOptions[this.position]) {
                let styles = this.positionOptions[this.position].style;

                for (let ii in styles) {
                    styles[ii] = ((ii === 'top' || ii === 'bottom') ? this.offsetVertical : this.offsetHorizontal) + 'px'
                }

                return styles;
            }

            return {};
        },
        containerStyles: function () {
            let position, zIndex;
            if (this.displayType === 'overlay') {
                position = 'fixed';
                zIndex = 9999;
            } else {
                position = 'relative'
            }
            if (typeof this.forcedPosition !== 'undefined') {
                position = this.forcedPosition;
            }
            return {
                position: position,
                zIndex: zIndex,
            }
        },
        boxStyles: function () {
            return {
                backgroundColor: this.colorScheme.backgroundColor,
                color: this.colorScheme.textColor,
                minWidth: this.width || '100px',
                maxWidth: this.width || '',
                minHeight: this.height || '250px',
                maxHeight: this.height || '',
            }
        },
        boxClass: function (){
            let width = parseInt(this.width);
            if (!isNaN(width) && width < 420){
                return 'compact';
            }
        },
        buttonStyles: function () {
            if (this.subscriptionSuccess === true || this.subscriptionSuccess === false){
                return {
                    backgroundColor: this.colorScheme.buttonTextColor,
                    color: this.colorScheme.buttonBackgroundColor,
                }
            }
            return {
                color: this.colorScheme.buttonTextColor,
                backgroundColor: this.colorScheme.buttonBackgroundColor,
            }
        },
        linkStyles: function () {
            return {
                color: this.colorScheme.textColor,
            }
        },
        isVisible: function () {
            return this.show && this.visible;
        },
    },
}
</script>
