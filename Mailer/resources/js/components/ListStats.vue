<template>
    <div class="card">
        <div class="card-header">
            <h2>Stats</h2>
        </div>
        <div class="card-body">

            <div v-if="!loaded" class="preloader-wrap">
                <div class="preloader pl-xl pls-teal">
                    <svg class="pl-circular" viewBox="25 25 50 50">
                        <circle class="plc-path" cx="50" cy="50" r="20"></circle>
                    </svg>
                </div>
            </div>

            <div v-if="loaded">
                <table class="table">
                    <tbody>
                        <tr>
                            <td><strong>Number of subscribed:</strong></td>
                            <td>{{ subscribed }}</td>
                        </tr>
                        <tr>
                            <td><strong>Number of un-subscribed:</strong></td>
                            <td>{{ unSubscribed }}</td>
                        </tr>
                    </tbody>
                </table>
                <hr>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Unique subscribers</th>
                            <th>last 7 days</th>
                            <th>last 30 days</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Number of opened</strong></td>
                            <td>{{ openedWeek }}</td>
                            <td>{{ openedMonth }}</td>
                        </tr>
                        <tr>
                            <td><strong>Number of clicked</strong></td>
                            <td>{{ clickedWeek }}</td>
                            <td>{{ clickedMonth }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</template>

<script>
    export default {
        name: "list-stats",
        props: [
            "id"
        ],
        data() {
            return {
                loaded: false,

                subscribed: null,
                unSubscribed: null,
                openedWeek: null,
                openedMonth: null,
                clickedWeek: null,
                clickedMonth: null
            }
        },
        created() {
            let vm = this;

            setTimeout(() => {
                $.ajax({
                    url: "/list/stats/" + this.id,
                    success(data) {
                        vm.subscribed = data["subscribed"];
                        vm.unSubscribed = data["un-subscribed"];
                        vm.openedWeek = data["opened"]["7-days"];
                        vm.openedMonth = data["opened"]["30-days"];
                        vm.clickedWeek = data["clicked"]["7-days"];
                        vm.clickedMonth = data["clicked"]["30-days"];

                        vm.loaded = true;
                    }
                })
            }, 300);
        }
    }
</script>

<style>
    .preloader-wrap {
        text-align: center;
        padding-bottom: 20px;
    }
</style>
