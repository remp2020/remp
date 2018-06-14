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
        text-align: center;
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
        <div v-if="loading" class="preloader-wrapper">
            <div class="preloader pl-xxl">
                <svg class="pl-circular" viewBox="25 25 50 50">
                    <circle class="plc-path" cx="50" cy="50" r="20" />
                </svg>
            </div>
        </div>
        <div v-if="error" class="stats-error" :title="error">!</div>
        <h3>{{ title }}</h3>

        <div class="card-body">
            <canvas :id="name" :height="height"></canvas>
        </div>

    </div><!-- .card -->
</template>

<script>
    let props = {
        name: {
            type: String,
            required: true
        },
        title: {
            type: String,
            required: true
        },
        height: {
            type: Number,
            required: true
        },
        loading: {
            type: Boolean,
            default: true
        },
        error: {
            type: String
        },
        chartData: {
            type: Object,
            required: true
        }
    }
    export default {
        props: props,
        watch: {
            chartData(newChartData) {
                this.setChartData(newChartData.dataSets, newChartData.labels)
            }
        },
        mounted() {
            if (this.chartData.hasOwnProperty("dataSets")) {
                this.setChartData(this.chartData.dataSets, this.chartData.labels)
            }
        },
        methods: {
            setChartData(dataSets, labels) {
                if (this.chart != null) {
                    this.chart.config.data = {
                        labels: labels,
                        datasets: dataSets
                    }
                    this.chart.update();
                    return;
                }

                var ctx = document.getElementById(this.name).getContext('2d');

                this.chart = new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: labels,
                        datasets: dataSets
                    },
                    options: {
                        responsive: true,
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
                            }],
                            xAxes: [{
                                type: 'time',
                                distribution: 'series',
                                time: {
                                    displayFormats: {
                                        minute: 'HH:mm',
                                        hour: 'HH:mm',
                                    }
                                },
                                ticks: {
                                    maxRotation: 0,
                                    minRotation: 0,
                                }
                            }]
                        }
                    }
                });
            }
        }
    }
</script>
