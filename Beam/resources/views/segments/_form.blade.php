@push('head')
<link href="/assets/vendor/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet">
<script src="/assets/vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
@endpush

<segment-form></segment-form>

@push('scripts')

<script type="text/javascript">
    Vue.component('v-select', {
        props : ['options', 'value', 'multiple', 'livesearch'],
        template : "<select :multiple='multiple' class='selectpicker' :data-live-search='livesearch'>"+
        "<option :value='option.value || option' v-for='option in options'>@{{ option.label || option.value || option }}</option>"+
        "</select>",
        mounted : function () {
            var vm = this;
            $(this.$el).selectpicker('val', this.value !== null ? this.value : null);
            $(this.$el).on('changed.bs.select', function () {
                vm.$emit('input', $(this).val());
            });
        },
        updated : function () {
            $(this.$el).selectpicker('refresh');
        },
        destroyed : function () {
            $(this.$el).selectpicker('destroy');
        }
    });

    Vue.component('segment-form', {
        template: '#segment-form-template',
        data: function() {
            return {
                "name": '{!! $segment->name !!}' || null,
                "code": '{!! $segment->code !!}' || null,
                "active": {!! @json($segment->active) !!} || null,
                "rules": {!! $segment->rules->toJson() !!},
                "removedRules": [],
                "categories": ["banner"],
                "events": {
                    "banner": ["show", "click"]
                }
            }
        },
        methods: {
            addRule: function () {
                this.rules.push({
                    id: null,
                    count: null,
                    timespan: null,
                    event: null,
                    category: null,
                    fields: [{
                        key: null,
                        value: null
                    }]
                });
            },
            addField: function (ruleIndex) {
                this.rules[ruleIndex].fields.push({
                    key: null,
                    value: null
                })
            },
            removeRule: function (index) {
                this.removedRules.push(this.rules[index].id);
                this.rules.splice(index, 1)
            },
            removeField: function (ruleIndex, fieldIndex) {
                var fields = this.rules[ruleIndex].fields;
                fields.splice(fieldIndex, 1);
                if (fields.length === 0) {
                    this.addField(ruleIndex);
                }
            }
        }
    });

    new Vue({
        el: '#segment-form'
    });
</script>

@endpush