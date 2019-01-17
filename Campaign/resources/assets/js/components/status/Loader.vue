<template>
    <div v-if="yes" class="d-flex justify-content-center preloader pls-purple">
        <svg class="pl-circular" viewBox="25 25 50 50">
            <circle class="plc-path" cx="50" cy="50" r="20"></circle>
        </svg>
    </div>
</template>

<style>
    .d-flex {
        display: flex;
    }
</style>

<script>
    export default {
        props: ['yes'],
    }
</script>