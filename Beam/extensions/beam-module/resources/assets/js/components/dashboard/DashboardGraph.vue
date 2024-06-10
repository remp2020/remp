<template>
    <div>
        <div v-if="error" class="error" :title="error"></div>

        <div id="chartContainerHeader" class="row">
            <div class="col-md-6">
                <h4>
                    <text-with-tooltip>
                        <template #default>
                            <span>
                                👥 <animated-integer :value="concurrents"></animated-integer>
                            </span>
                            <span class="mobile-percentage" :class="{ 'mobile-percentage--null': mobileConcurrentsPercentage === null }">
                                (📱<animated-integer :value="mobileConcurrentsPercentage || 0"></animated-integer>%)
                            </span>
                        </template>
                        <template #tooltip>
                            <span v-if="mobileConcurrentsPercentage === null">
                                You have to configure "concurrents" block in Telegraf in order to see percentage of concurrent mobile users visiting your website.
                                <a href="https://github.com/remp2020/remp/blob/master/Docker/telegraf/telegraf.conf" target="_blank">https://github.com/remp2020/remp/blob/master/Docker/telegraf/telegraf.conf</a>
                            </span>
                            <span v-else>
                                Shows number of concurrent users visiting website and percentage of users using mobile device.
                            </span>
                        </template>
                    </text-with-tooltip>
                </h4>
            </div>
            <div class="col-md-6">
                <options :classes="['pull-right', 'm-l-15', 'm-b-5']"></options>

                <button-switcher :options="options"
                                 :classes="['pull-right']"
                                 v-model="interval">
                </button-switcher>
            </div>
        </div>

        <div class="col-md-12 settings-box">
            <div v-if="externalEvents.length > 0" class="external-events-wrapper">
                <select class="selectpicker bs-select-hidden" title="Select events" multiple="" v-model="selectedExternalEvents">
                    <option v-for="option in externalEvents" :value="option">{{option.text}}</option>
                </select>
            </div>

            <!-- Slot for settings such as token selector (for public dashboard) -->
            <slot name="additional-settings"></slot>
        </div>

        <div class="col-md-12">
            <div id="chartContainer">
                <div v-if="loading" class="preloader pls-purple">
                    <svg class="pl-circular" viewBox="25 25 50 50">
                        <circle class="plc-path" cx="50" cy="50" r="20"></circle>
                    </svg>
                </div>

                <div class="events-legend-wrapper">
                    <div v-if="eventLegend.data"
                         v-show="eventLegend.visible"
                         v-bind:style="eventLegend.style"
                         @mouseleave="hideEventLegend">

                        <div class="events-legend"
                             v-for="event in eventLegend.data"
                             v-html="event.title"
                             v-bind:style="event.style">
                        </div>
                    </div>
                </div>

                <div ref="svg-container" id="article-chart">
                    <svg style="z-index: 100" ref="svg"></svg>
                    <div style="z-index: 101" @mouseenter="hideEventLegend" class="mouse-catch-block"></div>
                </div>

                <div id="legend-wrapper">
                    <div v-if="highlightedRow" v-show="legendVisible" v-bind:style="{ left: legendLeft }" id="article-graph-legend">

                        <div v-if="settings.newGraph" class="legend-title">{{highlightedRow.startDate | filterLegendTitle}}</div>
                        <div v-else class="legend-title">{{highlightedRow.startDate | formatDate(data.intervalMinutes)}}</div>

                        <table>
                            <tr>
                                <th></th>
                                <th v-if="highlightedRow.hasCurrent">Current</th>
                                <th v-if="highlightedRow.hasPrevious">
                                    <template v-if="settings.compareWith=='average'">Average</template>
                                    <template v-else>Week&nbsp;ago</template>
                                </th>
                            </tr>
                            <tr v-for="(item, tag) in highlightedRow.values">
                                <td>
                                <span v-if="highlightedRow.hasCurrent"
                                      style="font-weight: bold"
                                      v-bind:style="{color: item.color}">&#9679;</span>
                                    <span v-if="tag==''">Uncategorized</span>
                                    <span v-else style="text-transform: capitalize">{{tag}}</span>
                                </td>
                                <td v-if="highlightedRow.hasCurrent">{{item.current}}</td>
                                <td v-if="highlightedRow.hasPrevious">{{item.previous}}</td>
                            </tr>
                            <tr style="border-top: 1px solid #d1d1d1">
                                <td><b>Total</b></td>
                                <td v-if="highlightedRow.hasCurrent"><b>{{highlightedRow.currentSum}}</b></td>
                                <td v-if="highlightedRow.hasPrevious"><b>{{highlightedRow.previousSum}}</b></td>
                            </tr>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
    #article-chart {
        height: 200px;
        position: relative;

    }

    #article-chart .mouse-catch-block {
        position: absolute;
        display: block;
        bottom: -10px;
        height: 10px;
        width: 100%;
        background-color: transparent;
    }

    .settings-box {
        display: flex;
        align-items: center;
        justify-content: right;
        padding-right: 30px;
        padding-left: 30px;
    }

    .external-events-wrapper {
        display:inline-block;
        width: 220px;
    }

    #chartContainer {
        position: relative;
    }

    #chartContainerHeader {
        padding: 20px 30px 0px 30px;
    }

    #legend-wrapper {
        position: relative;
        overflow: visible;
        height:0;
    }
    #article-graph-legend table {
        width: 100%;
        background-color: transparent;
        border-collapse: collapse;
    }
    #article-graph-legend table td, th {
        padding: 3px 6px
    }
    #article-graph-legend {
        position:absolute;
        white-space:nowrap;
        z-index: 1000;
        top:0;
        left: 0;
        opacity: 0.95;
        color: #fff;
        padding: 2px;
        background-color: #494949;
        border-radius: 2px;
        border: 2px solid #494949;
        transform: translate(-50%, 0px)
    }

    .legend-title {
        text-align: center;
        font-size: 14px;
    }

    #chartContainer .preloader {
        z-index: 2000;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%)
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

    .mobile-percentage {
      font-size: 12px;

      &--null {
        opacity: 0.6;
      }
    }
</style>

<script>
    import AnimatedInteger from './AnimatedInteger.vue'
    import ButtonSwitcher from './ButtonSwitcher.vue'
    import Options from './Options.vue'
    import * as constants from './constants'
    import {debounce, formatInterval} from './constants'
    import axios from 'axios'
    import * as d3 from 'd3'
    import TextWithTooltip from '../TextWithTooltip.vue'

    let props = {
        url: {
            type: String,
            required: true
        },
        urlNew: {
            type: String,
            required: true
        },
        concurrents: {
            type: Number,
            required: true
        },
        mobileConcurrentsPercentage: {
            type: Number,
        },
        externalEvents: {
            type: Array,
            default: () => [],
        },
        showInterval7Days: {
            type: Boolean,
            default: true,
        },
        showInterval30Days: {
            type: Boolean,
            default: true,
        }
    };

    Vue.filter('formatDate', formatInterval)

    function stackMax(layer) {
        return d3.max(layer, function (d) { return d[1]; });
    }

    export default {
        components: {
            TextWithTooltip, ButtonSwitcher, AnimatedInteger, Options
        },
        name: 'dashboard-graph',
        props: props,
        created() {
            this.vars = {
                container: null,
                svg: null,
                dataG: null,
                oldDataG: null,
                oldDataLineG: null,
                colorScale: null,
                xAxis: null,
                vertical: null,
                margin: {
                    top: 20,
                    right: 20,
                    bottom: 20,
                    left: 20
                },
                loadDataTimer: null,
                mouseRect: null,
                eventsG: null,
            }
        },
        data() {
            let options = [
                {text: 'Today', value: 'today'}
            ];
            if (this.showInterval7Days) {
                options.push({text: '7 days', value: '7days'});
            }
            if (this.showInterval30Days) {
                options.push({text: '30 days', value: '30days'});
            }
            return {
                data: null,
                error: null,
                loading: false,
                interval: 'today',
                legendVisible: false,
                highlightedRow: null,
                legendLeft: '0px',
                eventLegend: {
                    visible: false,
                    data: [],
                    style: {}
                },
                selectedExternalEvents: [],
                options: options
            };
        },
        computed: {
            hasPrevious() {
                return this.data !== null && this.data.previousResultsSummed.length > 0
            },
            settings() {
                return this.$store.state.settings
            }
        },
        watch: {
            data(val) {
                this.fillData()
            },
            interval(value) {
                this.reload()
            },
            settings(value) {
                this.reload()
            },
            selectedExternalEvents(values) {
                this.reload()
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
            hideEventLegend() {
                this.eventLegend.visible = false
            },
            reload() {
                clearInterval(this.vars.loadDataTimer)
                this.loadData()
                this.vars.loadDataTimer = setInterval(this.loadData, constants.REFRESH_DATA_TIMEOUT_MS)
            },
            createGraph(){
                this.vars.container = this.$refs["svg-container"]
                let outerWidth = this.vars.container.clientWidth,
                    outerHeight = this.vars.container.clientHeight,
                    width = outerWidth - this.vars.margin.left - this.vars.margin.right,
                    height = outerHeight - this.vars.margin.top - this.vars.margin.bottom

                this.vars.svg = d3.select(this.$refs["svg"])
                    .attr("width", outerWidth)
                    .attr("height", outerHeight)
                    .append("g")
                    .attr("transform", "translate(" + this.vars.margin.left + "," + this.vars.margin.top + ")");

                this.vars.oldDataG = this.vars.svg.append("g").attr("class", "old-data-g")
                this.vars.dataG = this.vars.svg.append("g").attr("class", "data-g")
                // Line is last so it's going to be always visible
                this.vars.oldDataLineG = this.vars.svg.append("g").attr("class", "data-line-g")

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
                    .attr("stroke", "black")
                    .attr("stroke-width", "1px")

                let that = this
                // append a rect to catch mouse movements on canvas
                this.vars.mouseRect = mouseG.append('svg:rect')
                    .attr('width', width)
                    .attr('height', height)
                    .attr('fill', 'none')
                    .attr('pointer-events', 'all')
                    .on('mouseout', function() {
                        that.legendVisible = false
                        // that.eventLegend.visible = false
                        that.vars.vertical.style("opacity", "0");
                    })
                    .on('mouseover', function() {
                        that.legendVisible = true
                        that.vars.vertical.style("opacity", "1");
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

                const bisectDate = d3.bisector(d => d.date).left;
                const xDateMillis = moment(xDate).valueOf()

                function getCloserRow(rowLeft, rowRight, toDateMillis) {
                    const leftDateMillis = moment(rowLeft.date).valueOf()
                    const rightDateMillis = moment(rowRight.date).valueOf()
                    if ((toDateMillis - leftDateMillis) < (rightDateMillis - toDateMillis)) {
                        return rowLeft
                    }
                    return rowRight
                }

                function getSelectedRow(rowIndex, data) {
                    let rowRight = data[rowIndex]
                    let rowLeft = data[rowIndex - 1]
                    let row

                    // Find out which value is closer
                    if (rowLeft !== undefined && rowRight !== undefined) {
                        row = getCloserRow(rowLeft, rowRight, xDateMillis)
                    } else if (rowLeft !== undefined) {
                        row = rowLeft
                    } else if (rowRight !== undefined) {
                        row = rowRight
                    }
                    return row
                }
                let rowIndex, rowIndexPrevious

                rowIndex = bisectDate(this.data.results, xDate)
                if (this.hasPrevious) {
                    rowIndexPrevious = bisectDate(this.data.previousResults, xDate)
                }

                let lastItem = this.data.resultsForSearch[this.data.resultsForSearch.length - 1]

                let verticalX, selectedDate, values = {}

                this.data.tags.forEach(function(tag) {
                    values[tag] = {
                        current: undefined,
                        previous: undefined,
                        color: undefined
                    }
                })

                let currentSum = 0, previousSum = 0
                let previousRow, currentRow

                if (rowIndexPrevious !== undefined) {
                    previousRow = getSelectedRow(rowIndexPrevious, this.data.previousResults)
                    if (previousRow !== undefined) {
                        verticalX = this.vars.x(previousRow.date)
                        selectedDate = previousRow.date

                        this.data.tags.forEach(function(tag) {
                            values[tag].previous = previousRow[tag]
                            previousSum += previousRow[tag]
                        })
                    }
                }

                if (rowIndex !== undefined) {
                    currentRow = getSelectedRow(rowIndex, this.data.resultsForSearch)
                    if (currentRow !== undefined) {
                        if (previousRow !== undefined && lastItem.date === currentRow.date &&
                            previousRow.date > currentRow.date && getCloserRow(currentRow, previousRow, xDateMillis) === previousRow) {

                            // In this case, we are "out-of" the current results, do not show them in legend
                            currentRow = undefined

                        } else {
                            verticalX = this.vars.x(currentRow.date)
                            selectedDate = currentRow.date

                            this.data.tags.map(tag => {
                                values[tag].current = currentRow[tag]
                                values[tag].color = d3.color(this.vars.colorScale(tag)).hex()
                                currentSum += currentRow[tag]
                            })
                        }
                    }
                }

                // After rows are selected, draw line and legend
                this.vars.vertical.attr("d", function() {
                    let d = "M" + verticalX + "," + height;
                    d += " " + verticalX + "," + 0;
                    return d;
                })
                this.legendLeft = (Math.round(verticalX) + this.vars.margin.left) + "px"

                this.highlightedRow = {
                    startDate: selectedDate,
                    values: values,
                    hasCurrent:  currentRow !== undefined,
                    hasPrevious: previousRow !== undefined,
                    currentSum: currentSum,
                    previousSum: previousSum
                }
            },
            fillData() {
                if (this.data === null || this.data.results.length === 0){
                    return
                }
                let results = this.data.results,
                    previousResults = this.data.previousResultsSummed,
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

                if (this.maxDate) { // explicit maxDate
                    maxDate = this.maxDate
                }

                this.vars.x.domain([results[0].date, maxDate])
                    .range([0, width])
                this.vars.y.domain([0, yMax])
                    .range([height, 0])

                this.vars.colorScale = d3.scaleOrdinal()
                    .domain(tags)
                    .range(colors);

                // Remove original data if present
                this.vars.dataG.selectAll(".layer").remove();
                this.vars.dataG.selectAll(".layer-line").remove();
                this.vars.oldDataG.selectAll("path").remove();
                this.vars.oldDataLineG.selectAll("path").remove();
                this.vars.eventsG.selectAll('.event').remove()

                // Update axis
                this.vars.svg.select('.axis--x').transition().call(this.vars.xAxis)

                let area = d3.area()
                    .curve(d3.curveMonotoneX)
                    .x((d, i) => this.vars.x(d.data.date))
                    .y0((d) => this.vars.y(d[0]))
                    .y1((d) => this.vars.y(d[1]))

                let areaStroke = d3.line()
                    .curve(d3.curveMonotoneX)
                    .x((d, i) => this.vars.x(d.data.date))
                    .y((d) => this.vars.y(d[1]))

                let areaSimple = d3.area()
                    .curve(d3.curveMonotoneX)
                    .x((d) => this.vars.x(d.date))
                    .y0(this.vars.y(0))
                    .y1((d) => this.vars.y(d.value))

                let areaSimpleStroke = d3.line()
                    .curve(d3.curveMonotoneX)
                    .x((d) => this.vars.x(d.date))
                    .y((d) => this.vars.y(d.value))

                // Update data
                this.vars.dataG.selectAll(".layer")
                    .data(layers)
                    .enter().append("g")
                    .attr("class", "layer")
                    .append("path")
                    .attr("d", area)
                    .attr("fill", (d, i) => colors[i])

                this.vars.dataG.selectAll(".layer-line")
                    .data(layers)
                    .enter().append("g")
                    .attr("class", "layer-line")
                    .append("path")
                    .attr("d", areaStroke)
                    .attr("stroke", "#626262")
                    .attr("opacity", 0.5)
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

                if (this.hasPrevious) {
                    this.vars.oldDataG.append("path")
                        .datum(previousResults)
                        .attr("fill", "#f2f2f2")
                        .attr("d", areaSimple)

                    this.vars.oldDataLineG.append("path")
                        .data([previousResults])
                        .attr("class", "line")
                        .attr("stroke", "#dedede")
                        .attr("opacity", 0.75)
                        .attr("fill", "none")
                        .attr("d", areaSimpleStroke);
                }
            },
            loadData() {
                this.loading = true
                axios
                    .post(this.settings.newGraph ? this.urlNew : this.url, {
                        tz: Intl.DateTimeFormat().resolvedOptions().timeZone,
                        interval: this.interval,
                        settings: this.settings,
                        externalEvents: this.selectedExternalEvents.map(option => option.value),
                    })
                    .then(response => {
                        this.loading = false
                        const tags = response.data.tags
                        const results = response.data.results
                        const previousResults = response.data.previousResults
                        const previousResultsSummed = response.data.previousResultsSummed
                        const events = response.data.events

                        const parseDate = function (d) {
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
                            intervalMinutes: response.data.intervalMinutes,
                            results: results.map(parseDate),
                            resultsForSearch: results.filter(item => !item.hasOwnProperty('_unfinished')).map(parseDate),
                            previousResults: previousResults.map(parseDate),
                            previousResultsSummed: previousResultsSummed.map((d) => (
                                {
                                    date: d3.isoParse(d.Date),
                                    value: d.value
                                }
                            )),
                            events: events ? events.map(parseEvents) : [],
                            tags: tags,
                            colors: response.data.colors
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
