<template>
    <div class="dtp-container fg-line">
        <label :for="labelId" class="fg-label">{{ label }}</label>
        <input class="form-control date-time-picker" :name="labelId" :id="labelId" type="datetime">
    </div>
</template>

<script type="text/javascript">
    export default {
        name: "DateTimePicker",
        props: ['label', 'value'],
        data() {
            return {
                labelId: null
            }
        },
        created() {
            this.labelId = "dtp-input-" + this._uid
        },
        mounted() {
            let datetime = $("#" + this.labelId)
            let that = this

            datetime.on('dp.change', function() {
                let t = $(this).data("DateTimePicker").date();
                that.$emit('input', t ? moment(t).utc().format() : null)
            }).datetimepicker()

            // default date changes DOM directly, therefore doing it in nextTick()
            Vue.nextTick()
                .then(function(){
                    let timeToSet = that.value ? moment(that.value) : moment()
                    datetime.data("DateTimePicker").defaultDate(timeToSet)
                })
        }
    }
</script>