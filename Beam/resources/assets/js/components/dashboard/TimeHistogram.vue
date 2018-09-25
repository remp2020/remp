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

            <div ref="svg-container" style="height: 200px" id="article-chart">
                <svg style="z-index: 10" ref="svg"></svg>
            </div>

            <div id="legend-wrapper">
                <div v-if="highlightedRow" v-show="legendVisible" v-bind:style="{ left: legendLeft }" id="article-graph-legend">

                    <span>{{highlightedRow.startDate | formatDate}}</span>
                    <table>
                        <tr>
                            <th></th>
                            <th v-if="highlightedRow.hasCurrent">Current</th>
                            <th v-if="highlightedRow.hasPrevious">Week ago</th>
                        </tr>
                        <tr v-for="(item, tag) in highlightedRow.values">
                            <td>
                                <span v-if="highlightedRow.hasCurrent"
                                      style="font-weight: bold"
                                      v-bind:style="{color: item.color}">&#9679;</span>&nbsp;
                                {{tag}}
                            </td>
                            <td v-if="highlightedRow.hasCurrent">{{item.current}}</td>
                            <td v-if="highlightedRow.hasPrevious">{{item.previous}}</td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
    #chartContainer {
        position: relative;
    }

    #legend-wrapper {
        position: relative;
        overflow: visible;
        height:0;
    }
    #article-graph-legend table {
        width: 100%;
        background-color: transparent;
        border-spacing: 4px;
        border-collapse: separate;
    }
    #article-graph-legend {
        position:absolute;
        z-index: 1000;
        top:0;
        left: 0;
        opacity: 0.8;
        color: #fff;
        padding: 2px;
        background-color: #b6b6b6;
        border-radius: 2px;
        border: 2px solid #b6b6b6;
    }

    #chartContainer .preloader {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%)
    }
</style>

<script>
    import ButtonSwitcher from './ButtonSwitcher.vue'
    import axios from 'axios'
    import * as d3 from 'd3'

    let props = {
        url: {
            type: String,
            required: true
        }
    };

    Vue.filter('formatDate', function(value) {
        if (value) {
            return moment(value).format('YYYY-MM-DD HH:mm')
        }
    })

    function stackMax(layer) {
        return d3.max(layer, function (d) { return d[1]; });
    }

    const debounce = (fn, time) => {
        let timeout;

        return function() {
            const functionCall = () => fn.apply(this, arguments);

            clearTimeout(timeout);
            timeout = setTimeout(functionCall, time);
        }
    }
    const REFRESH_DATA_TIMEOUT_MS = 30000

    let container, svg, dataG, oldDataG, oldDataLineG, x,y, colorScale, xAxis, vertical, mouseRect,
        margin = {top: 20, right: 20, bottom: 20, left: 20},
        loadDataTimer = null

    export default {
        components: {
            ButtonSwitcher
        },
        name: 'article-chart',
        props: props,
        data() {
            return {
                data: null,
                error: null,
                loading: false,
                interval: 'today',
                legendVisible: false,
                highlightedRow: null,
                legendLeft: "100px"
            };
        },
        computed: {
            hasPrevious() {
                return this.data !== null && this.data.previousResultsSummed.length > 0
            }
        },
        watch: {
            data(val) {
                this.fillData()
            },
            interval(value) {
                clearInterval(loadDataTimer)
                this.loadData()
                loadDataTimer = setInterval(this.loadData, REFRESH_DATA_TIMEOUT_MS)
            }
        },
        mounted() {
            this.createGraph()
            this.loadData()
            loadDataTimer = setInterval(this.loadData, REFRESH_DATA_TIMEOUT_MS)
            window.addEventListener('resize', debounce((e) => {
                this.fillData()
            }, 100));
        },
        methods: {
            createGraph(){
                container = this.$refs["svg-container"]
                let outerWidth = container.clientWidth,
                    outerHeight = container.clientHeight,
                    width = outerWidth - margin.left - margin.right,
                    height = outerHeight - margin.top - margin.bottom

                svg = d3.select(this.$refs["svg"])
                    .attr("width", outerWidth)
                    .attr("height", outerHeight)
                    .append("g")
                    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

                oldDataG = svg.append("g").attr("class", "old-data-g")
                dataG = svg.append("g").attr("class", "data-g")
                // Line is the latest so it will always be visible
                oldDataLineG = svg.append("g").attr("class", "data-line-g")

                x = d3.scaleTime().range([0, width])
                y = d3.scaleLinear().range([height, 0])

                xAxis = d3.axisBottom(x).ticks(5)

                let gX = svg.append("g")
                    .attr("transform", "translate(0," + height + ")")
                    .attr("class", "axis axis--x")
                    .call(xAxis)

                // Mouse events
                let mouseG = svg.append("g")
                    .attr("class", "mouse-over-effects");

                vertical = mouseG.append("path")
                    .attr("class", "mouse-line")
                    .attr("opacity", 0.1)
                    .attr("stroke", "black")
                    .attr("stroke-width", "1px")

                let that = this
                // append a rect to catch mouse movements on canvas
                mouseRect = mouseG.append('svg:rect')
                    .attr('width', width)
                    .attr('height', height)
                    .attr('fill', 'none')
                    .attr('pointer-events', 'all')
                    .on('mouseout', function() {
                        that.legendVisible = false
                        vertical
                            .style("opacity", "0");
                    })
                    .on('mouseover', function() {
                        that.legendVisible = true
                        vertical
                            .style("opacity", "1");
                    })
                    .on('mousemove', function() {
                        if (that.data !== null) {
                            let mouse = d3.mouse(this);
                            const xDate = x.invert(mouse[0])
                            that.highlightRows(xDate, height)
                        }
                    })
            },
            highlightRows(xDate, height) {
                const bisectDate = d3.bisector(d => d.date).left;
                const xDateMillis = moment(xDate).valueOf()

                function getSelectedRow(rowIndex, data) {
                    let rowRight = data[rowIndex]
                    let rowLeft = data[rowIndex - 1]
                    let row = rowRight

                    // Find out which value is closer
                    if (rowLeft !== undefined) {
                        const leftDateMillis = moment(rowLeft.date).valueOf()
                        const rightDateMillis = moment(rowRight.date).valueOf()
                        if ((xDateMillis - leftDateMillis) < (rightDateMillis - xDateMillis)){
                            row = rowLeft
                        }
                    }
                    return row
                }

                let rowIndex, rowIndexPrevious

                // Get row indexes depending on type of graph being shown
                if (this.hasPrevious) {
                    const lastCurrentDateMillis = moment(this.data.results[this.data.results.length - 1].date).valueOf()
                    if (lastCurrentDateMillis >= xDateMillis) {
                        rowIndex = bisectDate(this.data.results, xDate);
                    }
                    rowIndexPrevious = bisectDate(this.data.previousResults, xDate);
                } else {
                    rowIndex = bisectDate(this.data.results, xDate);
                }

                let verticalX, selectedDate, hasCurrent = false, hasPrevious = false, values = {}

                this.data.tags.forEach(function(tag) {
                    values[tag] = {
                        current: undefined,
                        previous: undefined,
                        color: undefined
                    }
                })

                if (rowIndex !== undefined) {
                    let currentRow = getSelectedRow(rowIndex, this.data.results)
                    verticalX = x(currentRow.date)
                    selectedDate = currentRow.date
                    hasCurrent = true

                    this.data.tags.forEach(function(tag) {
                        values[tag].current = currentRow[tag]
                        values[tag].color = d3.color(colorScale(tag)).hex()
                    })
                }

                if (rowIndexPrevious !== undefined) {
                    let previousRow = getSelectedRow(rowIndexPrevious, this.data.previousResults)
                    verticalX = x(previousRow.date)
                    selectedDate = previousRow.date
                    hasPrevious = true

                    this.data.tags.forEach(function(tag) {
                        values[tag].previous = previousRow[tag]
                    })
                }

                // After rows are selected, draw line and legend
                vertical.attr("d", function() {
                        let d = "M" + verticalX + "," + height;
                        d += " " + verticalX + "," + 0;
                        return d;
                    })
                this.legendLeft = Math.round(verticalX) + "px"

                this.highlightedRow = {
                    startDate: selectedDate,
                    values: values,
                    hasCurrent: hasCurrent,
                    hasPrevious: hasPrevious
                }

            },
            fillData() {
                if (this.data === null){
                    return
                }
                let results = this.data.results,
                    previousResults = this.data.previousResultsSummed,
                    tags = this.data.tags

                let outerWidth = container.clientWidth,
                    outerHeight = container.clientHeight,
                    width = outerWidth - margin.left - margin.right,
                    height = outerHeight - margin.top - margin.bottom

                svg.attr("width", outerWidth)
                    .attr("height", outerHeight)

                mouseRect.attr("width", width)
                    .attr("height", height)

                let stack = d3.stack()
                    .keys(tags)
                    .offset(d3.stackOffsetNone)

                let layers = stack(results);

                let maxDate =  results[results.length - 1].date
                let yMax = d3.max(layers, stackMax)

                if (this.hasPrevious) {
                    maxDate = previousResults[previousResults.length - 1].date
                    yMax = Math.max(d3.max(previousResults, (d) => (d.value)), yMax)
                }

                x.domain([results[0].date, maxDate])
                    .range([0, width])
                y.domain([0, yMax])
                    .range([height, 0])

                let colors = tags.map(function (d, i) {
                    return d3.interpolateWarm(i / tags.length);
                });

                colorScale = d3.scaleOrdinal()
                    .domain(tags)
                    .range(colors);

                // Remove original data if present
                dataG.selectAll(".layer").remove();
                oldDataG.selectAll("path").remove();
                oldDataLineG.selectAll("path").remove();

                // Update axis
                svg.select('.axis--x').transition().call(xAxis)

                let area = d3.area()
                    .x((d, i) => x(d.data.date))
                    .y0((d) => y(d[0]))
                    .y1((d) => y(d[1]))

                let areaSimple = d3.area()
                    .x((d) => x(d.date))
                    .y0(y(0))
                    .y1((d) => y(d.value))

                let lineSimple = d3.line()
                    .x((d) => x(d.date))
                    .y((d) => y(d.value))

                // Update data
                let layerGroups = dataG.selectAll(".layer")
                    .data(layers)
                    .enter().append("g")
                    .attr("class", "layer");

                layerGroups.append("path")
                    .attr("d", area)
                    .attr("fill", function (d, i) {
                        return colors[i];
                    })

                if (this.hasPrevious) {
                    oldDataG.append("path")
                        .datum(previousResults)
                        .attr("fill", "#f4f4f4")
                        .attr("d", areaSimple)

                    oldDataLineG.append("path")
                        .data([previousResults])
                        .attr("class", "line")
                        .attr("stroke", "#dedede")
                        .attr("opacity", 0.75)
                        .attr("fill", "none")
                        .attr("d", lineSimple);
                }
            },
            loadData() {
                this.loading = true
                axios
                    .get(this.url, {
                        params: {
                            tz: Intl.DateTimeFormat().resolvedOptions().timeZone,
                            interval: this.interval,
                        }
                    })
                    .then(response => {
                        this.loading = false
                        const tags = response.data.tags
                        const results = response.data.results
                        const previousResults = response.data.previousResults
                        const previousResultsSummed = response.data.previousResultsSummed

                        const parseDate = function (d) {
                            let dataObject = {
                                date: d3.isoParse(d.Date)
                            };
                            tags.forEach(function (s) {
                                dataObject[s] = +d[s];
                            })
                            return dataObject;
                        }

                        this.data = {
                            results: results.map(parseDate),
                            previousResults: previousResults.map(parseDate),
                            previousResultsSummed: previousResultsSummed.map((d) => (
                                {
                                    date: d3.isoParse(d.Date),
                                    value: d.value
                                }
                            )),
                            tags: tags
                        }
                    })
                    .catch(error => {
                        this.error = error
                        this.loading = false;
                    })
            },
        }
    }
</script>