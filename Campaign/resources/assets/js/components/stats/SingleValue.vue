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
    }

    .preloader-wrapper {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
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
        <div v-if="loading" class="preloader-wrapper">
            <div class="preloader">
                <svg class="pl-circular" viewBox="25 25 50 50">
                    <circle class="plc-path" cx="50" cy="50" r="20" />
                </svg>
            </div>
        </div>
        <div v-if="error" class="stats-error" :title="errorText">!</div>
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
            title: {
                type: String,
                required: true
            },
            subtitle: {
                type: String,
                required: false
            },
            loading: {
                type: Boolean,
                default: true
            }
        },
        data() {
            return {
                error: false,
                errorText: "",
                count: 0
            }
        },
        methods: {
            handleResult(result) {
                this.error = false;
                this.errorText = "";

                if (result.success === true) {
                    this.count = result.data.count;
                } else {
                    this.error = true;
                    this.errorText = result.message;
                }
            }
        }
    }
</script>
