<template>
    <div style="position: relative" class="date-selector">
        <a class="toggle-link clickable" @click="isToggled = !isToggled">{{ toggleText }}</a>

        <transition name="fade">
            <div v-if="isToggled" class="m-l-5 m-b-5">
                <ul class="tab-nav">
                    <li :class="selectedTab == 1 ? 'active' : ''">
                        <a @click="switchTab(1)" class="clickable">Quick ranges</a>
                    </li>
                    <li :class="selectedTab == 2 ? 'active' : ''">
                        <a @click="switchTab(2)" class="clickable">Absolute range</a>
                    </li>
                </ul>

                <div class="m-t-20">
                    <div v-if="selectedTab == 1" class="row">
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
        </transition>

    </div>
</template>

<style scoped>
    .date-selector .toggle-link:before {
        font-family: 'Material-Design-Iconic-Font';
        font-size: 17px;
        position: absolute;
        left: 6px;
        -webkit-transition: all;
        -o-transition: all;
        transition: all;
        -webkit-transition-duration: 300ms;
        transition-duration: 300ms;
        top: -2px;
    }
    .date-selector .toggle-link:before {
        content: "\f337";
        -webkit-transform: scale(1);
        -ms-transform: scale(1);
        -o-transform: scale(1);
        transform: scale(1);
    }
    .date-selector .toggle-link {
        display: block;
        margin-left: 28px;
        font-size: 14px
    }
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
    .fade-enter-active {
        transition: opacity .5s;
    }
    .fade-leave-active {
        transition: opacity 0s;
    }
    .fade-enter, .fade-leave-to {
        opacity: 0;
    }
</style>

<script type="text/javascript">
    import dateTimePicker from './DateTimePickerWrapper.vue';

    const originalQuickSelection = [
        [
            {name: 'Today', carbonFrom: 'today', carbonTo: 'tomorrow - 1 sec', selected: false},
            {name: 'Today so far', carbonFrom: 'today', carbonTo: 'now', selected: false},
            {name: 'This week', carbonFrom: 'monday', carbonTo: 'next monday - 1 sec', selected: false},
            {name: 'This month', carbonFrom: 'first day of this month', carbonTo: 'first day of next month - 1 sec', selected: false},
            {name: 'This year', carbonFrom: 'first day of january this year', carbonTo: 'first day of january next year - 1 sec', selected: false},
            {name: 'Week to date', carbonFrom: 'now - 1 week', carbonTo: 'now', selected: false},
            {name: 'Month to date', carbonFrom: 'now - 1 month', carbonTo: 'now', selected: false},
            {name: 'Year to date', carbonFrom: 'now - 1 year', carbonTo: 'now', selected: false},
        ],
        [
            {name: 'Yesterday', carbonFrom: 'yesterday', carbonTo: 'today - 1 sec', selected: false},
            {name: 'Day before yesterday', carbonFrom: 'yesterday - 1 day', carbonTo: 'yesterday - 1 sec', selected: false},
            {name: 'This day last week', carbonFrom: 'today - 1 week', carbonTo: 'today - 6 days - 1 sec', selected: false},
            {name: 'Previous week', carbonFrom: 'last monday - 1 week', carbonTo: 'last monday - 1 sec', selected: false},
            {name: 'Previous month', carbonFrom: 'first day of last month', carbonTo: 'first day of this month - 1 sec', selected: false},
            {name: 'Previous year', carbonFrom: 'first day of last year', carbonTo: 'first day of this year - 1 sec', selected: false},
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
        ],
        [
            {name: 'Last 30 days', carbonFrom: 'now - 30 days', carbonTo: 'now', selected: false},
            {name: 'Last 60 days', carbonFrom: 'now - 60 days', carbonTo: 'now', selected: false},
            {name: 'Last 90 days', carbonFrom: 'now - 90 days', carbonTo: 'now', selected: false},
            {name: 'Last 6 months', carbonFrom: 'now - 6 months', carbonTo: 'now', selected: false},
            {name: 'Last 1 year', carbonFrom: 'now - 1 year', carbonTo: 'now', selected: false},
            {name: 'Last 2 years', carbonFrom: 'now - 2 years', carbonTo: 'now', selected: false},
            {name: 'Last 5 years', carbonFrom: 'now - 5 years', carbonTo: 'now', selected: false},
        ]
    ]

    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    function formatAbsoluteToggleText(from, to) {
        return capitalizeFirstLetter(moment(from).format('llll')) + " to " + capitalizeFirstLetter(moment(to).format('llll'))
    }

    let props = {
        from: {
            type: String,
            required: true
        },
        to: {
            type: String,
            required: true
        },
        callback: {
            type: Function,
            required: true
        },
    };

    export default {
        name: "DateSelector",
        components: {
            dateTimePicker
        },
        props: props,
        data() {
            return {
                isToggled: false,
                absoluteFrom: null,
                absoluteTo: null,
                toggleText: "Select date",
                selectedTab: null,
                quickSelection: originalQuickSelection
            }
        },
        mounted() {
            let isQuick = false
            let that = this
            let text = null
            this.quickSelection.forEach(function (inner) {
                inner.forEach(function (selection) {
                    if (selection.carbonFrom === that.from && selection.carbonTo === that.to) {
                        selection.selected = true
                        isQuick = true
                        text = selection.name
                    } else {
                        selection.selected = false
                    }
                })
            })

            if (!isQuick) {
                this.absoluteFrom = this.from
                this.absoluteTo = this.to
                text = formatAbsoluteToggleText(this.from, this.to)
                this.selectedTab = 2
            }  else {
                this.absoluteFrom = null
                this.absoluteTo = null
                this.selectedTab = 1
            }
            this.toggleText = text
        },
        created() {

        },
        methods: {
            selectQuick(selection) {
                this.underscoreQuickSelection(selection)
                this.callback(selection.carbonFrom, selection.carbonTo)
                this.toggleText = selection.name
            },
            selectAbsolute(from, to) {
                this.callback(from, to)
                this.toggleText = formatAbsoluteToggleText(from, to)
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
