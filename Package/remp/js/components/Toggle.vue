<template>
    <div class="toggle-switch" :class="{ disabled: disabled }">
        <input  :id="id"
                :name="name"
                value="1"
                :checked="checked"
                type="checkbox"
                hidden="hidden">
        <label :for="id" class="ts-helper" @click.prevent="onChange"></label>
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
        isDisabled: {
            type: Boolean,
            default: false
        },
        isChecked: {
            type: Boolean,
            default: false
        },

        authToken: String,
        method: String,
        toggleUrl: String,
        toggleData: Object,
        callback: Function
    };

    export default {
        name: "toggle",
        props: props,
        data() {
            return {
                checked: this.isChecked,
                disabled: this.isDisabled
            }
        },
        methods: {
            request(url, data) {
                var self = this;

                $.ajax({
                    type: this.method,
                    url: url,
                    data: data,
                    headers: {
                        'Authorization': 'Bearer ' + self.authToken
                    },
                    complete: function (response, status) {
                        self.disabled = false;

                        if (response.status == 200) {
                            if (response.responseJSON.active == true) {
                                self.checked = true;
                            } else {
                                self.checked = false;
                            }
                        }

                        if (self.callback) self.callback(response, status)
                    }
                })

            },
            toggle() {
                if (this.toggleData) {
                    this.request(this.toggleUrl, this.toggleData);
                } else {
                    this.request(this.toggleUrl, {});
                }
            },
            onChange() {
                if (!this.disabled) {
                    this.disabled = true;
                    this.toggle();
                }
            }
        }
    }
</script>
