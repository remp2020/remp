<template>
    <div id="chartContainer" >
        <div class="card card-chart">
            <div class="card-header">
                <h2>Sessions per browser</h2>
            </div>
            <div class="card-body card-padding">
                <div v-if="error" class="error" :title="error"></div>
                <div class="chartContainer">

                    <button-switcher :options="[
                            {text: '7 days', value: '7days'},
                            {text: '30 days', value: '30days'}
                        ]"
                        v-model="interval">
                    </button-switcher>

                    <div v-if="loading" class="preloader pls-purple">
                        <svg class="pl-circular" viewBox="25 25 50 50">
                            <circle class="plc-path" cx="50" cy="50" r="20"></circle>
                        </svg>
                    </div>

                    <div class="m-t-20 m-b-20" v-if="tagLabels">
                        <div v-bind:style="{color: item.color}" v-for="(item, tag) in tagLabels">
                            <b>{{tag}}</b>: {{item.label}}
                        </div>
                    </div>

                    <div :id="svgContainerId" :ref="svgContainerId" style="height: 200px" class="google-analytics-reporting-chart">
                        <svg style="z-index: 10" :id="svgId" :ref="svgId"></svg>
                    </div>

                    <div class="legend-wrapper">
                        <div v-if="highlightedRow" v-show="legendVisible" v-bind:style="{ left: legendLeft }" class="google-analytics-reporting-graph-legend">

                            <span>{{highlightedRow.startDate | formatDate(data.intervalMinutes)}}</span>
                            <table>
                                <tr>
                                    <th>Source</th>
                                    <th>Value</th>
                                </tr>
                                <tr v-for="item in highlightedRow.values">
                                    <td>
                                        <span style="font-weight: bold" v-bind:style="{color: item.color}">&#9679;</span>&nbsp;
                                        <span v-if="item.tag==''">Uncategorized</span>
                                        <span v-else style="text-transform: capitalize">{{item.tag}}</span>
                                    </td>
                                    <td>{{item.value}}</td>
                                </tr>
                                <tr style="border-top: 1px solid #d1d1d1">
                                    <td><b>Total</b></td>
                                    <td><b>{{highlightedRow.sum}}</b></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .chartContainer {
        position: relative;
    }

    .legend-wrapper {
        position: relative;
        height:0;
    }
    .google-analytics-reporting-graph-legend table {
        width: 100%;
        background-color: transparent;
        border-collapse: collapse;
    }
    .google-analytics-reporting-graph-legend table td, th {
        padding: 3px 6px
    }
    .google-analytics-reporting-graph-legend {
        position:absolute;
        z-index: 1000;
        top:0;
        left: 0;
        opacity: 0.85;
        color: #fff;
        padding: 2px;
        background-color: #494949;
        border-radius: 2px;
        border: 2px solid #494949;
        transform: translate(-50%, 0px)
    }

    .chartContainer .preloader {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%)
    }
</style>

<script>
    import ButtonSwitcher from './ButtonSwitcher.vue'
    import {debounce, formatInterval} from './constants'
    import axios from 'axios'
    import * as d3 from 'd3'

    const props = {
        url: {
            type: String,
            required: true
        },
        urlParams: {
            type: Object,
            default: function() {
                return {}
            }
        },
        stacked: {
            type: Boolean,
            default: true
        }
    }

    Vue.filter('formatDate', formatInterval)

    function stackMax(layer) {
        return d3.max(layer, function (d) { return d[1]; });
    }

    const bisectDate = d3.bisector(d => d.date).left;

    export default {
        components: {
            ButtonSwitcher
        },
        name: 'google-analytics-reporting-chart',
        props: props,
        data() {
            return {
                data: null,
                tagLabels: null,
                loading: false,
                interval: '7days',
                legendVisible: false,
                highlightedRow: null,
                legendLeft: "0px"
            };
        },
        watch: {
            data(val) {
                this.fillData()
            },
            interval(value) {
                this.reload()
            }
        },
        computed: {
            svgContainerId() {
                return "svg-container-" + this._uid
            },
            svgId() {
                return "svg-" + this._uid
            }
        },
        created() {
            this.vars = {
                container: null,
                svg: null,
                dataG: null,
                x: null,
                y: null,
                colorScale: null,
                xAxis: null,
                vertical: null,
                mouseRect: null,
                margin: {
                    top: 20,
                    right: 20,
                    bottom: 20,
                    left: 20
                },
            }
        },
        mounted() {
            this.createGraph()
            this.loadData()
            window.addEventListener('resize', debounce((e) => {
                this.fillData()
            }, 100));
        },
        methods: {
            reload() {
                this.loadData()
            },
            createGraph() {
                this.vars.container = this.$refs[this.svgContainerId]
                let outerWidth = this.vars.container.clientWidth,
                    outerHeight = this.vars.container.clientHeight,
                    width = outerWidth - this.vars.margin.left - this.vars.margin.right,
                    height = outerHeight - this.vars.margin.top - this.vars.margin.bottom

                this.vars.svg = d3.select(this.$refs[this.svgId])
                    .attr("width", outerWidth)
                    .attr("height", outerHeight)
                    .append("g")
                    .attr("transform", "translate(" + this.vars.margin.left + "," + this.vars.margin.top + ")");

                this.vars.dataG = this.vars.svg.append("g").attr("class", "data-g")

                this.vars.x = d3.scaleTime().range([0, width])
                this.vars.y = d3.scaleLinear().range([height, 0])

                this.vars.xAxis = d3.axisBottom(this.vars.x).ticks(5)

                let gX = this.vars.svg.append("g")
                    .attr("transform", "translate(0," + height + ")")
                    .attr("class", "axis axis--x")
                    .call(this.vars.xAxis)

                // Mouse events
                let mouseG = this.vars.svg.append("g")
                    .attr("class", "mouse-over-effects");

                this.vars.vertical = mouseG.append("path")
                    .attr("class", "mouse-line")
                    .style("stroke", "black")
                    .style("stroke-width", "1px")
                    .style("opacity", "0");

                let that = this
                // append a rect to catch mouse movements on canvas
                this.vars.mouseRect = mouseG.append('svg:rect')
                    .attr('width', width)
                    .attr('height', height)
                    .attr('fill', 'none')
                    .attr('pointer-events', 'all')
                    .on('mouseout', function() {
                        that.legendVisible = false
                        that.vars.vertical
                            .style("opacity", "0");
                    })
                    .on('mouseover', function() {
                        that.legendVisible = true
                        that.vars.vertical
                            .style("opacity", "1");
                    })
                    .on('mousemove', function() {
                        if (that.data !== null) {
                            let mouse = d3.mouse(this);
                            const xDate = that.vars.x.invert(mouse[0])
                            that.highlightRow(xDate, height)
                        }
                    })
            },
            highlightRow(xDate, height) {
                let rowIndex = bisectDate(this.data.results, xDate);

                let rowRight = this.data.results[rowIndex]
                let rowLeft = this.data.results[rowIndex - 1]
                let row = rowRight

                // Find out which value is closer
                if (rowLeft !== undefined) {
                    const xDateMillis = moment(xDate).valueOf()
                    const leftDateMillis = moment(rowLeft.date).valueOf()
                    const rightDateMillis = moment(rowRight.date).valueOf()

                    if ((xDateMillis - leftDateMillis) < (rightDateMillis - xDateMillis)){
                        row = rowLeft
                    }
                }

                let verticalX = this.vars.x(row.date)

                this.vars.vertical
                    .attr("d", function() {
                        let d = "M" + verticalX + "," + height;
                        d += " " + verticalX + "," + 0;
                        return d;
                    })

                let valuesSum = 0

                let that = this

                let values = this.data.tags.map(function (tag) {
                    valuesSum += row[tag]
                    return {
                        tag: tag,
                        value: row[tag],
                        color: d3.color(that.vars.colorScale(tag)).hex()
                    }
                })

                this.highlightedRow = {
                    startDate: row.date,
                    values: values,
                    sum: valuesSum
                }

                this.legendLeft = (Math.round(this.vars.x(row.date)) + this.vars.margin.left) + "px"

            },
            fillData() {
                if (this.data === null){
                    return
                }
                let results = this.data.results,
                    tags = this.data.tags,
                    colors = this.data.colors

                let outerWidth = this.vars.container.clientWidth,
                    outerHeight = this.vars.container.clientHeight,
                    width = outerWidth - this.vars.margin.left - this.vars.margin.right,
                    height = outerHeight - this.vars.margin.top - this.vars.margin.bottom

                this.vars.svg.attr("width", outerWidth)
                    .attr("height", outerHeight)

                this.vars.mouseRect.attr("width", width)
                    .attr("height", height)

                // Recompute data depending on graph type
                let layers = [], yMax = 0
                if (this.stacked) {
                    let stack = d3.stack()
                        .keys(tags)
                        .offset(d3.stackOffsetNone)

                    layers = stack(results);
                    yMax = d3.max(layers, stackMax)
                } else {
                    for (let j = 0; j < tags.length; j++) {
                        layers[j] = []
                        layers["key"] = tags[j]
                    }

                    for (let i = 0; i < results.length; i++) {
                        for (let j = 0; j < tags.length; j++) {
                            let value = results[i][tags[j]]
                            yMax = Math.max(yMax, value)
                            layers[j].push({
                                date: results[i].date,
                                y: value
                            })
                        }
                    }
                }

                this.vars.x.domain([results[0].date, results[results.length - 1].date])
                    .range([0, width])
                this.vars.y.domain([0, yMax])
                    .range([height, 0])

                this.vars.colorScale = d3.scaleOrdinal()
                    .domain(tags)
                    .range(colors);

                // Remove original data if present
                this.vars.dataG.selectAll(".layer").remove();
                this.vars.dataG.selectAll(".layer-line").remove();

                // Update data
                if (this.stacked) {
                    let area = d3.area()
                        .curve(d3.curveMonotoneX)
                        .x((d, i) => this.vars.x(d.data.date))
                        .y0((d) => this.vars.y(d[0]))
                        .y1((d) => this.vars.y(d[1]))

                    this.vars.dataG.selectAll(".layer")
                        .data(layers)
                        .enter().append("g")
                        .attr("class", "layer")
                        .append("path")
                        .attr("d", area)
                        .attr("fill", (d, i) => colors[i])
                }

                let areaStroke = d3.line()
                    .curve(d3.curveMonotoneX)

                if (this.stacked) {
                    areaStroke
                        .x((d, i) => this.vars.x(d.data.date))
                        .y((d) => this.vars.y(d[1]))
                } else {
                    areaStroke
                        .x((d, i) => this.vars.x(d.date))
                        .y((d) => this.vars.y(d.y))
                }

                this.vars.dataG.selectAll(".layer-line")
                    .data(layers)
                    .enter().append("g")
                    .attr("class", "layer-line")
                    .append("path")
                    .attr("d", areaStroke)
                    .attr("stroke", this.stacked ? "#626262" : (d, i) => colors[i])
                    .attr("opacity", this.stacked ? 0.25 : 1)
                    .attr("shape-rendering", "geometricPrecision")
                    .attr("fill", "none")

                // Update axis
                this.vars.svg.select('.axis--x').transition().call(this.vars.xAxis)
            },
            loadData() {
                this.loading = true
                axios
                    .get(this.url, {
                        params: Object.assign({
                            tz: Intl.DateTimeFormat().resolvedOptions().timeZone,
                            interval: this.interval,
                        }, this.urlParams)
                    })
                    .then(response => {
                        this.loading = false
                        const tags = response.data.tags
                        const data = response.data.results

                        let parseData = function (d) {
                            let dataObject = {
                                date: d3.isoParse(d.Date)
                            };
                            tags.forEach(function (s) {
                                dataObject[s] = +d[s];
                            })
                            return dataObject;
                        }

                        this.data = {
                            results: data.map(parseData),
                            tags: tags,
                            colors: response.data.colors,
                            intervalMinutes: response.data.intervalMinutes
                        }
                        this.tagLabels = response.data.tagLabels || null
                    })
                    .catch(error => {
                        this.error = error
                        this.loading = false;
                    })
            },
        }
    }
</script>
