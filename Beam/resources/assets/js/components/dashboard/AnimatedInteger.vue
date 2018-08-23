<template>
    <span>{{ tweeningValue }}</span>
</template>

<script>
    let TWEEN = require('@tweenjs/tween.js');

    export default {
        name: "animated-integer",
        props: {
            value: {
                type: Number,
                required: true
            }
        },
        data: function () {
            return {
                tweeningValue: 0
            }
        },
        watch: {
            value: function (newValue, oldValue) {
                this.tween(oldValue, newValue)
            }
        },
        mounted: function () {
            this.tween(0, this.value)
        },
        methods: {
            tween: function (startValue, endValue) {
                let vm = this
                function animate () {
                    if (TWEEN.update()) {
                        requestAnimationFrame(animate)
                    }
                }

                new TWEEN.Tween({ tweeningValue: startValue })
                    .to({ tweeningValue: endValue }, 500)
                    .onUpdate(function (object) {
                        vm.tweeningValue = object.tweeningValue.toFixed(0)
                    })
                    .start()

                animate()
            }
        }
    }
</script>


