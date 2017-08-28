<template>
    <div class="row" v-html="htmlContent" style="overflow-x: scroll"></div>
</template>

<script>
    export default {
        name: 'mail-preview',
        computed: {
            htmlContent: function() {
                let layout = this.$parent.htmlLayout;
                let content = this.$parent.htmlContent;
                if (!layout) {
                    return content;
                }
                return layout.replace("{{ content|raw }}", content);
            }
        }
    }
</script>