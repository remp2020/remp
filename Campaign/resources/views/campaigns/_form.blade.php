@php

/* @var $campaign \App\Campaign */
/* @var $banners \Illuminate\Support\Collection */
/* @var $segments \Illuminate\Support\Collection */

$banners = $banners->map(function(\App\Banner $banner) {
   return ['id' => $banner->id, 'name' => $banner->name];
});

$segments = $segments->mapToGroups(function ($item) {
    return [$item->group->name => [$item->code => $item->name]];
})->mapWithKeys(function($item, $key) {
    return [$key => $item->collapse()];
});

@endphp

@push('head')
<link href="/assets/vendor/bootstrap-select/dist/css/bootstrap-select.min.css" rel="stylesheet">
<script src="/assets/vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
@endpush

<campaign-form></campaign-form>

@push('scripts')

<script type="text/javascript">
    Vue.component('v-select', {
        props : ['options', 'value', 'multiple', 'livesearch'],
        template : "<select :multiple='multiple' class='selectpicker' :data-live-search='livesearch'>"+
                        "<option :value='option.value' v-for='option in options'>@{{ option.label }}</option>"+
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

    Vue.component('campaign-form', {
        template: '#campaign-form-template',
        data: function() {
            return {
                "name": '{!! $campaign->name !!}' || null,
                "segmentId": '{!! $campaign->segment_id !!}' || null,
                "bannerId": {!! $campaign->banner_id !!} || null,
                "active": {!! $campaign->active !!} || null,
                "rules": {!! $campaign->rules->toJson() !!},
                "removedRules": [],

                "banners": {!! $banners->toJson(JSON_UNESCAPED_UNICODE) !!},
                "segments": {!! $segments->toJson(JSON_UNESCAPED_UNICODE) !!},
                "eventTypes": [
                    {
                        "category": "banner",
                        "action": "show",
                        "value": "banner|show",
                        "label": "banner / show"
                    },
                    {
                        "category": "banner",
                        "action": "click",
                        "value": "banner|click",
                        "label": "banner / click"
                    }
                ]
            }
        },
        methods: {
            addRule: function () {
                this.rules.push({
                    id: null,
                    count: null,
                    timespan: null,
                    event: null
                });
            },
            removeRule: function (index) {
                this.removedRules.push(this.rules[index].id);
                this.rules.splice(index, 1)
            }
        }
    });

    new Vue({
        el: '#campaign-form'
    });
</script>

@endpush