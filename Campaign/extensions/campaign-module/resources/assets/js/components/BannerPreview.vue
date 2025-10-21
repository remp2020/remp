<style type="text/css">
    @import '../../sass/banner.scss';
</style>

<template>
    <div class="remp-banner" :id="wrapperId">
        <html-preview v-if="template === 'html'"
                :alignmentOptions="alignmentOptions"
                :dimensionOptions="dimensionOptions"
                :positionOptions="positionOptions"
                :show="visible"
                :uuid="uuid"
                :campaignUuid="campaignUuid"
                :forcedPosition="forcedPosition"

                :textAlign="htmlTemplate.textAlign"
                :dimensions="htmlTemplate.dimensions"
                :textColor="htmlTemplate.textColor"
                :fontSize="htmlTemplate.fontSize"
                :backgroundColor="htmlTemplate.backgroundColor"
                :text="htmlTemplate.text"
                :css="htmlTemplate.css"

                :position="position"
                :offsetVertical="offsetVertical"
                :offsetHorizontal="offsetHorizontal"
                :targetUrl="targetUrl"
                :closeable="closeable"
                :closeText="closeText"
                :transition="transition"
                :displayType="displayType"
        ></html-preview>

        <medium-rectangle-preview v-if="template === 'medium_rectangle'"
                :alignmentOptions="alignmentOptions"
                :positionOptions="positionOptions"
                :show="visible"
                :uuid="uuid"
                :campaignUuid="campaignUuid"
                :forcedPosition="forcedPosition"

                :headerText="mediumRectangleTemplate.headerText"
                :mainText="mediumRectangleTemplate.mainText"
                :buttonText="mediumRectangleTemplate.buttonText"
                :width="mediumRectangleTemplate.width"
                :height="mediumRectangleTemplate.height"
                :colorScheme="colorSchemes[mediumRectangleTemplate.colorScheme]"

                :position="position"
                :offsetVertical="offsetVertical"
                :offsetHorizontal="offsetHorizontal"
                :targetUrl="targetUrl"
                :closeable="closeable"
                :closeText="closeText"
                :transition="transition"
                :displayType="displayType"
        ></medium-rectangle-preview>

        <bar-preview v-if="template === 'bar'"
                :alignmentOptions="alignmentOptions"
                :positionOptions="positionOptions"
                :show="visible"
                :uuid="uuid"
                :campaignUuid="campaignUuid"
                :forcedPosition="forcedPosition"

                :mainText="barTemplate.mainText"
                :buttonText="barTemplate.buttonText"
                :colorScheme="colorSchemes[barTemplate.colorScheme]"

                :position="position"
                :offsetVertical="offsetVertical"
                :offsetHorizontal="offsetHorizontal"
                :targetUrl="targetUrl"
                :closeable="closeable"
                :closeText="closeText"
                :transition="transition"
                :displayType="displayType"
        ></bar-preview>

        <collapsible-bar-preview v-if="template === 'collapsible_bar'"
                :alignmentOptions="alignmentOptions"
                :positionOptions="positionOptions"
                :show="visible"
                :uuid="uuid"
                :campaignUuid="campaignUuid"
                :forcedPosition="forcedPosition"

                :mainText="collapsibleBarTemplate.mainText"
                :headerText="collapsibleBarTemplate.headerText"
                :collapseText="collapsibleBarTemplate.collapseText"
                :expandText="collapsibleBarTemplate.expandText"
                :buttonText="collapsibleBarTemplate.buttonText"
                :colorScheme="colorSchemes[collapsibleBarTemplate.colorScheme]"
                :initialState="collapsibleBarTemplate.initialState"

                :targetUrl="targetUrl"
                :transition="transition"
                :displayType="displayType"
        ></collapsible-bar-preview>

        <short-message-preview v-if="template === 'short_message'"
                :alignmentOptions="alignmentOptions"
                :positionOptions="positionOptions"
                :show="visible"
                :uuid="uuid"
                :campaignUuid="campaignUuid"
                :forcedPosition="forcedPosition"

                :text="shortMessageTemplate.text"
                :colorScheme="colorSchemes[shortMessageTemplate.colorScheme]"

                :position="position"
                :offsetVertical="offsetVertical"
                :offsetHorizontal="offsetHorizontal"
                :targetUrl="targetUrl"
                :closeable="closeable"
                :closeText="closeText"
                :transition="transition"
                :displayType="displayType"
        ></short-message-preview>

        <overlay-rectangle-preview v-if="template === 'overlay_rectangle'"
                :alignmentOptions="alignmentOptions"
                :show="visible"
                :uuid="uuid"
                :campaignUuid="campaignUuid"

                :headerText="overlayRectangleTemplate.headerText"
                :mainText="overlayRectangleTemplate.mainText"
                :buttonText="overlayRectangleTemplate.buttonText"
                :width="overlayRectangleTemplate.width"
                :height="overlayRectangleTemplate.height"
                :colorScheme="colorSchemes[overlayRectangleTemplate.colorScheme]"
                :imageLink="overlayRectangleTemplate.imageLink"

                :targetUrl="targetUrl"
                :closeable="closeable"
                :closeText="closeText"
                :transition="transition"
                :displayType="displayType"
                :adminPreview="adminPreview"
        >
        </overlay-rectangle-preview>

        <html-overlay-preview v-if="template === 'html_overlay'"
              :alignmentOptions="alignmentOptions"
              :show="visible"
              :uuid="uuid"
              :campaignUuid="campaignUuid"

              :textAlign="htmlOverlayTemplate.textAlign"
              :textColor="htmlOverlayTemplate.textColor"
              :fontSize="htmlOverlayTemplate.fontSize"
              :backgroundColor="htmlOverlayTemplate.backgroundColor"
              :text="htmlOverlayTemplate.text"
              :css="htmlOverlayTemplate.css"

              :targetUrl="targetUrl"
              :closeable="closeable"
              :closeText="closeText"
              :transition="transition"
              :displayType="displayType"
        ></html-overlay-preview>

        <overlay-two-buttons-signature-preview v-if="template === 'overlay_two_buttons_signature'"
                                               :alignmentOptions="alignmentOptions"
                                               :show="visible"
                                               :uuid="uuid"
                                               :campaignUuid="campaignUuid"

                                               :textBefore="overlayTwoButtonsSignatureTemplate.textBefore"
                                               :textAfter="overlayTwoButtonsSignatureTemplate.textAfter"
                                               :textBtnPrimary="overlayTwoButtonsSignatureTemplate.textBtnPrimary"
                                               :textBtnPrimaryMinor="overlayTwoButtonsSignatureTemplate.textBtnPrimaryMinor"
                                               :textBtnSecondary="overlayTwoButtonsSignatureTemplate.textBtnSecondary"
                                               :textBtnSecondaryMinor="overlayTwoButtonsSignatureTemplate.textBtnSecondaryMinor"
                                               :targetUrlSecondary="overlayTwoButtonsSignatureTemplate.targetUrlSecondary"
                                               :signatureImageUrl="overlayTwoButtonsSignatureTemplate.signatureImageUrl"
                                               :textSignature="overlayTwoButtonsSignatureTemplate.textSignature"

                                               :targetUrl="targetUrl"
                                               :closeable="closeable"
                                               :closeText="closeText"
                                               :transition="transition"
                                               :displayType="displayType"

                                               :adminPreview="adminPreview"
        ></overlay-two-buttons-signature-preview>
        <newsletter-rectangle-preview v-if="template === 'newsletter_rectangle'"
                                      :alignmentOptions="alignmentOptions"
                                      :positionOptions="positionOptions"
                                      :show="visible"
                                      :uuid="uuid"
                                      :campaignUuid="campaignUuid"
                                      :variantUuid="variantUuid"
                                      :forcedPosition="forcedPosition"

                                      :newsletterId="newsletterRectangleTemplate.newsletterId"
                                      :btnSubmit="newsletterRectangleTemplate.btnSubmit"
                                      :title="newsletterRectangleTemplate.title"
                                      :text="newsletterRectangleTemplate.text"
                                      :success="newsletterRectangleTemplate.success"
                                      :failure="newsletterRectangleTemplate.failure"
                                      :terms="newsletterRectangleTemplate.terms"
                                      :colorScheme="colorSchemes[newsletterRectangleTemplate.colorScheme]"
                                      :width="newsletterRectangleTemplate.width"
                                      :height="newsletterRectangleTemplate.height"

                                      :endpoint="newsletterRectangleTemplate.endpoint"
                                      :useXhr="newsletterRectangleTemplate.useXhr"
                                      :requestBody="newsletterRectangleTemplate.requestBody"
                                      :requestHeaders="newsletterRectangleTemplate.requestHeaders"
                                      :paramsTransposition="newsletterRectangleTemplate.paramsTransposition"
                                      :paramsExtra="newsletterRectangleTemplate.paramsExtra"

                                      :position="position"
                                      :offsetVertical="offsetVertical"
                                      :offsetHorizontal="offsetHorizontal"
                                      :closeable="closeable"
                                      :closeText="closeText"
                                      :transition="transition"
                                      :displayType="displayType"
        ></newsletter-rectangle-preview>
    </div>
</template>


<script>
    import lib from "@remp/js-commons/js/remplib.js";
    import HtmlPreview from "./previews/Html"
    import MediumRectanglePreview from "./previews/MediumRectangle"
    import BarPreview from "./previews/Bar"
    import CollapsibleBarPreview from "./previews/CollapsibleBar"
    import ShortMessagePreview from "./previews/ShortMessage"
    import OverlayRectanglePreview from "./previews/OverlayRectangle"
    import HtmlOverlayPreview from "./previews/HtmlOverlay"
    import OverlayTwoButtonsSignaturePreview from "./previews/OverlayTwoButtonsSignature"
    import NewsletterRectanglePreview from "./previews/NewsletterRectangle"

    const props = [
        "name",
        "targetUrl",
        "position",
        "offsetVertical",
        "offsetHorizontal",
        "transition",
        "closeable",
        "closeText",
        "displayDelay",
        "closeTimeout",
        "targetSelector",
        "displayType",
        "template",
        "uuid",
        "campaignUuid",
        "forcedPosition",
        "show",

        "snippets",
        "urlParams",

        "mediumRectangleTemplate",
        "barTemplate",
        "collapsibleBarTemplate",
        "htmlTemplate",
        "shortMessageTemplate",
        "overlayRectangleTemplate",
        "htmlOverlayTemplate",
        "overlayTwoButtonsSignatureTemplate",
        "newsletterRectangleTemplate",

        "alignmentOptions",
        "dimensionOptions",
        "positionOptions",
        "colorSchemes",

        "variantUuid",
        "campaignPublicId",
        "userData",

        "adminPreview",

        "js",
        "jsIncludes",
        "cssIncludes",
        "manualEventsTracking",
    ];

    export default {
        components: {
            HtmlPreview,
            MediumRectanglePreview,
            BarPreview,
            CollapsibleBarPreview,
            ShortMessagePreview,
            OverlayRectanglePreview,
            HtmlOverlayPreview,
            OverlayTwoButtonsSignaturePreview,
            NewsletterRectanglePreview,
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
        mounted: function() {
            this.wrapperId = 'remp-banner-' + lib.uuidv4();

            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });

            this.visible = this.show;

            let cssIncludes = (this.cssIncludes) ? this.cssIncludes.filter(cssInclude => cssInclude) : [];
            if (cssIncludes && cssIncludes.length > 0) {
                for (let ii = 0; ii < cssIncludes.length; ii++) {
                    lib.loadStyle(this.injectSnippets(cssIncludes[ii]));
                }
            }

            let head = document.getElementsByTagName('head')[0]
            let iframe = document.getElementById("iframe-preview");
            if (iframe) {
                head = iframe.contentDocument.head;
            }

            let jsIncludes = (this.jsIncludes) ? this.jsIncludes.filter(jsInclude => jsInclude) : [];
            if (jsIncludes && jsIncludes.length > 0) {
                let loadedScriptsCount = 0;
                jsIncludes.forEach((jsInclude) => {
                    let script = document.createElement('script');
                    script.src = this.injectSnippets(jsInclude);
                    script.async = true;

                    let callbackOnLoad = () => {
                        loadedScriptsCount += 1;
                        if (loadedScriptsCount === jsIncludes.length) {
                            this.runCustomJavascript(this.js);
                        }
                    };

                    script.onreadystatechange = script.onload = function() {
                        if (typeof callbackOnLoad !== 'undefined' && !callbackOnLoad.done && (!script.readyState || /loaded|complete/.test(script.readyState))) {
                            callbackOnLoad.done = true;
                            callbackOnLoad();
                        }
                    };

                    head.appendChild(script);
                });
            } else {
                this.runCustomJavascript(this.injectSnippets(this.js));
            }
        },
        data: () => ({
            visible: false,
            wrapperId: null,
            paramsAdded: false
        }),
        watch: {
            'transition': function() {
                let vm = this;
                setTimeout(function() { vm.visible = false }, 100);
                setTimeout(function() { vm.visible = true }, 800);
            },
            'show': {
                handler: function() {
                    if (this.show) {
                        this.addParamsToLinks();
                    }

                    this.visible = this.show;
                },
                immediate: true
            }
        },
        computed: {
            url: function() {
                if (this.targetUrl) {
                    return this.targetUrl;
                }
                return null;
            },
        },
        methods: {
            addParamsToLinks: function () {
                if (!this.paramsAdded) {
                    this.$nextTick(function () {
                        let wrap = document.getElementById(this.wrapperId);
                        if (!wrap) {
                            return;
                        }
                        let hrefs = wrap.getElementsByTagName('a');

                        for(let ii = 0; ii < hrefs.length; ii++) {
                            let href = hrefs[ii].getAttribute('href'),
                                linkParams = {};

                            [].forEach.call(hrefs[ii].attributes, function(attr) {
                                if (/^data-param-/.test(attr.name)) {
                                    linkParams[attr.name.substr(11)] = attr.value;
                                }
                            });

                            if (href) {
                                hrefs[ii].setAttribute('href', this.addUrlParams(href, linkParams));
                                hrefs[ii].setAttribute('referrerpolicy', 'no-referrer-when-downgrade');
                            }
                        }
                    });
                }

                this.paramsAdded = true;
            },
            addUrlParams: function(url, linkParams) {
                let separator = url.indexOf("?") === -1 ? "?" : "&";
                url =  url + separator + "rtm_source=remp_campaign" +
                    "&rtm_medium=" + encodeURIComponent(this.displayType);
                if (this.campaignUuid) {
                    url += "&rtm_campaign=" + encodeURIComponent(this.campaignUuid);
                }
                if (this.uuid) {
                    url += "&rtm_content=" + encodeURIComponent(this.uuid);
                }
                if (this.variantUuid) {
                    url += "&rtm_variant=" + encodeURIComponent(this.variantUuid);
                }

                if (this.urlParams) {
                    for (let param in this.urlParams) {
                        if (this.urlParams.hasOwnProperty(param)) {
                            url += "&" + encodeURIComponent(param) + '=' + encodeURIComponent(this.urlParams[param]())
                        }
                    }
                }

                if (linkParams) {
                    for (let param in linkParams) {
                        if (linkParams.hasOwnProperty(param)) {
                            url += "&" + encodeURIComponent(param) + '=' + encodeURIComponent(linkParams[param])
                        }
                    }
                }

                return url;
            },
            injectSnippets: function(str) {
                let re = /\{\{\s?(.*?)\s?\}\}/g;
                let match;

                while (match = re.exec(str)) {
                    re.lastIndex = 0;
                    let replRegex = new RegExp(match[0], "g");
                    let replVal = '';
                    if (remplib &&
                        remplib.campaign &&
                        remplib.campaign.snippets &&
                        remplib.campaign.snippets.hasOwnProperty(match[1])
                    ) {
                        replVal = remplib.campaign.snippets[match[1]].value()
                    } else if (this.snippets && this.snippets.hasOwnProperty(match[1])) {
                        replVal = this.snippets[match[1]];
                    } else {
                        throw EvalError("cannot render banner, snippet [" + match[1] + "] is missing");
                    }
                    str = str.replace(replRegex, replVal);
                }
                return str;
            },
            closed: function(event) {
                event.preventDefault();
                remplib.campaign.storeCampaignClosed(this.campaignPublicId);

                this.visible = false;
                this.$parent.$emit('values-changed', [
                    {key: "show", val: false}
                ]);
                if (this.manualEventsTracking || this.closeTracked) {
                    return true;
                }
                this.trackEvent("banner", "close", null, null, {
                    "rtm_source": "remp_campaign",
                    "rtm_medium": this.displayType,
                    "rtm_campaign": this.campaignUuid,
                    "rtm_content": this.uuid,
                    "rtm_variant": this.variantUuid
                });
                this.closeTracked = true;
            },
            clicked: function(event, hideBanner = false) {
                remplib.campaign.storeCampaignClicked(this.campaignPublicId);

                if (this.manualEventsTracking || this.clickTracked) {
                    return true;
                }
                this.trackEvent("banner", "click", null, null, {
                    "rtm_source": "remp_campaign",
                    "rtm_medium": this.displayType,
                    "rtm_campaign": this.campaignUuid,
                    "rtm_content": this.uuid,
                    "rtm_variant": this.variantUuid
                });
                this.clickTracked = true;
                if (hideBanner) {
                    this.visible = false;
                }
                return true;
            },
            collapsed: function(isCollapsed) {
                if (typeof remplib.campaign === 'undefined') {
                    return;
                }

                remplib.campaign.storeCampaignCollapsed(this.campaignPublicId, isCollapsed);
            },
            trackEvent: function(category, action, tags, fields, source) {
                if (typeof remplib.tracker === 'undefined') {
                    return;
                }
                remplib.tracker.trackEvent(category, action, tags, fields, source);
            },
            customPositioned: function() {
                if (this.displayType === 'inline') {
                    return false;
                }
                if (this.displayType === 'overlay') {
                    return true;
                }
                if (this.forcedPosition !== undefined && this.forcedPosition === 'absolute') {
                    return true;
                }
                return false;
            },
            runCustomJavascript: function (js) {
                let that = this;
                this.$nextTick(() => {
                      try {
                          // Evaluating JS code using Function with passed params object
                          // https://stackoverflow.com/questions/49125059/how-to-pass-parameters-to-an-eval-based-function-injavascript
                          let body = 'function(params) { ' + that.injectSnippets(js) + ' }';
                          let wrap = s => "{ return " + body + " };";

                          let contentWindow = window;
                          let iframe = document.getElementById("iframe-preview");
                          if (iframe) {
                              contentWindow = iframe.contentWindow;
                          }

                          contentWindow.myFunction = new contentWindow.Function(wrap(body));
                          contentWindow.myFunction.call(null).call(null, that.paramsForCustomJavascript());
                      } catch(err) {
                          console.warn("unable to execute custom banner JS:", js);
                          console.warn(err);
                      }
                }, this)
            },
            paramsForCustomJavascript: function () {
                return {
                    "rtmSource": "remp_campaign",
                    "rtmMedium": this.displayType,
                    "rtmCampaign": this.campaignUuid,
                    "rtmContent": this.uuid,
                    "rtmVariant": this.variantUuid,
                    // kept for backward-compatibility reasons
                    "utmSource": "remp_campaign",
                    "utmMedium": this.displayType,
                    "utmCampaign": this.campaignUuid,
                    "utmContent": this.uuid,
                    "bannerVariant": this.variantUuid,
                }
            }
        }
    }
</script>
