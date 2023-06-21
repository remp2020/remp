<template>
    <div class="chartContainer">

        <button-switcher v-if="showDataSourceSwitcher" :options="[
                    {text: 'Pageviews with sources', value: 'journal'},
                    {text: 'Concurrents with sources', value: 'snapshots'},
                    {text: 'Pageviews', value: 'pageviews'},
                    ]"
                     :classes="['pull-right']"
                     v-model="dataSource">
        </button-switcher>

        <button-switcher :options="[
                    {text: 'Since publishing', value: 'all', group: 0},
                    {text: 'Today', value: 'today', group: 1},
                    {text: '7 days', value: '7days', group: 1},
                    {text: '30 days', value: '30days', group: 1},
                    {text: 'First day', value: 'first1day', group: 2},
                    {text: 'First 7 days', value: 'first7days', group: 2},
                    {text: 'First 14 days', value: 'first14days', group: 2}]"
                         v-model="interval">
        </button-switcher>

        <div v-if="loading" class="preloader pls-purple">
            <svg class="pl-circular" viewBox="25 25 50 50">
                <circle class="plc-path" cx="50" cy="50" r="20"></circle>
            </svg>
        </div>

        <div class="m-t-20 m-b-20" v-if="tagLabels">
            <div v-bind:style="{color: item.color}" v-for="(item, tag) in tagLabels">
                <b>{{tag}}</b>: <span v-html="item.pageloads_count"></span>
                <span v-for="(label, index) in item.labels">
                    <span>{{label}}</span> <span style="color: #000;" v-if="index < item.labels.length - 1"> | </span>
                </span>
            </div>
        </div>

        <div class="m-t-10 events-box" v-if="eventOptions">
            <label class="checkbox checkbox-inline m-r-20" v-for="option in eventOptions">
                <input :id="option.value" :value="option" type="checkbox" v-model="selectedEvents" />
                <i class="input-helper"></i>{{option.text}}
            </label>
            <div v-if="externalEvents.length > 0" class="external-events-wrapper">
                <select class="selectpicker bs-select-hidden" title="Other events" multiple="" v-model="selectedExternalEvents">
                    <option v-for="option in externalEvents" :value="option">{{option.text}}</option>
                </select>
            </div>
        </div>

        <div class="events-legend-wrapper">
            <div
                    v-if="eventLegend.data"
                    v-show="eventLegend.visible"
                    v-bind:style="eventLegend.style">

                <div class="events-legend"
                     v-for="event in eventLegend.data"
                     v-html="event.title"
                     v-bind:style="event.style">
                </div>

            </div>
        </div>

        <div :id="svgContainerId" :ref="svgContainerId" style="height: 200px" class="article-chart">
            <svg style="z-index: 10" :id="svgId" :ref="svgId"></svg>
        </div>

        <div class="legend-wrapper">
            <div v-if="highlightedRow" v-show="legend.visible" v-bind:style="{ left: legend.left }" class="article-graph-legend">

                <div v-if="dataSource" class="legend-title">{{highlightedRow.startDate | formatDate(data.intervalMinutes)}}</div>
                <div v-else class="legend-title">{{highlightedRow.startDate | filterLegendTitle}}</div>

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

</template>

<style scoped>
    .events-box {
        display: flex;
        align-items: center;
    }

    .external-events-wrapper {
        display:inline-block;
        width: 220px;
    }

    .chartContainer {
        position: relative;
    }

    .legend-wrapper {
        position: relative;
        height:0;
    }
    .article-graph-legend table {
        width: 100%;
        background-color: transparent;
        border-collapse: collapse;
    }
    .article-graph-legend table td, th {
        padding: 3px 6px
    }

    .legend-title {
        text-align: center;
        font-size: 14px;
    }

    .article-graph-legend {
        position:absolute;
        white-space:nowrap;
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

    .events-legend-wrapper {
        position: relative;
        height: 0;
    }

    /* div to correctly wrap transformed .events-legend */
    .events-legend-wrapper > div {
        position: absolute;
        z-index: 1000;
        bottom:-21px;
        width: auto;
    }

    .events-legend {
        max-width: 220px;
        opacity: 0.85;
        color: #fff;
        padding: 2px;
        margin-top: 2px;
        background-color: #00bdf1;
        border-radius: 2px;
        border: 2px solid #00bdf1;
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
    import * as constants from './constants'
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
        eventOptions: {
            type: Array,
            default: () => []
        },
        stacked: {
            type: Boolean,
            default: true
        },
        defaultGraphDataSource: {
            type: String,
            default: 'pageviews'
        },
        externalEvents: {
            type: Array,
            default: () => [],
        },
        showDataSourceSwitcher: {
            type: Boolean,
            default: false
        },
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
        name: 'page-loads-graph',
        props: props,
        data() {
            return {
                data: null,
                tagLabels: null,
                loading: false,
                interval: 'all',
                dataSource: this.defaultGraphDataSource,
                highlightedRow: null,
                legend: {
                    visible: false,
                    data: null,
                    left: "0px"
                },
                eventLegend: {
                    visible: false,
                    data: [],
                    style: {}
                },
                selectedEvents: [],
                selectedExternalEvents: [],
            };
        },
        watch: {
            data(val) {
                this.fillData()
            },
            interval(value) {
                this.reload()
            },
            dataSource(value) {
                this.reload()
            },
            selectedEvents(values) {
                this.reload()
            },
            selectedExternalEvents(values) {
                this.reload()
            },
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
            for (const option of this.eventOptions) {
                if (option.checked) {
                    this.selectedEvents.push(option)
                }
            }

            this.vars = {
                container: null,
                svg: null,
                dataG: null,
                eventsG: null,
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
                loadDataTimer: null
            }
        },
        mounted() {
            this.createGraph()
            this.loadData()
            this.vars.loadDataTimer = setInterval(this.loadData, constants.REFRESH_DATA_TIMEOUT_MS)
            window.addEventListener('resize', debounce((e) => {
                this.fillData()
            }, 100));
        },
        filters: {
            filterLegendTitle(value) {
                return moment(value).format('lll')
            }
        },
        methods: {
            reload() {
                clearInterval(this.vars.loadDataTimer)
                this.loadData()
                this.vars.loadDataTimer = setInterval(this.loadData, constants.REFRESH_DATA_TIMEOUT_MS)
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

                this.vars.eventsG = this.vars.svg.append("g").attr("class", "events-g")

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
                        that.legend.visible = false
                        that.eventLegend.visible = false
                        that.vars.vertical
                            .style("opacity", "0");
                    })
                    .on('mouseover', function() {
                        that.legend.visible = true
                        that.vars.vertical
                            .style("opacity", "1");
                    })
                    .on('mousemove', function() {
                        if (that.data !== null) {
                            let mouse = d3.mouse(this);
                            const xDate = that.vars.x.invert(mouse[0])
                            that.highlightRow(xDate, height)
                            that.highlightEvent(xDate)
                        }
                    })
            },
            highlightEvent(xDate) {
                const xDateMillis = moment(xDate).valueOf()
                const pxThresholdToShowLegend = 50

                // get closest event (not using bisect, as there are only few events to search)
                let selectedEvents = [], min = Number.MAX_SAFE_INTEGER
                for (const event of this.data.events) {
                    let diff = Math.abs(xDateMillis - moment(event.date).valueOf())
                    if (diff < min) {
                        min = diff
                        selectedEvents = [event]
                    } else if (diff === min) {
                        selectedEvents.push(event)
                    }
                }

                // show event only if it's close to x-position of the mouse
                if (selectedEvents.length > 0 && Math.abs(this.vars.x(selectedEvents[0].date) - this.vars.x(xDate)) < pxThresholdToShowLegend) {
                    this.eventLegend.visible = true
                    this.eventLegend.style = {
                        'left': (this.vars.x(selectedEvents[0].date) + this.vars.margin.left) + "px"
                    }

                    this.eventLegend.data = []

                    for (const event of selectedEvents) {
                        this.eventLegend.data.push({
                            'title':  event.event.title,
                            'style': {
                                'background-color': event.event.color,
                                'border-color': event.event.color,
                            }
                        })
                    }
                } else {
                    this.eventLegend.visible = false
                }
            },
            highlightRow(xDate, height) {
                if (!this.data.results || this.data.results.length === 0) {
                    return
                }

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
                    .attr("d", () => {
                        return "M" + verticalX + "," + height + " " + verticalX + "," + 0
                    })

                let valuesSum = 0

                let values = this.data.tags.map(tag => {
                    valuesSum += row[tag]
                    return {
                        tag: tag,
                        value: row[tag],
                        color: d3.color(this.vars.colorScale(tag)).hex()
                    }
                })

                this.highlightedRow = {
                    startDate: row.date,
                    values: values,
                    sum: valuesSum
                }

                this.legend.left = (Math.round(this.vars.x(row.date)) + this.vars.margin.left) + "px"

            },
            fillData() {
                if (this.data === null || this.data.results.length === 0){
                    return
                }
                let results = this.data.results,
                    tags = this.data.tags,
                    colors = this.data.colors,
                    events = this.data.events

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

                let minDate = results[0].date
                let maxDate = results[results.length - 1].date
                if (this.minDate) {
                    minDate = this.minDate
                }
                if (this.maxDate) {
                    maxDate = this.maxDate
                }

                this.vars.x.domain([minDate, maxDate])
                    .range([0, width])
                this.vars.y.domain([0, yMax])
                    .range([height, 0])

                this.vars.colorScale = d3.scaleOrdinal()
                    .domain(tags)
                    .range(colors);

                // Remove original data if present
                this.vars.dataG.selectAll(".layer").remove();
                this.vars.dataG.selectAll(".layer-line").remove();
                this.vars.eventsG.selectAll('.event').remove()

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

                let eventLayers = this.vars.eventsG.selectAll(".event")
                    .data(events)
                    .enter().append("g")
                    .attr("class", "event")

                eventLayers
                    .append("path")
                    .attr("d", item => {
                        let xDate = this.vars.x(item.date)
                        return "M" + xDate + "," + height + " " + xDate + "," + 0
                    })
                    .attr("stroke-dasharray", "6 4")
                    .attr("stroke", item => item.event.color)

                eventLayers
                    .append("polygon")
                    .attr("points", (item, i) => {
                        let xDate = this.vars.x(item.date)
                        // small triangle on top of dashed line
                        let size = 5
                        let points = [
                            [xDate - size, 0],
                            [xDate + size, 0],
                            [xDate, size],
                        ]
                        return points.map((p) => p[0] + "," + p[1]).join(" ")
                    })
                    .attr("fill", item => item.event.color)

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
                            dataSource: this.dataSource,
                            events: this.selectedEvents.map(option => option.value),
                            externalEvents: this.selectedExternalEvents.map(option => option.value)
                        }, this.urlParams)
                    })
                    .then(response => {
                        this.loading = false
                        const tags = response.data.tags
                        const data = response.data.results
                        const events = response.data.events

                        let parseData = function (d) {
                            let dataObject = {
                                date: d3.isoParse(d.Date)
                            };
                            tags.forEach(function (s) {
                                dataObject[s] = +d[s];
                            })
                            return dataObject;
                        }

                        let parseEvents = function(event) {
                            return {
                                date: d3.isoParse(event.date),
                                event: event
                            }
                        }

                        this.data = {
                            results: data.map(parseData),
                            tags: tags,
                            events: events ? events.map(parseEvents) : [],
                            colors: response.data.colors,
                            intervalMinutes: response.data.intervalMinutes
                        }
                        this.tagLabels = response.data.tagLabels || null

                        if (response.data.minDate !== undefined) {
                            this.minDate = d3.isoParse(response.data.minDate)
                        }
                        if (response.data.maxDate !== undefined) {
                            this.maxDate = d3.isoParse(response.data.maxDate)
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
