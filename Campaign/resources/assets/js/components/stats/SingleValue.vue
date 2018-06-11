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

    .stats-error {
        position: absolute;
        top: 0;
        right: 0;
        width: 20px;
        height: 20px;
        background: red;
        color: #fff;
        display: none;
    }

    .preloader-wrapper {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: none;
    }

    .preloader-wrapper .preloader {
        position: absolute;
        top: 50%;
        left: 50%;
        margin-left: -20px;
        margin-top: -20px;
    }
</style>


<template>
    <div class="card">
        <div class="preloader-wrapper">
            <div class="preloader">
                <svg class="pl-circular" viewBox="25 25 50 50">
                    <circle class="plc-path" cx="50" cy="50" r="20" />
                </svg>
            </div>
        </div>
        <div class="stats-error">!</div>
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

                $(this.$el).find('.preloader-wrapper').show();
                $(vm.$el).find('.stats-error').hide();

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

                        $(vm.$el).find('.preloader-wrapper').fadeOut();

                        if (data.success) {
                            vm.count = data.data.count
                        } else {
                            $(vm.$el).find('.stats-error').show().attr('title', data.message);
                        }
                    },
                    error(xhr, status, error) {
                        $(vm.$el).find('.stats-error').show().attr('title',error);
                        $(vm.$el).find('.preloader-wrapper').fadeOut();
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
