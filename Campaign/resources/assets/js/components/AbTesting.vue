<template>
    <div class="ab-testing">
        <div class="row">
            <div class="col-md-12">

                <div id="slider" class="m-b-25 m-t-25"></div>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Banner</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <ab-testing-variant
                            v-for="(variant, index) in variants"
                            :index="index"
                            :variant="variant"
                            :key="variant.index"
                        ></ab-testing-variant>
                    </tbody>
                </table>

                <input type="hidden" v-for="variant in variantsToRemove" :value="variant.id" :key="variant.id" name="variants_to_remove[]">
            </div>
        </div><!-- .row -->

    </div>
</template>

<script type="text/javascript">
    import AbTestingVariant from "./AbTestingVariant";

    export default {
        components: {
            AbTestingVariant
        },
        props: {
            variantOptions: null,
            variants: {
                default: [
                    {
                        name: "Variant B",
                        val: 30
                    }
                ]
            },
            bannerId: {
                default: null
            }
        },
        data() {
            return {
                sliderEl: null,
                dontRunSliderUpdate: true,
                variantsToRemove: [],
                variantNumber: 0
            };
        },
        created() {
            if (!this.variants.length) {
                this.variants.push({
                    id: null,
                    variant: "Primary banner",
                    proportion: 100,
                    control_group: 0,
                    banner_id: this.bannerId
                });

                this.variants.push({
                    id: null,
                    variant: 'Control Group',
                    proportion: 0,
                    control_group: 1,
                    banner_id: null
                })
            }

            this.variantNumber = this.variants.length;
        },
        mounted() {
            // calculate slider starts
            var starts = this.calculateStarts();

            // get slider element
            this.sliderEl = document.getElementById('slider');

            // render slider ui
            this.renderSlider(starts);
        },
        methods: {
            calculateStarts() {
                var starts  = [],
                    sum     = 0;

                // calc slider handle starts from variants data
                for(var ii = 0; ii < this.variants.length - 1; ii++) {
                    sum += this.variants[ii].proportion;
                    starts.push(sum);
                }

                return starts;
            },
            renderSlider(starts) {
                // destroy slider if exists
                if (this.sliderEl.noUiSlider) {
                    this.sliderEl.noUiSlider.destroy();
                }

                // bind noUiSlider
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

                this.$children[0].renderProportionInputValues();

                // bind events
                this.sliderEl.noUiSlider.on('update', this.handleSliderUpdate);
            },
            handleSliderUpdate(values, handle) {
                var sum = 0;

                // update variants proportions
                for (var ii = 0; ii < values.length; ii++) {
                    var val = parseInt(values[ii]);

                    if (ii == 0) {
                        this.variants[ii].proportion = val;
                        sum += val;
                    } else {
                        this.variants[ii].proportion = parseInt(val-values[ii-1]);
                        sum += parseInt(val-values[ii-1]);
                    }
                }

                // update control group proportion
                this.variants[this.variants.length-1].proportion = 100-sum;
            },
            // noUiSlider needs exact position to set slider handle
            // so we have to calculate it using our percentual variant proportions
            // -> this means set handle to sum of all previous + this variant proporion
            // control group doesn't have handle so its treated independently
            handleInputUpdate(event, i) {
                let a = Array(this.variants.length).fill(null),
                    prevInputsSum = 0,
                    sum = 0;


                // sum all previous inputs & all inputs
                for(let ii = 0; ii < this.variants.length; ii++) {
                    var els = document.getElementsByClassName('ab-testing-input-' + ii);

                    if (els.length) {
                        sum += parseInt(els[0].value);
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

                // set slider positions
                this.sliderEl.noUiSlider.set(a)
            },
            addEmptyVariant: function (event) {
                // lower proportion of the highest-proportion variant to compensate for new variant
                let max = 0;
                let maxIdx = 0;
                this.variants.forEach((variant, idx) => {
                    if (variant.proportion > max) {
                        max = variant.proportion;
                        maxIdx = idx;
                    }
                })
                this.variants[maxIdx].proportion = this.variants[maxIdx].proportion-10;

                // add empty variant before control group
                this.variants.splice(this.variants.length - 1, 0, {
                    id: null,
                    variant: "Variant " + this.variantNumber,
                    proportion: 10,
                    control_group: 0,
                    banner_id: null
                });

                this.variantNumber++;

                this.variants[0].banner_id = this.bannerId;

                setTimeout(() => {
                    this.renderSlider(this.calculateStarts());
                }, 50);

                if (event) {
                    event.preventDefault();
                }
            },
            removeVariant: function (event, i, id) {
                this.variants.splice(i, 1);

                this.variantsToRemove.push({id: id});

                setTimeout(() => {
                    this.renderSlider(this.calculateStarts());
                }, 50);

                event.preventDefault();
            },
            getVariantOptionByValue: function (id) {
                for (let ii = this.variantOptions.length - 1; ii >= 0; ii--) {
                    if (this.variantOptions[ii].value == id) {
                        return this.variantOptions[ii];
                    }
                }

                return null;
            }
        },
        watch: {
            bannerId: {
                handler: function (newValue) {
                    this.variants[0].banner_id = newValue;
                },
                deep: true,
            }
        }
    }
</script>

<style>
.ab-testing {
    padding: 20px 40px;
}

.ab-testing-input {
    max-width: 40px;
    text-align: center;
    display: inline-block;
}

.table-td-color {
    width: 20px;
}

.table > tbody > tr > td.table-td-actions {
    width: 50px;
    vertical-align: bottom;
}

.table-td-color > div {
    width: 20px;
    height: 20px;
}

.noUi-connect.color-0,
.table-td-color.color-0 > div {
    background: #E37B40;
}

.noUi-connect.color-1,
.table-td-color.color-1 > div {
    background: #46B29D;
}

.noUi-connect.color-2,
.table-td-color.color-2 > div {
    background: #DE5B49;
}

.noUi-connect.color-3,
.table-td-color.color-3 > div {
    background: #324D5C;
}

.noUi-connect.color-4,
.table-td-color.color-4 > div {
    background: #F0CA4D;
}


.table-td-button {
    width: 27px;
}

.table-td-button > button {
    width: 25px;
    height: 25px;
    padding: 0;
}


.table > tbody > tr > td:first-child {
    padding-left: 20px;
}

.table > tbody > tr > td:last-child {
    padding-right: 20px;
}

.table > tbody > tr:last-child > td, .table > tfoot > tr:last-child > td {
    padding-bottom: 15px;
}

html input[disabled] {
    background: none;
    border: none;
}
</style>

