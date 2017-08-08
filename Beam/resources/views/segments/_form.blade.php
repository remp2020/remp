<div id="segments-form">
    <segment-form></segment-form>
</div>

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

    let segment = {
        "name": '{!! $segment->name !!}' || null,
        "code": '{!! $segment->code !!}' || null,
        "active": {!! @json($segment->active) !!} || null,
        "rules": {!! $segment->rules->toJson() !!},
        "removedRules": [],
        "eventCategories": ["campaign"],
        "eventNames": {
            "campaign": ["display", "click", "close"]
        }
    }
    remplib.segmentForm.bind("#segment-form", segment);
</script>

@endpush