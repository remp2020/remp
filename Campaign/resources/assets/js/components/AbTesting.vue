<template>
    <div class="ab-testing">
        <div class="row">
            <div class="col-md-12">

                <div id="slider" class="m-b-25 m-t-25"></div>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Variant name</th>
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
            }
        },
        data() {
            return {
                sliderEl: null,
                dontRunSliderUpdate: true
            };
        },
        created() {
            if (!this.variants.length) {
                this.addEmptyVariant();
            }
        },
        mounted() {
            var starts  = [],
                sum     = 0;

            // create slider handle starts from variants data
            for(var ii = 0; ii < this.variants.length - 1; ii++) {
                sum += this.variants[ii].proportion;
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
        },
        methods: {
            handleSliderUpdate(values, handle) {
                var sum = 0;

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

                this.variants[this.variants.length-1].proportion = 100-sum;


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
            },
            addEmptyVariant: function (event, index) {
                this.variants.splice(this.variants.length - 1, 0, {
                    'id': null,
                    'name': "Variant" + index,
                    'proportion': 10
                });

                if (event) {
                    event.preventDefault();
                }
            },
            removeVariant: function (event, i) {
                let toRemove = this.variants[i]
                this.variants.splice(i, 1);

                event.preventDefault();
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
}

.table-td-color {
    width: 20px;
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

