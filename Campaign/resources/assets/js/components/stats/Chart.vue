<style scoped>
    .card {
        padding: 30px;
    }

    h3 {
        margin-top: 0;
    }

    canvas {
        width: 100%;
        /* max-height: 500px; */
    }
</style>

<template>
    <div class="card">
        <h3>{{ title }}</h3>

        <div class="card-body">
            <canvas :id="name" height="500"></canvas>
        </div>

    </div><!-- .card -->
</template>

<script>
    let props = {
        url: {
            type: String,
            required: true
        },
        name: {
            type: String,
            required: true
        },
        title: {
            type: String,
            required: true
        },
        from: {
            type: String,
            required: true
        },
        to: {
            type: String,
            required: true
        }
    }

    export default {
        props: props,
        data() {
            return {
                loaded: false,
                labels: null,
                data: null
            }
        },
        mounted() {
            this.load()
        },
        methods: {
            load() {
                var vm = this;

                $.ajax({
                    method: 'POST',
                    url: vm.url,
                    data: {
                        from: vm.from,
                        to: vm.to,
                        chartWidth: $('#' + this.name).width(),
                        _token: document.head.querySelector("[name=csrf-token]").content
                    },
                    dataType: 'JSON',
                    success(data, stats) {
                        vm.loaded = true;

                        vm.init(data.dataSets, data.labels)
                    }
                })
            },
            init(dataSets, labels) {
                var ctx = document.getElementById(this.name).getContext('2d');

                var myLineChart = new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: labels,
                        datasets: dataSets
                    },
                    options: {
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
                        }
                    }
                });
            }
        }
    }
</script>
