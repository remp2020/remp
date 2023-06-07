<template>
    <div class="chartContainer">
        <h3>Diagram for conversion source type: {{this.conversionSourceType | trimFromString('session_')}}</h3>
        <p>
            <i class="zmdi zmdi-info-outline zmdi-hc-fw"></i>
            <span>Diagram is based on the {{this.conversionSourceType | trimFromString('session_')}} pageview of all user pageviews that led to purchase</span>
        </p>
        <button-switcher :options="[
        {text: '7 days', value: '7'},
        {text: '30 days', value: '30'}]"
                         :classes="['bottom-left']"
                         v-model="interval">
        </button-switcher>

        <div v-if="loading" class="preloader pls-purple">
            <svg class="pl-circular" viewBox="25 25 50 50">
                <circle class="plc-path" cx="50" cy="50" r="20"></circle>
            </svg>
        </div>

        <div ref="svg-container" class="conversion-sources-diagram">
            <svg :id="`sankey-${this.conversionSourceType}`" xmlns="http://www.w3.org/2000/svg"></svg>
            <div v-if="noData" class="alert alert-danger" role="alert" style="text-align: center">
                No data available
            </div>
        </div>
    </div>
</template>

<style scoped>
    .conversion-sources-diagram {
        height: 300px;
        position: relative;
        margin-top: 20px;
    }

    .chartContainer {
        position: relative;
    }

    .chartContainer .preloader {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%)
    }
</style>

<script>
    import axios from 'axios'
    import * as d3Base from 'd3'
    import { sankey, sankeyLinkHorizontal } from 'd3-sankey'
    import ButtonSwitcher from '../dashboard/ButtonSwitcher.vue'

    const d3 = Object.assign(d3Base, { sankey, sankeyLinkHorizontal });

    let props = {
        dataUrl: {
            type: String,
            required: true
        },
        conversionSourceType: {
            type: String,
            required: true
        }
    };

    export default {
        components: {
            ButtonSwitcher
        },
        name: 'conversions-sankey-diagram',
        props: props,
        data() {
            return {
                interval: '7',
                loading: false,
                noData: false
            };
        },
        mounted() {
            this.loadData();
        },
        watch: {
            interval(value) {
                this.reload();
            }
        },
        filters: {
            trimFromString: function (value, needle) {
                if (value.indexOf(needle) === -1) {
                    return value;
                }
                return value.substring(needle.length);
            }
        },
        methods: {
            loadData() {
                this.loading = true;
                axios
                    .post(this.dataUrl, {
                        tz: Intl.DateTimeFormat().resolvedOptions().timeZone,
                        interval: this.interval,
                        conversionSourceType: this.conversionSourceType,
                    })
                    .then(response => {
                        this.loading = false;

                        if (response.data.links.length === 0) {
                            this.noData = true;
                            return;
                        }

                        this.noData = false;
                        this.createDiagram(response.data);
                    })
            },
            reload() {
                this.noData = false;
                $(`#sankey-${this.conversionSourceType}`).remove();
                const container = this.$refs["svg-container"];
                $(container).prepend(`<svg id="sankey-${this.conversionSourceType}" xmlns="http://www.w3.org/2000/svg"></svg>`);
                this.loadData();
            },
            createDiagram(data) {
                const container = this.$refs["svg-container"];
                const width = container.clientWidth;
                const height = container.clientHeight;

                let edgeColor = 'path';

                const _sankey = d3.sankey()
                    .nodeWidth(15)
                    .nodePadding(10)
                    .extent([[1, 1], [width - 1, height - 5]])
                    .nodeId(function (d) {
                        return d.name;
                    });
                const sankey = ({nodes, links}) => _sankey({
                    nodes: nodes.map(d => Object.assign({}, d)),
                    links: links.map(d => Object.assign({}, d))
                });

                const f = d3.format(",.2f");
                const format = d => `${f(d)}%`;


                const svg = d3.select('#sankey-' + this.conversionSourceType)
                    .attr("viewBox", `0 0 ${width} ${height}`)
                    .style("width", "100%")
                    .style("height", "auto");

                const {nodes, links} = sankey(data);
                const nodeColors = data.nodeColors;

                const color = name => typeof nodeColors[name] !== 'undefined' ? nodeColors[name] : 'grey';

                svg.append("g")
                    .selectAll("rect")
                    .data(nodes)
                    .enter()
                    .append("rect")
                    .attr("x", d => d.x0)
                    .attr("y", d => d.y0)
                    .attr("height", d => d.y1 - d.y0)
                    .attr("width", d => d.x1 - d.x0)
                    .attr("fill", d => color(d.name))
                    .append("title")
                    .text(d => `${d.name}\n${format(d.value)}`);

                const link = svg.append("g")
                    .attr("fill", "none")
                    .attr("stroke-opacity", 0.5)
                    .selectAll("g")
                    .data(links)
                    .enter()
                    .append("g")
                    .style("mix-blend-mode", "multiply");

                if (edgeColor === "path") {
                    const gradient = link.append("linearGradient")
                        .attr("id", (d,i) => {
                            //  (d.uid = DOM.uid("link")).id
                            const id = `sankey-${this.conversionSourceType}-link-${i}`;
                            d.uid = `url(#${id})`;
                            return id;
                        })
                        .attr("gradientUnits", "userSpaceOnUse")
                        .attr("x1", d => d.source.x1)
                        .attr("x2", d => d.target.x0);

                    gradient.append("stop")
                        .attr("offset", "0%")
                        .attr("stop-color", d => color(d.source.name));

                    gradient.append("stop")
                        .attr("offset", "100%")
                        .attr("stop-color", d => color(d.target.name));
                }

                link.append("path")
                    .attr("d", d3.sankeyLinkHorizontal())
                    .attr("stroke", d => edgeColor === "path" ? d.uid
                        : edgeColor === "input" ? color(d.source.name)
                            : color(d.target.name))
                    .attr("stroke-width", d => Math.max(1, d.width));

                let tooltip = d3.select(container)
                    .append("div")
                    .style("opacity", 0)
                    .attr("class", "tooltip")
                    .style("background-color", "white")
                    .style("border", "solid")
                    .style("border-width", "2px")
                    .style("border-radius", "5px")
                    .style("padding", "5px");

                let mouseover = function (d) {
                    tooltip.style("opacity", 1);
                };

                let mousemove = function (d) {
                    tooltip
                        .html(`${d.source.name} -> ${d.target.name}\n${format(d.value)}`)
                        .style("left", (d3.mouse(this)[0] + 30) + "px")
                        .style("top", (d3.mouse(this)[1]) + "px");
                };

                let mouseleave = function (d) {
                    tooltip.style("opacity", 0);
                };

                link.on("mouseover", mouseover)
                    .on("mousemove", mousemove)
                    .on("mouseleave", mouseleave);

                svg.append("g")
                    .selectAll("text")
                    .data(nodes)
                    .enter()
                    .append("text")
                    .attr("x", d => d.x0 < width / 2 ? d.x1 + 6 : d.x0 - 6)
                    .attr("y", d => (d.y1 + d.y0) / 2)
                    .attr("dy", "0.35em")
                    .attr("text-anchor", d => d.x0 < width / 2 ? "start" : "end")
                    .append("tspan")
                    .style("font-size", "15px")
                    .style("font-weight", "bold")
                    .text(d => `${format(d.value)}`)
                    .append("tspan")
                    .style("font-size", "10px")
                    .style("font-weight", "normal")
                    .text(d => `${d.name}`)
                    .attr("x", d => d.x0 < width / 2 ? d.x1 + 6 : d.x0 - 6)
                    .attr("dy", "0.9em");
            }
        }
    }
</script>
