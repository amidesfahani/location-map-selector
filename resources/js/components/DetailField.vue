<template>
    <panel-item :field="field.value" v-if="field.value">
        <template slot="value">
        </template>
    </panel-item>
</template>

<script>
export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],
    computed: {
    },
    methods: {
    },
}
</script>

<style>
</style>
