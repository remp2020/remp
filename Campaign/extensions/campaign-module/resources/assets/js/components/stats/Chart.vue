<template>
    <div class="card card-chart">
        <div v-if="loading" class="preloader-wrapper">
            <div class="preloader pl-xxl">
                <svg class="pl-circular" viewBox="25 25 50 50">
                    <circle class="plc-path" cx="50" cy="50" r="20" />
                </svg>
            </div>
        </div>
        <div v-if="error" class="error" :title="error">!</div>

        <div class="card-header">
            <h3 v-html="title" class="pull-left"></h3>
            <a v-if="editLink" :href="editLink" class="btn btn-sm palette-Cyan bg waves-effect pull-right">
                <i class="zmdi zmdi-palette-Cyan zmdi-edit"></i>&nbsp;&nbsp;Edit
            </a>
        </div>

        <div class="card-body card-padding">
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
        editLink: {
            type: String,
            required: false
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
    };
    export default {
        props: props,
        watch: {
            chartData(newChartData) {

                let labels = newChartData.labels;
                let dataSets = newChartData.dataSets;

                // Fix of Chart.js bug of overlapping y-axis with first value on the x-axis
                // Currently (2020/04), Chart.js doesn't support adding padding between data and axis
                // Hacky solution is to insert dummy date fillers having 0 values at the beginning and end of data array,
                // so the actual data is not hidden.
                // See problem: https://stackoverflow.com/questions/30697292/chart-js-spacing-and-padding
                if (labels.length > 1) {
                    let firstDate = moment(labels[0]);
                    let secondDate = moment(labels[1]);
                    let intervalSecs = Math.abs(firstDate.diff(secondDate, "seconds"));

                    // compute filler labels so they have same time distance from the actual data as data points between themselves
                    let leftFiller = firstDate.subtract(intervalSecs, 's').utc().format();
                    let rightFiller = moment(labels[labels.length-1]).add(intervalSecs, 's').utc().format();

                    // add filler labels
                    labels.unshift(leftFiller);
                    labels.push(rightFiller);

                    // add filler data
                    for (const dataSet of dataSets) {
                        dataSet.data.unshift(0);
                        dataSet.data.push(0);
                    }
                }

                this.setChartData(dataSets, labels, newChartData.timeUnit)
            }
        },
        mounted() {
            if (this.chartData.hasOwnProperty("dataSets")) {
                this.setChartData(this.chartData.dataSets, this.chartData.labels, this.chartData.timeUnit)
            }
        },
        methods: {
            setChartData(dataSets, labels, timeUnit) {
                if (this.chart != null) {
                    this.chart.config.data = {
                        labels: labels,
                        datasets: dataSets,
                        timeUnit: timeUnit,
                    };
                    this.chart.config.options.scales.xAxes[0].time.unit = timeUnit;
                    this.chart.update();
                    return;
                }

                let ctx = document.getElementById(this.name).getContext('2d');

                this.chart = new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: labels,
                        datasets: dataSets,
                        timeUnit: timeUnit,
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
                            x: {
                                type: 'time',
                                distribution: 'series',
                                time: {
                                    unit: timeUnit,
                                    displayFormats: {
                                        minute: 'HH:mm',
                                        hour: 'HH:mm',
                                    },
                                    tooltipFormat: "LL",
                                },
                                ticks: {
                                    maxRotation: 0,
                                    minRotation: 0,
                                },
                            },
                            y: {
                                ticks: {
                                    beginAtZero: true,
                                },
                            },
                        },
                        tooltips: {
                            callbacks: {
                                title: function(tooltipItem, chartData) {
                                    // we have only on x axis, we can use zero item directly
                                    if (chartData.timeUnit === 'day' || chartData.timeUnit === 'week') {
                                        return moment(tooltipItem[0].xLabel).format('LL');
                                    }
                                    return moment(tooltipItem[0].xLabel).format('LLL');
                                }
                            }
                        }
                    }
                });
            }
        }
    }
</script>

<style scoped>
    h3 {
        margin-top: 0;
    }

    canvas {
        width: 100%;
    }

    .error {
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
