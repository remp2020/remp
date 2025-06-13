<template>
    <div class="card c-dark palette-Amber bg">
        <div class="cw-current media">
            <div class="pull-left zmdi zmdi-graphic-eq m-t-10 p-r-0" style="font-size: 4em;"></div>
            <div class="cwc-info media-body">
                <div class="cwci-temp">{{ title }}</div>
                <ul class="cwci-info">
                    <li>campaign</li>
                </ul>
            </div>
        </div>

        <div class="card-body">
            <canvas :id="name" width="300" height="100"></canvas>

            <div class="list-group lg-alt lg-even-white">
                <card-row v-for="variant in variants" :key="variant.id" :variant="variant" :type="type"></card-row>
            </div>
        </div>

    </div><!-- .card -->
</template>

<script>
    import CardRow from './CardRow'

    let props = {
        name: {
            type: String,
            required: true
        },
        title: {
            type: String,
            required: true
        },
        interval: {
            type: String,
            required: true
        },
        type: {
            type: String,
            required: true
        },
        id: {
            type: Number,
            required: true
        },
        variants: {
            type: Array,
            required: true
        }
    }

    export default {
        components: {
            CardRow
        },
        props: props,
        data() {
            return {
                loaded: false,
                labels: null,
                data: null
            }
        },
        created() {
            this.load()
        },
        methods: {
            load() {
                var vm = this;

                $.ajax({
                    method: 'POST',
                    url: '/campaigns/' + vm.id + '/stats/histogram',
                    data: {
                        type: vm.type,
                        interval: vm.interval,
                        _token: document.head.querySelector("[name=csrf-token]").content
                    },
                    dataType: 'JSON',
                    success(data, stats) {
                        vm.loaded = true;

                        vm.init(data.dataSets)
                    }
                })
            },
            init(dataSets) {
                var ctx = document.getElementById(this.name).getContext('2d');

                var myLineChart = new Chart(ctx, {
                    type: "line",
                    data: dataSets,
                    options: {
                        elements: {
                            point: {
                                radius: 0,
                                hitRadius: 3
                            }
                        },
                        layout: {
                            padding: -20
                        },
                        scales: {
                            x: {
                                ticks: {
                                    display: false
                                },
                                gridLines: {
                                    display: false,
                                    // color: "rgba(255, 255, 255, 1)",
                                    color: "rgba(0, 0, 0, 0)",
                                    lineWidth: 0
                                },
                            },
                            y: {
                                ticks: {
                                    display: false,
                                },
                                gridLines: {
                                    display: false,
                                    // color: "rgba(255, 255, 255, 1)",
                                    color: "rgba(0, 0, 0, 0)",
                                    lineWidth: 0
                                },
                            },
                        },
                        legend: {
                            display: false
                        }
                    }
                });
            }
        }
    }
</script>
