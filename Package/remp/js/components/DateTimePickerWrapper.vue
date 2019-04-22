<template>
    <div class="dtp-container fg-line">
        <label v-if="label" :for="labelId" class="fg-label">{{ label }}</label>
        <input :disabled="isDisabled" class="form-control date-time-picker" :name="labelId" :id="labelId" type="datetime">
    </div>
</template>

<script type="text/javascript">
    export default {
        name: "DateTimePicker",
        props: {
            label: String,
            value: String,
            isDisabled: {
                type: Boolean,
                default: false
            }
        },
        data() {
            return {
                labelId: null,
            }
        },
        created() {
            this.labelId = "dtp-input-" + this._uid
        },
        mounted() {
            let datetime = $("#" + this.labelId)
            let that = this
            datetime.datetimepicker({
                'locale': moment.locale()
            })

            // defaultDate() changes DOM directly, therefore doing it in nextTick()
            Vue.nextTick()
                .then(function(){
                    let timeToSet = that.value ? moment(that.value) : moment()
                    datetime.data("DateTimePicker").defaultDate(timeToSet)
                    datetime.on('dp.change', function(val) {
                        let t = $(this).data("DateTimePicker").date();
                        that.$emit('input', t ? moment(t).utc().format() : null)
                    })
                })
        }
    }
</script>