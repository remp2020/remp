<style scoped>
    .card {
        text-align: center;
        padding: 10px 0;
    }

    h4 {
        margin-top: 0;
        margin-bottom: 0;
        font-size: 14px;
    }

    strong {
        font-size: 12px;
    }

    .card-body {
        font-size: 20px;
    }
</style>


<template>
    <div class="card">
        <h4>{{ title }}</h4>
        <strong>&nbsp;{{ subtitle }}&nbsp;</strong>

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
            subtitle: {
                type: String,
                required: false
            },
            from: {
                type: String,
                required: true
            },
            to: {
                type: String,
                required: true
            },
            normalized: {
                type: Boolean,
                required: false,
                default: false
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
                        normalized: vm.normalized,
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
