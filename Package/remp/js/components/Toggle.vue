<template>
    <div class="toggle-switch">
        <input  :id="id"
                :name="name"
                value="1"
                :disabled="disabled"
                v-model="checked"
                @change="onChange"
                type="checkbox"
                hidden="hidden">
        <label :for="id" class="ts-helper"></label>
    </div>
</template>

<script type="text/javascript">
    let props = {
        id: {
            type: String,
            default() {
                return 'toggle-' + this._uid
            }
        },
        name: String,
        disabled: {
            type: Boolean,
            default: false
        },
        isChecked: {
            type: Boolean,
            default: false
        },

        authToken: String,
        method: String,
        activateUrl: String,
        deactivateUrl: String,
        data: Object,
        activateData: Object,
        deactivateData: Object,
        callback: Function
    };

    export default {
        name: "toggle",
        props: props,
        data() {
            return {
                checked: this.isChecked
            }
        },
        methods: {
            request(url, data, callback) {
                $.ajax({
                    type: this.method,
                    url: url,
                    data: data,
                    headers: {
                        'Authorization': 'Bearer ' + this.authToken
                    },
                    complete: function (response, status) {
                        if (this.callback) this.callback(response, status)
                    }
                })

            },
            activate() {
                var data = {};

                if (this.activateData) {
                    data = Object.assign(this.data, this.activateData);
                } else if (this.data) {
                    data = this.data;
                } else {
                    data = {};
                }

                this.request(this.activateUrl, data);
            },
            deactivate() {
                var data = {};

                if (this.activateData) {
                    data = Object.assign(this.data, this.deactivateData);
                } else if (this.data) {
                    data = this.data;
                } else {
                    data = {};
                }

                this.request(this.deactivateUrl, data)
            },
            onChange() {
                if (this.checked) {
                    this.deactivate();
                } else {
                    this.activate();
                }
            }
        }
    }
</script>
