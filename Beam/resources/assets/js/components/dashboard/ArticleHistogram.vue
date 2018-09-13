<template>
    <div>
        <button-switcher :options="[
                    {text: 'Today', value: 'today'},
                    {text: '7 days', value: '7days'},
                    {text: '30 days', value: '30days'},
                    {text: 'Since publishing', value: 'all'}]"
                         v-model="interval">
        </button-switcher>
        <div ref="svg-container" style="height: 200px" id="article-chart">
            <svg style="z-index: 10" ref="svg"></svg>
        </div>
        <div id="legend-wrapper">
            <div v-if="highlightedRow" v-show="legendVisible" v-bind:style="{ left: legendLeft }" id="article-graph-legend">

                <span>{{highlightedRow.startDate | formatDate}}</span>
                <table>
                    <tr>
                        <th>Source</th>
                        <th>Value</th>
                    </tr>
                    <tr v-for="item in highlightedRow.values">
                        <td><span style="font-weight: bold" v-bind:style="{color: item.color}">&#9679;</span>&nbsp;{{item.tag}}</td>
                        <td>{{item.value}}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</template>

<style>
</style>

<style scoped>
    #legend-wrapper {
        position: relative;
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
        top:0;
        left: 0;
        opacity: 0.8;
        color: #fff;
        padding: 2px;
        background-color: #b6b6b6;
        border-radius: 2px;
        border: 2px solid #b6b6b6;
    }
</style>

<script>
    import ButtonSwitcher from './ButtonSwitcher.vue'
    import axios from 'axios'
    import * as d3 from 'd3'

    const props = {
        url: {
            type: String,
            required: true
        }
    }

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
    const bisectDate = d3.bisector(d => d.date).left;

    const REFRESH_DATA_TIMEOUT_MS = 15000

    let container, svg, dataG, x,y, colorScale, xAxis, vertical, mouseRect,
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
                loading: false,
                interval: 'today',
                legendVisible: false,
                highlightedRow: null,
                legendLeft: "100px"
            };
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

                dataG = svg.append("g").attr("class", "data-g")

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
                    .style("stroke", "black")
                    .style("stroke-width", "1px")
                    .style("opacity", "0");

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
                            let rowIndex = bisectDate(that.data.results, xDate);

                            let rowRight = that.data.results[rowIndex]
                            let rowLeft = that.data.results[rowIndex - 1]
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

                            let verticalX = x(row.date)

                            vertical
                                .attr("d", function() {
                                    let d = "M" + verticalX + "," + height;
                                    d += " " + verticalX + "," + 0;
                                    return d;
                                })

                            let values = that.data.tags.map(function (tag) {
                                return {
                                    tag: tag,
                                    value: row[tag],
                                    color: d3.color(colorScale(tag)).hex()
                                }
                            })

                            that.highlightedRow = {
                                startDate: row.date,
                                values: values
                            }

                            that.legendLeft = Math.round(x(xDate)) + "px"
                        }
                    })
            },
            fillData() {
                if (this.data === null){
                    return
                }
                let results = this.data.results, tags = this.data.tags

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

                x.domain([results[0].date, results[results.length - 1].date])
                    .range([0, width])
                y.domain([0, d3.max(layers, stackMax)])
                    .range([height, 0])

                let colors = tags.map(function (d, i) {
                    return d3.interpolateWarm(i / tags.length);
                });

                colorScale = d3.scaleOrdinal()
                    .domain(tags)
                    .range(colors);

                // Remove original data if present
                dataG.selectAll(".layer").remove();

                // Update data
                let layerGroups = dataG.selectAll(".layer")
                    .data(layers)
                    .enter().append("g")
                    .attr("class", "layer");

                // Draw data
                let area = d3.area()
                    .x(function (d, i) { return x(d.data.date) })
                    .y0(function (d) { return y(d[0]); })
                    .y1(function (d) { return y(d[1]); })

                layerGroups.append("path")
                    .attr("d", area)
                    .attr("fill", function (d, i) {
                        return colors[i];
                    });

                // Update axis
                svg.select('.axis--x').transition().call(xAxis)
            },
            loadData() {
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
                        const data = response.data.results

                        let parsedData = data.map(function (d) {
                            let dataObject = {
                                date: d3.isoParse(d.Date)
                            };
                            tags.forEach(function (s) {
                                dataObject[s] = +d[s];
                            })
                            return dataObject;
                        });

                        this.data = {
                            results: parsedData,
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