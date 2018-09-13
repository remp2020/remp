<template>
    <div class="card card-chart">
        <div v-if="error" class="error" :title="error">!</div>

        <div class="card-header">
            <h4>Page Loads by Traffic Source</h4>
        </div>

        <div id="chartContainer" class="card-body card-padding">
            <button-switcher :options="[
                {text: 'Today', value: 'today'},
                {text: '7 days', value: '7days'},
                {text: '30 days', value: '30days'}]"
                             v-model="interval">
            </button-switcher>

            <div v-if="loading" class="preloader pls-purple">
                <svg class="pl-circular" viewBox="25 25 50 50">
                    <circle class="plc-path" cx="50" cy="50" r="20"></circle>
                </svg>
            </div>

            <chart  style="width: 100%; height:220px;"
                    :class="{hiddenChart: loading}"
                    :auto-resize="true"
                    :options="chartOptions"></chart>
        </div>

    </div>
</template>

<style>
    .histogram-tooltip {
        width: 100%;
        background-color: transparent;
        border-spacing: 4px;
        border-collapse: separate;
    }

    .histogram-tooltip td, th {
        text-align: right;
    }
</style>

<style scoped>
    #chartContainer {
        position: relative;
    }

    #chartContainer .preloader {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%)
    }

    .hiddenChart {
        visibility: hidden;
    }

    .browser text {
        text-anchor: end;
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

<script>
    import axios from 'axios'
    import ButtonSwitcher from './ButtonSwitcher.vue'
    import ECharts from 'vue-echarts/components/ECharts'
    import 'echarts/lib/chart/line'
    import 'echarts/lib/component/tooltip'

    let props = {
        url: {
            type: String,
            required: true
        }
    };

    function trimPrefix(str, prefix) {
        if (str.startsWith(prefix)) {
            return str.slice(prefix.length)
        } else {
            return str
        }
    }

    function generateTooltipTable(aggregated, totalCurrent, totalPrevious) {
        if (totalCurrent === 0) {
            let tooltipHtml = `
                        <table class="histogram-tooltip">
                        <tr>
                            <th></th>
                            <th>7 days ago</th>
                        </tr>`
            for (const [key, item] of aggregated) {
                tooltipHtml += `
                            <tr>
                                <td>${key}</td>
                                <td>${item.previous}</td>
                            </tr>`
            }
            tooltipHtml += `
                            <tr>
                                <th>Total</th>
                                <td>${totalPrevious}</td>
                            </tr>`
            tooltipHtml += "</table>"
            return tooltipHtml
        } else {
            let tooltipHtml = `
                        <table class="histogram-tooltip">
                        <tr>
                            <th></th>
                            <th>Today</th>
                            <th>7 days ago</th>
                        </tr>`
            for (const [key, item] of aggregated) {
                tooltipHtml += '<tr><td>'

                if (item.color !== null) {
                    tooltipHtml += `<span style="font-weight: bold; color:${item.color}">&#9679;</span>`
                }
                tooltipHtml += ` ${key}</td>
                                <td>${item.current}</td>
                                <td>${item.previous}</td>
                            </tr>`
            }
            tooltipHtml += `
                            <tr>
                                <th>Total</th>
                                <td>${totalCurrent}</td>
                                <td>${totalPrevious}</td>
                            </tr>`

            tooltipHtml += "</table>"
            return tooltipHtml
        }
    }

    function getGraphOptions(xAxisLabels, series, colors) {
        return {
            title: {
                text: 'Concurrents by traffic source'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'cross',
                    label: {
                        backgroundColor: '#6a7985'
                    }
                },
                formatter: function (params, ticket, callback) {
                    let aggregated = new Map()
                    let totalCurrent = 0, totalPrevious = 0
                    let colorIndex = 0
                    params.forEach(function(item){
                        if (item.seriesName.startsWith('current_')){
                            let name = trimPrefix(item.seriesName, 'current_')
                            if (!aggregated.has(name)){
                                aggregated.set(name, {
                                    current: 0,
                                    previous: 0,
                                    color: null
                                })
                            }
                            let obj = aggregated.get(name)
                            obj.current = item.data
                            obj.color = colors[colorIndex]
                            colorIndex++
                            totalCurrent += item.data
                        } else {
                            let name = trimPrefix(item.seriesName, 'previous_')
                            if (!aggregated.has(name)){
                                aggregated.set(name, {
                                    current: 0,
                                    previous: 0,
                                    color: null
                                })
                            }
                            let obj = aggregated.get(name)
                            obj.previous = item.data

                            totalPrevious += item.data
                        }
                    })

                    return generateTooltipTable(aggregated, totalCurrent, totalPrevious)
                }
            },
            animation: false,
            toolbox: {
                feature: {
                    saveAsImage: {}
                }
            },
            padding: 0,
            grid: {
                left: '0%',
                right: '0%',
                bottom: '10%',
                containLabel: false
            },
            xAxis: [
                {
                    type: 'category',
                    boundaryGap: false,
                    data: xAxisLabels
                }
            ],
            yAxis: {
                type: 'value',
                splitLine: {
                    show: false
                },
                show: false
            },
            series: series
        }
    }

    let loadDataTimer = null

    export default {
        components: {
            'chart': ECharts,
            'button-switcher': ButtonSwitcher
        },
        props: props,
        data() {
            return {
                loading: true,
                error: null,
                chartOptions: null,
                interval: 'today'
            }
        },
        watch: {
            interval(value) {
                clearInterval(loadDataTimer)
                this.loadData()
                // every 5 minutes
                loadDataTimer = setInterval(this.loadData, 5*60*1000)
            }
        },
        created() {
            this.loadData()
            // every 5 minutes
            loadDataTimer = setInterval(this.loadData, 5*60*1000)
        },
        destroyed() {
            clearInterval(loadDataTimer)
        },
        methods: {
            loadData() {
                this.loading = true
                axios
                    .get(this.url, {
                        params: {
                            'tz': Intl.DateTimeFormat().resolvedOptions().timeZone,
                            'interval': this.interval,
                        }
                    })
                    .then(response => {
                        this.loading = false
                        this.setChartData(response.data.series, response.data.xaxis, response.data.colors)
                    })
                    .catch(error => {
                        this.error = error
                        this.loading = false;
                    })
            },
            setChartData(series, xAxisLabels, colors) {
                this.chartOptions = getGraphOptions(xAxisLabels, series, colors)
            }
        }
    }
</script>