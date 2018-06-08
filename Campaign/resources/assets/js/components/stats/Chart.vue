<style scoped>
    .card {
        padding: 30px;
    }

    h3 {
        margin-top: 0;
    }

    canvas {
        width: 100%;
    }

    .stats-error {
        position: absolute;
        top: 0;
        right: 0;
        width: 20px;
        height: 20px;
        background: red;
        color: #fff;
        display: none;
    }

    .preloader-wrapper {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: none;
    }

    .preloader-wrapper .preloader {
        position: absolute;
        top: 50%;
        left: 50%;
        margin-left: -20px;
        margin-top: -20px;
    }
</style>

<template>
    <div class="card">
        <div class="preloader-wrapper">
            <div class="preloader pl-xxl">
                <svg class="pl-circular" viewBox="25 25 50 50">
                    <circle class="plc-path" cx="50" cy="50" r="20" />
                </svg>
            </div>
        </div>
        <div class="stats-error">!</div>
        <h3>{{ title }}</h3>

        <div class="card-body">
            <canvas :id="name" :height="height"></canvas>
        </div>

    </div><!-- .card -->
</template>

<script>
    let props = {
        url: {
            type: String,
            required: true
        },
        name: {
            type: String,
            required: true
        },
        title: {
            type: String,
            required: true
        },
        from: {
            type: String,
            required: true
        },
        to: {
            type: String,
            required: true
        },
        height: {
            type: Number,
            required: true
        }
    }

    export default {
        props: props,
        data() {
            return {
                loaded: false,
                labels: null,
                data: null
            }
        },
        mounted() {
            this.load()
        },
        watch: {
            from() {
                this.load();
            },
            to() {
                this.load();
            }
        },
        methods: {
            load() {
                var vm = this;

                $(this.$el).find('.preloader-wrapper').show();

                $.ajax({
                    method: 'POST',
                    url: vm.url,
                    data: {
                        from: vm.from,
                        to: vm.to,
                        chartWidth: $('#' + this.name).width(),
                        _token: document.head.querySelector("[name=csrf-token]").content
                    },
                    dataType: 'JSON',
                    success(data, stats) {
                        vm.loaded = true;

                        $(vm.$el).find('.preloader-wrapper').fadeOut();

                        vm.init(data.dataSets, data.labels)
                    }
                })
            },
            init(dataSets, labels) {
                var ctx = document.getElementById(this.name).getContext('2d');

                var myLineChart = new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: labels,
                        datasets: dataSets
                    },
                    options: {
                        maintainAspectRatio: true,
                        elements: {
                            point: {
                                radius: 0,
                                hitRadius: 3
                            }
                        },
                        layout: {
                            padding: {
                                right: 30
                            }
                        },
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }
                    }
                });
            }
        }
    }
</script>
