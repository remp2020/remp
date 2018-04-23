<template>
    <div class="ab-testing">
        <div id="slider" class="m-b-25 m-t-25"></div>

        <table class="table table-striped">
            <tbody>
                <tr v-for="(variant, i) in variants" :key="i">
                    <td style="width: 43px;" :class="['color-' + i]">&nbsp;</td>
                    <td>{{ variant.name }}</td>
                    <td style="text-align: right;">
                        <input type="text" :class="['ab-testing-input', 'ab-testing-input-' + i]" name="asd" :value="variant.val" @change="handleInputUpdate(this.event, i)" id="">&nbsp;&nbsp;%
                    </td>
                </tr>
            </tbody>
        </table>


    </div>
</template>

<script type="text/javascript">
    export default {
        data() {
            return {
                sliderEl: null,
                dontRunSliderUpdate: true,
                variants: [
                    {
                        name: "Variant A",
                        val: 30
                    },
                    {
                        name: "Variant B",
                        val: 30
                    },
                    {
                        name: "Variant C",
                        val: 20
                    },
                    {
                        name: "Control Group",
                        val: 20
                    },
                ]
            };
        },
        methods: {
            handleSliderUpdate(values, handle) {
                var sum = 0;

                for (var ii = 0; ii < values.length; ii++) {
                    var val = parseInt(values[ii]);

                    if (ii == 0) {
                        this.variants[ii].val = val;
                        sum += val;
                    } else {
                        this.variants[ii].val = parseInt(val-values[ii-1]);
                        sum += parseInt(val-values[ii-1]);
                    }
                }

                this.variants[this.variants.length-1].val = 100-sum;
            },
            handleInputUpdate(event, i) {
                var a = Array(this.variants.length).fill(null),
                    prevInputsSum = 0,
                    sum = 0;


                // sum all previous inputs & all inputs
                for(var ii = 0; ii < this.variants.length; ii++) {
                    var els = document.getElementsByClassName('ab-testing-input-' + ii);

                    if (els.length) {
                        sum += parseInt(els[0].value)
                        prevInputsSum += (ii < i) ? parseInt(els[0].value) : 0;
                    }
                }

                // handle control group input
                if (this.variants.length - 1 === i) {
                    a[i-1] = prevInputsSum + (100 - prevInputsSum - parseInt(event.srcElement.value));

                // handle regular variant input
                } else {
                    a[i] = prevInputsSum+parseInt(event.srcElement.value)
                }

                this.sliderEl.noUiSlider.set(a)
            }
        },
        mounted() {
            var starts  = [],
                sum     = 0;

            // create slider handle starts from variants data
            for(var ii = 0; ii < this.variants.length - 1; ii++) {
                sum += this.variants[ii].val;
                starts.push(sum);
            }

            // create slider
            this.sliderEl = document.getElementById('slider');
            noUiSlider.create(this.sliderEl, {
                start: starts,
                range: {
                    min: 0,
                    max: 100
                },
                step: 1,
                connect: Array(this.variants.length).fill(true)
            });

            // set slider connects color
            var connects = slider.querySelectorAll('.noUi-connect');
            for (var i = 0; i < connects.length; i++) {
                connects[i].classList.add('color-' + i);
            }

            // bind events
            this.sliderEl.noUiSlider.on('update', this.handleSliderUpdate);
        }
    }
</script>

<style>
.ab-testing {
    max-width: 600px;
    padding: 20px 40px;
}

.ab-testing-input {
    max-width: 40px;
    text-align: center;
}

.ab-testing .color-0 {
    background: #E37B40;
}

.ab-testing .color-1 {
    background: #46B29D;
}

.ab-testing .color-2 {
    background: #DE5B49;
}

.ab-testing .color-3 {
    background: #324D5C;
}

.ab-testing .color-4 {
    background: #F0CA4D;
}
</style>

