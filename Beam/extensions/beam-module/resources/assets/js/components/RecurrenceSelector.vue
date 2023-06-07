<template>
    <div class="recurrence-selector">
        <div class="input-group">
            <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
            <date-time-picker label="Start date" v-model="start"></date-time-picker>
        </div>

        <div class="input-group m-t-15" style="margin-left: 44px">
            <div class="checkbox">
                <label>
                    <input v-model="repeat" type="checkbox" >
                    <i class="input-helper"></i>
                    Repeat
                </label>
            </div>
        </div>

        <div v-show="repeat" style="margin-left: 44px">
            <div class="m-t-20">
                <div class="fg-line">
                    <label class="fg-label">Repeat every</label>
                </div>

                <div class="row">
                    <div class="col-xs-2">
                        <div class="fg-line form-group">
                            <input v-model="repeatInterval" class="form-control input-sm" type="number" min="1" />
                        </div>
                    </div>
                    <div class="col-xs-3">
                        <div class="fg-line form-group">
                            <select v-model="repeatEvery" class="selectpicker">
                                <option v-for="option in repeatOptions" v-bind:value="option.value">
                                    {{ option.text }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div v-show="repeatEvery == 'week'" id="week-repeat-options">
                <div class="fg-line">
                    <label class="fg-label">Repeat on</label>
                </div>

                <div class="row">
                    <div class="col-xs-12">
                        <div class="fg-line form-group">
                            <div class="btn-group">
                                <button type="button" @click="switchWeekday(0)" :class="[weekRecurrence[0] ? 'btn-info' : 'btn-default']" class="btn">Mon</button>
                                <button type="button" @click="switchWeekday(1)" :class="[weekRecurrence[1] ? 'btn-info' : 'btn-default']" class="btn">Tue</button>
                                <button type="button" @click="switchWeekday(2)" :class="[weekRecurrence[2] ? 'btn-info' : 'btn-default']" class="btn">Wed</button>
                                <button type="button" @click="switchWeekday(3)" :class="[weekRecurrence[3] ? 'btn-info' : 'btn-default']" class="btn">Thr</button>
                                <button type="button" @click="switchWeekday(4)" :class="[weekRecurrence[4] ? 'btn-info' : 'btn-default']" class="btn">Fri</button>
                                <button type="button" @click="switchWeekday(5)" :class="[weekRecurrence[5] ? 'btn-info' : 'btn-default']" class="btn">Sat</button>
                                <button type="button" @click="switchWeekday(6)" :class="[weekRecurrence[6] ? 'btn-info' : 'btn-default']" class="btn">Sun</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="fg-line">
                    <label class="fg-label">Ends</label>
                </div>

                <div class="row">
                    <div class="col-md-2">
                        <div class="radio">
                            <label>
                                <input name="endson" v-model="endsOn" value="never" type="radio">
                                <i class="input-helper"></i>
                                Never
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <div class="radio">
                            <label>
                                <input name="endson" v-model="endsOn" value="on" type="radio">
                                <i class="input-helper"></i>
                                On
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <date-time-picker v-bind:is-disabled="!endsOnIsOn" v-model="endsOnDate"></date-time-picker>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <div class="radio">
                            <label>
                                <input name="endson" v-model="endsOn" value="after" type="radio">
                                <i class="input-helper"></i>
                                After
                            </label>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="">
                            <input :disabled="endsOn != 'after'" style="display: inline; width:36px;" v-model="repeatCount" class="p-l-5 m-r-10 form-control input-sm" type="number" min="1" />
                            {{repeatCount | pluralize('occurrence', 'occurrences')}}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
    .recurrence-selector {
    }
</style>

<script type="text/javascript">
    import RRule from 'rrule'
    import DateTimePicker from '@remp/js-commons/js/components/DateTimePickerWrapper'

    let repeat2freq = {
        'day': RRule.DAILY,
        'week': RRule.WEEKLY,
        'month': RRule.MONTHLY,
        'year': RRule.YEARLY
    }

    let freq2repeat = {}
    freq2repeat[RRule.DAILY] = 'day'
    freq2repeat[RRule.WEEKLY] = 'week'
    freq2repeat[RRule.MONTHLY] = 'month'
    freq2repeat[RRule.YEARLY] = 'year'

    function repeatEveryToRRuleFreq(val) {
        if (repeat2freq.hasOwnProperty(val)){
            return repeat2freq[val]
        } else {
            console.error("Unable to convert '" + val + "' to RRule frequency")
        }
    }

    function rRuleFreqToRepeatEvery(val) {
        if (freq2repeat.hasOwnProperty(val)){
            return freq2repeat[val]
        } else {
            console.error("Unable to convert '" + val + "' to repeat type")
        }
    }

    let rRuleWeekDays = [RRule.MO, RRule.TU, RRule.WE, RRule.TH, RRule.FR, RRule.SA, RRule.SU]

    export default {
        name: "RecurrenceSelector",
        components: {
            DateTimePicker
        },
        props: {
            startDate: {
                type: String,
                required: true
            },
            recurrence: {
                type: String,
                default: null
            },
            callback: {
                type: Function,
                required: true
            },
        },
        data() {
            return {
                start: this.startDate,
                repeat: false,
                repeatInterval: 1,
                repeatCount: 1,
                endsOnDate: moment().toISOString(),
                repeatEvery: 'day',
                endsOn: 'never',
                weekRecurrence: [
                    false, // Mon
                    false,
                    false,
                    false,
                    false,
                    false,
                    false
                ],
                repeatOptions: [
                    { text: 'day', value: 'day' },
                    { text: 'week', value: 'week' },
                    { text: 'month', value: 'month' },
                    { text: 'year', value: 'year' }
                ]
            }
        },
        created() {
            this.turnOnDefaultWeekDay(this.startDate)

            if (this.recurrence !== null) {
                this.repeat = true

                let rule = Rule.rrulestr(this.recurrence)

                this.repeatInterval = rule.options.interval
                this.repeatEvery = rRuleFreqToRepeatEvery(rule.options.freq)

                if (rule.options.byweekday !== null) {
                    for (let i = 0; i < 7; i++) {
                        this.weekRecurrence[i] = rule.options.byweekday.includes(i)
                    }
                }

                if (rule.options.count !== null) {
                    this.repeatCount = rule.options.count
                    this.endsOn = 'after'
                }
                else if (rule.options.until !== null){
                    this.endsOnDate = moment(rule.options.until).toISOString()
                    this.endsOn = 'on'
                }
            }
        },
        watch: {
            allProperties: function() {
                if (!this.repeat) {
                    this.callback(this.start, null)
                    return
                }

                let ruleProps = {
                    dtstart: new Date(this.start),
                    freq: repeatEveryToRRuleFreq(this.repeatEvery),
                    interval: this.repeatInterval
                }

                if (this.repeatEvery === 'week') {
                    let weekDays = []
                    for (let i=0; i < this.weekRecurrence.length; i++) {
                        if (this.weekRecurrence[i]){
                            weekDays.push(rRuleWeekDays[i])
                        }
                    }
                    ruleProps.byweekday = weekDays
                }
                if (this.endsOn === 'on') {
                    ruleProps.until = new Date(this.endsOnDate)
                } else if (this.endsOn === 'after') {
                    ruleProps.count = this.repeatCount
                }

                let rule = new RRule(ruleProps)
                this.callback(this.start, rule.toString())
            }
        },
        computed: {
            endsOnIsOn: function () {
                return this.endsOn === 'on'
            },
            allProperties() {
                return [
                    this.start,
                    this.repeat,
                    this.repeatEvery,
                    this.repeatInterval,
                    this.repeatCount,
                    this.weekRecurrence,
                    this.endsOn,
                    this.endsOnDate
                ].join()
            }
        },
        methods: {
            switchWeekday(dayNumber) {
                this.weekRecurrence.splice(dayNumber, 1, !this.weekRecurrence[dayNumber])
                let atLeastOneTrue = this.weekRecurrence.reduce((acc, val) => acc || val)
                if (!atLeastOneTrue) {
                    this.turnOnDefaultWeekDay(this.start)
                }
            },
            turnOnDefaultWeekDay(date) {
                let d = moment(date)
                for (let i = 0; i < 7; i++) {
                    this.weekRecurrence.splice(i, 1, i === (d.isoWeekday() - 1))
                }
            }
        }
    }
</script>
