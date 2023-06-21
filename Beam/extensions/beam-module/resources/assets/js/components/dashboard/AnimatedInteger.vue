<template>
    <span>{{ tweeningValue | formatNumber }}</span>
</template>

<script>
    import Tween from '@tweenjs/tween.js';
    import '../../filters';

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
                    if (Tween.update()) {
                        requestAnimationFrame(animate)
                    }
                }

                new Tween.Tween({ tweeningValue: startValue })
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


