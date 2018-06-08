<style scoped>
    .card {
        text-align: center;
        padding: 15px 0;
    }

    h4 {
        margin-top: 0;
    }

    .card-body {
        font-size: 25px;
    }
</style>


<template>
    <div class="card">
        <h4>{{ title }}</h4>

        <div class="card-body">
            {{ count }}
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            url: {
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
        },
        data() {
            return {
                count: 0
            }
        },
        mounted() {
            this.load();
        },
        watch: {
            from() {
                this.load();
            },
            to() {
                this.load();
            }
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
                        if (data.success) {
                            vm.count = data.data.count
                        }
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
                        },
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }
                    }
                });
            }
        }
    }
</script>
