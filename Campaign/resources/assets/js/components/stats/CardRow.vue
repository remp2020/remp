<template>
    <div class="list-group-item media">
        <a href="">
            <div class="media-body ns-item">
                <small>{{ variant.variant }}</small>
                <h3 class="m-t-5">
                    {{ count }}
                </h3>
            </div>
        </a>
    </div>
</template>

<script>
    let props = {
        variant: {
            type: Object,
            required: true
        },
        type: {
            type: String,
            required: true
        }
    }

    export default {
        props: props,
        data() {
            return {
                count: 0
            }
        },
        created() {
            this.load();
        },
        methods: {
            load() {
                var vm = this;

                $.ajax({
                    method: 'POST',
                    url: '/campaigns/stats/variant/' + vm.variant.id + '/count',
                    data: {
                        type: vm.type,
                        _token: document.head.querySelector("[name=csrf-token]").content
                    },
                    dataType: 'JSON',
                    success(data, stats) {
                        vm.loaded = true;

                        vm.count = data.data[0].count;
                    }
                })
            }
        }
    }
</script>
