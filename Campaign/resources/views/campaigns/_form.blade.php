@php

/* @var $campaign \App\Campaign */
/* @var $banners \Illuminate\Support\Collection */
/* @var $segments \Illuminate\Support\Collection */

$banners = $banners->map(function(\App\Banner $banner) {
   return ['id' => $banner->id, 'name' => $banner->name];
});

$segments = $segments->mapToGroups(function ($item) {
    return [$item->group->name => [$item->code => $item]];
})->mapWithKeys(function($item, $key) {
    return [$key => $item->collapse()];
});

$segmentMap = $segments->flatten()->mapWithKeys(function ($item) {
    return [$item->code => $item->name];
})

@endphp

<campaign-form></campaign-form>

@push('scripts')

<script type="text/javascript">
    Vue.component('v-select', {
        props : ['options', 'opLabel', 'opValue', 'value', 'multiple', 'livesearch'],
        template : "<select :multiple='multiple' class='selectpicker' :data-live-search='livesearch'>"+
                        "<option :value='option[opValue]' v-for='option in options'>@{{ option[opLabel] }}</option>"+
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
                "segments": {!! isset($selectedSegments) ? $selectedSegments->toJson(JSON_UNESCAPED_UNICODE) : $campaign->segments->toJson(JSON_UNESCAPED_UNICODE) !!},
                "bannerId": {!! @json($campaign->banner_id) !!} || null,
                "active": {!! @json($campaign->active) !!} || null,

                "banners": {!! $banners->toJson(JSON_UNESCAPED_UNICODE) !!},
                "availableSegments": {!! $segments->toJson(JSON_UNESCAPED_UNICODE) !!},
                "addedSegment": null,
                "removedSegments": [],
                "segmentMap": {!! $segmentMap->toJson(JSON_UNESCAPED_UNICODE) !!},
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
            'selectSegment': function() {
                this.segments.push(this.addedSegment);
            },
            'removeSegment': function(index) {
                var toRemove = this.segments[index];
                this.segments.splice(index, 1);
                this.removedSegments.push(toRemove.id);
            }
        }
    });

    new Vue({
        el: '#campaign-form'
    });
</script>

@endpush