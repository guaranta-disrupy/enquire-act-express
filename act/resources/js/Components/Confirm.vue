<template>
    <jet-dialog-modal :show="modalShow">
        <template #title>
            <slot name="header">
                Confirm
            </slot>
        </template>

        <template #content>
            <slot name="body">
                Are you sure you want to proceed?
            </slot>
        </template>

        <template #footer>
            <jet-secondary-button @click="cancel" class="mr-2">
                <slot name="cancel">
                    nevermind
                </slot>
            </jet-secondary-button>
            <jet-danger-button @click="confirm">
                <slot name="confirm">
                    confirm
                </slot>
            </jet-danger-button>
        </template>
    </jet-dialog-modal>
</template>

<script>
    import JetDangerButton from '@/Jetstream/DangerButton.vue';
    import JetDialogModal from '@/Jetstream/DialogModal.vue';
    import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue';
    import { defineComponent } from 'vue';

    export default defineComponent({
        emits: ['cancelled', 'confirmed'],

        props: ['show'],

        components: {
            JetDangerButton,
            JetDialogModal,
            JetSecondaryButton
        },

        computed: {
            modalShow: {
                get: function() {
                    return this.show;
                },
                set: function(newValue){}
            }
        },

        methods: {
            cancel() {
                this.$emit('cancelled')
            },
            confirm() {
                this.$emit('confirmed')
            }
        }
    })
</script>

<style lang="css" scoped>
</style>
