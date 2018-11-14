<template>
    <div class="row url-input-row">
        <div class="col-xs-10 col-sm-11">
            <input type="text" class="form-control url-input" name="urls[]" :value="url.url">
        </div>
        <div class="col-xs-2 col-sm-1">
            <span class="btn btn-sm palette-Grey-400 bg waves-effect" v-on:click="$parent.removeUrl(index)">
                <i class="zmdi zmdi-minus-square"></i>
            </span>
        </div>
    </div>
</template>

<script>
    export default {
        props: [
            "index",
            "url"
        ]
    }
</script>

<style scoped>
    .url-input {
        display: block;
    }

    .url-input-row {
        margin-bottom: 10px;
    }

    .btn {
        float: right;
    }
</style>
