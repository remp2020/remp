<template>
    <div style="position: relative" class="date-selector">
        <a class="toggle-link clickable" @click="isToggled = !isToggled">{{ toggleText }}</a>

        <transition name="fade">
            <div v-if="isToggled" class="m-l-5 m-b-5">
                <ul class="tab-nav" style="overflow: hidden">
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
                quickSelection: [
                    [
                        {name: 'Last 2 days', carbonFrom: 'today - 2 days', carbonTo: 'now', selected: false},
                        {name: 'Last 7 days', carbonFrom: 'today - 7 days', carbonTo: 'now', selected: false},
                        {name: 'Last 30 days', carbonFrom: 'today - 30 days', carbonTo: 'now', selected: false},
                        {name: 'Last 60 days', carbonFrom: 'today - 60 days', carbonTo: 'now', selected: false},
                        {name: 'Last 90 days', carbonFrom: 'today - 90 days', carbonTo: 'now', selected: false},
                        {name: 'Last 6 months', carbonFrom: 'today - 6 months', carbonTo: 'now', selected: false},
                        {name: 'Last year', carbonFrom: 'today - 1 year', carbonTo: 'now', selected: false},
                        {name: 'Last 5 years', carbonFrom: 'today - 5 years', carbonTo: 'now', selected: false},
                    ],
                    [
                        {name: 'Today', carbonFrom: 'today midnight', carbonTo: 'tomorrow midnight - 1 sec', selected: false},
                        {name: 'Yesterday', carbonFrom: 'yesterday midnight', carbonTo: 'today midnight - 1 sec', selected: false},
                        {name: 'This day last week', carbonFrom: 'today midnight - 1 week', carbonTo: 'today midnight - 6 days - 1 sec', selected: false},
                        {name: 'This week', carbonFrom: 'this week midnight', carbonTo: 'next week midnight - 1 sec', selected: false},
                        {name: 'Previous week', carbonFrom: 'last week midnight - 1 week', carbonTo: 'last week midnight - 1 sec', selected: false},
                        {name: 'This month', carbonFrom: 'first day of this month midnight', carbonTo: 'first day of next month midnight - 1 sec', selected: false},
                        {name: 'Previous month', carbonFrom: 'first day of last month midnight', carbonTo: 'first day of this month midnight - 1 sec', selected: false},
                        {name: 'This year', carbonFrom: 'first day of january midnight this year', carbonTo: 'first day of january midnight next year - 1 sec', selected: false},
                        {name: 'Previous year', carbonFrom: 'first day of last year midnight', carbonTo: 'first day of this year midnight - 1 sec', selected: false},
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
                ]
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
                this.absoluteFrom = moment().utc().format()
                this.absoluteTo = moment().utc().format()
                this.selectedTab = 1
            }
            this.toggleText = text
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
                this.quickSelection = this.quickSelection.map(function (row) {
                    return row.map(function(cell) {
                        cell.selected = cell.name === selection.name
                        return cell
                    })
                })
            }
        }
    }
</script>
