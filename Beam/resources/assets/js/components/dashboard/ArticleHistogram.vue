<template>
    <div ref="svg-container" style="height: 200px;" id="article-chart">
        <svg ref="svg"></svg>
    </div>
</template>

<style>
</style>

<style scoped>

</style>

<script>
    import axios from 'axios'
    import * as d3 from 'd3'

    const props = {
        url: {
            type: String,
            required: true
        }
    }

    let container, svg, x,y, xAxis,
        margin = {top: 20, right: 30, bottom: 20, left: 30}

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

    export default {
        name: 'article-chart',
        props: props,
        data() {
            return {
                data: null,
                loading: false
            };
        },
        watch: {
            data(val) {
                this.fillData()
            }
        },
        mounted() {
            this.createGraph()
            this.loadData()
            setInterval(this.loadData, 15000)
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

                x = d3.scaleTime().range([0, width])
                y = d3.scaleLinear().range([height, 0])

                xAxis = d3.axisBottom(x).ticks(5)

                let gX = svg.append("g")
                    .attr("transform", "translate(0," + height + ")")
                    .attr("class", "axis axis--x")
                    .call(xAxis)
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

                // Prepare data
                let parsedData = results.map(function (d) {
                    let dataObject = {
                        date: d3.isoParse(d.Date)
                    };
                    tags.forEach(function (s) {
                        dataObject[s] = +d[s];
                    })
                    return dataObject;
                });

                let stack = d3.stack()
                    .keys(tags)
                    .offset(d3.stackOffsetNone)

                let layers = stack(parsedData);

                x.domain([parsedData[0].date, parsedData[parsedData.length - 1].date])
                    .range([0, width])
                y.domain([0, d3.max(layers, stackMax)])
                    .range([height, 0])

                // Tags
                let colors = tags.map(function (d, i) {
                    return d3.interpolateWarm(i / tags.length);
                });

                let colorScale = d3.scaleOrdinal()
                    .domain(tags)
                    .range(colors);

                // Remove original data if present
                svg.selectAll(".layer").remove();

                // Update data
                let layerGroups = svg.selectAll(".layer")
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
                            'tz': Intl.DateTimeFormat().resolvedOptions().timeZone,
                            'interval': '7days',
                        }
                    })
                    .then(response => {
                        this.loading = false
                        this.data = response.data
                    })
                    .catch(error => {
                        this.error = error
                        this.loading = false;
                    })
            },
        }
    }
</script>