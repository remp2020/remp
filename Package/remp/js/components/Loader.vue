<template>
    <div v-if="yes" :class="{isCentered: 'centered'}" class="d-flex justify-content-center preloader pls-purple">
        <svg class="pl-circular" viewBox="25 25 50 50">
            <circle class="plc-path" cx="50" cy="50" r="20"></circle>
        </svg>
    </div>
</template>

<style>
    .centered {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    .d-flex {
        display: flex;
    }
</style>

<script>
    export default {
        props: {
            yes: {
                type: Boolean,
                required: true
            },
            isCentered: {
                type: Boolean,
                default: false
            }
        }
    }
</script>