<template>
    <div class="date-selector">

        <div class="panel-group" role="tablist" aria-multiselectable="false">
            <div class="panel panel-collapse" style="background: transparent">
                <div class="panel-heading" role="tab">
                    <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                            {{ toggleText }}
                        </a>
                    </h4>
                </div>
                <div id="collapseOne" class="collapse m-t-5 m-10" role="tabpanel" aria-labelledby="headingOne" aria-expanded="false">
                        <div>
                            <a class="clickable f-14 p-5" :class="selectedTab == 1 ? 'activeSwitch' : ''" @click="switchTab(1)">Quick</a>
                            <a class="clickable f-14 p-5" :class="selectedTab == 2 ? 'activeSwitch' : ''" @click="switchTab(2)">Absolute</a>
                        </div>

                        <div style="padding-top: 20px">
                            <div class="row" v-if="selectedTab == 1">
                                <ul style="list-style: none;" class="pull-left p-l-15 p-r-15" v-for="innerSelection in quickSelection">
                                    <li v-for="selection in innerSelection">
                                        <a :class="selection.selected ? 'underline' : ''" class="clickable" @click="(selectQuick(selection))">{{selection.name}}</a>
                                    </li>
                                </ul>
                            </div>

                            <div v-if="selectedTab == 2" class="row" >
                                <div class="col-md-6">
                                    <div class="input-group m-b-10">
                                        <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                                        <date-time-picker label="From" v-model="absoluteFrom"></date-time-picker>
                                    </div>

                                    <div class="input-group m-b-10">
                                        <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                                        <date-time-picker label="To" v-model="absoluteTo"></date-time-picker>
                                    </div>

                                    <button style="margin-left: 44px" class="btn btn-info waves-effect" @click="selectAbsolute(absoluteFrom, absoluteTo)">Go</button>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
    .clickable {
        cursor: pointer;
    }
    .activeSwitch {
        background-color: #00acc1;
        border-radius: 3px;
        color: #fff;
    }
    .underline {
        text-decoration: underline;
    }
</style>

<script type="text/javascript">
    import dateTimePicker from './DateTimePickerWrapper.vue';

    const originalQuickSelection = [
        [
            {name: 'Today', carbonFrom: 'today', carbonTo: 'now', selected: false},
            {name: 'Yesterday', carbonFrom: 'yesterday', carbonTo: 'today', selected: false},
            {name: 'This week', carbonFrom: 'monday', carbonTo: 'now', selected: false},
            {name: 'This month', carbonFrom: 'first day of this month', carbonTo: 'now', selected: false},
            {name: 'This year', carbonFrom: 'first day of january this year', carbonTo: 'now', selected: false},
            {name: 'Week to date', carbonFrom: 'now - 1 week', carbonTo: 'now', selected: false},
            {name: 'Month to date', carbonFrom: 'now - 1 month', carbonTo: 'now', selected: false},
            {name: 'Year to date', carbonFrom: 'now - 1 year', carbonTo: 'now', selected: false},
        ],
        [
            {name: 'Last 5 minutes', carbonFrom: 'now - 5 mins', carbonTo: 'now', selected: false},
            {name: 'Last 15 minutes', carbonFrom: 'now - 15 mins', carbonTo: 'now', selected: false},
            {name: 'Last 30 minutes', carbonFrom: 'now - 30 mins', carbonTo: 'now', selected: false},
            {name: 'Last 1 hour', carbonFrom: 'now - 1 hour', carbonTo: 'now', selected: false},
            {name: 'Last 3 hours', carbonFrom: 'now - 3 hours', carbonTo: 'now', selected: false},
            {name: 'Last 6 hours', carbonFrom: 'now - 6 hours', carbonTo: 'now', selected: false},
            {name: 'Last 12 hours', carbonFrom: 'now - 12 hours', carbonTo: 'now', selected: false},
            {name: 'Last 24 hours', carbonFrom: 'now - 24 hours', carbonTo: 'now', selected: false},
        ]
    ]

    let props = {
        from: {
            type: String
        },
        to: {
            type: String
        },
        header: String,
        callback: Function
    };

    export default {
        name: "DateSelector",
        components: {
            dateTimePicker
        },
        props: props,
        data() {
            return {
                absoluteFrom: null,
                absoluteTo: null,
                toggleText: this.header,
                selectedTab: null,
                quickSelection: originalQuickSelection
            }
        },
        mounted() {
            let isQuick = false
            let that = this
            this.quickSelection.forEach(function (inner) {
                inner.forEach(function (selection) {
                    if (selection.carbonFrom === that.from && selection.carbonTo === that.to) {
                        selection.selected = true
                        isQuick = true
                    } else {
                        selection.selected = false
                    }
                })
            })

            if (!isQuick) {
                this.absoluteFrom = this.from
                this.absoluteTo = this.to
                this.selectedTab = 2
            }  else {
                this.absoluteFrom = null
                this.absoluteTo = null
                this.selectedTab = 1
            }

        },
        methods: {
            selectQuick(selection) {
                this.underscoreQuickSelection(selection)
                this.callback(selection.carbonFrom, selection.carbonTo)
            },
            selectAbsolute(carbonFrom, carbonTo) {
                this.callback(carbonFrom, carbonTo)
            },
            switchTab(num) {
                this.selectedTab = num
            },
            underscoreQuickSelection(selection) {
                this.quickSelection = originalQuickSelection.map(function (row) {
                    return row.map(function(cell) {
                        cell.selected = cell.name === selection.name
                        return cell
                    })
                })
            }
        }
    }
</script>
