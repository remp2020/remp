<template>
    <div>
        <ul class="tab-nav" role="tablist" data-tab-color="teal">
            <li class="active">
                <a href="#html-template" role="tab" data-toggle="tab" aria-expanded="true">HTML template</a>
            </li>
        </ul>

        <div class="card m-t-15">
            <div class="tab-content p-0">
                <div role="tabpanel" class="active tab-pane" id="html-template">
                    <div class="card-body card-padding p-l-15">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="zmdi zmdi-aspect-ratio-alt"></i></span>
                            <div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="dimensions" class="fg-label">Dimensions</label>
                                    </div>
                                    <div class="col-md-12">
                                        <select v-model.lazy="dimensions" class="selectpicker" name="dimensions" id="dimensions" title="Select dimensions">
                                            <option v-for="option in dimensionOptions" v-bind:value="option.key">
                                                {{ option.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="cp-container">
                            <div class="input-group fg-float m-t-30">
                                <span class="input-group-addon"><i class="zmdi zmdi-format-color-fill"></i></span>
                                <div class="fg-line dropdown">
                                    <label for="background_color" class="fg-label">Background Color</label>
                                    <input v-model="backgroundColor" class="form-control cp-value" data-toggle="dropdown" name="background_color" id="background_color" type="text">

                                    <div class="dropdown-menu">
                                        <div class="color-picker"></div>
                                    </div>
                                    <i class="cp-value"></i>
                                </div>
                            </div>
                        </div>

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-subject"></i></span>
                            <div class="fg-line">
                                <label for="html_text" class="fg-label">HTML Text</label>
                                <textarea v-model="text" class="form-control fg-input" rows="3" name="text" cols="50" id="html_text"></textarea>
                            </div>
                        </div>

                        <div class="cp-container">
                            <div class="input-group fg-float m-t-30">
                                <span class="input-group-addon"><i class="zmdi zmdi-format-color-text"></i></span>
                                <div class="fg-line dropdown">
                                    <label for="text_color" class="fg-label">Text Color</label>
                                    <input v-model="textColor" class="form-control cp-value" data-toggle="dropdown" name="text_color" id="text_color" type="text">

                                    <div class="dropdown-menu">
                                        <div class="color-picker" data-cp-default="#03A9F4"></div>
                                    </div>
                                    <i class="cp-value"></i>
                                </div>
                            </div>
                        </div>

                        <div class="input-group fg-float m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-format-size"></i></span>
                            <div class="fg-line">
                                <label for="font_size" class="fg-label">Font Size</label>
                                <input v-model="fontSize" class="form-control fg-input" name="font_size" type="number" id="font_size">
                            </div>
                        </div>

                        <div class="input-group m-t-30">
                            <span class="input-group-addon"><i class="zmdi zmdi-swap"></i></span>
                            <div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="text_align" class="fg-label">Text Alignment</label>
                                    </div>
                                    <div class="col-md-12">
                                        <select v-model="textAlign" class="selectpicker" name="text_align" id="text_align" title="Select alignment">
                                            <option v-for="option in alignmentOptions" v-bind:value="option.key">
                                                {{ option.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/javascript">
    let props = [
        "_backgroundColor",
        "_text",
        "_textColor",
        "_fontSize",
        "_textAlign",
        "_dimensions",

        "alignmentOptions",
        "dimensionOptions",
    ];
    export default {
        name: "html-template",
        props: props,
        mounted: function(){
            props.forEach((prop) => {
                this[prop.slice(1)] = this[prop];
            });
        },
        data: () => ({
            backgroundColor: null,
            text: null,
            textColor: null,
            fontSize: null,
            textAlign: null,
            dimensions: null,
        }),
        updated: function(a,b,c) {
            this.$parent.$emit("values-changed", [
                {key: "backgroundColor", val: this.backgroundColor},
                {key: "text", val: this.text},
                {key: "textColor", val: this.textColor},
                {key: "fontSize", val: this.fontSize},
                {key: "textAlign", val: this.textAlign},
                {key: "dimensions", val: this.dimensions},
            ]);
        }
    }
</script>