<template>
    <AppLayout title="Testing">
        <div class="max-w-7xl mx-auto py-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-3 gap-6">
                <div class="mt-5 md:mt-0">
                    <div class="bg-white shadow rounded-lg border-gray-300 border">
                        <div class="border-b border-gray-300 p-2">
                            <span class="text-xs text-gray-600 bg-gray-100 rounded p-1">
                                #1
                            </span>
                            <span class="text-xs text-gray-600 bg-gray-100 rounded p-1 ml-1">
                                Index: 4
                            </span>
                        </div>
                        <div class="px-4 py-5">
                            <div class="text-sm py-1">
                                <span v-html="text" class="text-sm"></span>
                            </div>
                            <div class="hidden sm:block">
                                <div class="pb-3 mt-3">
                                    <div class="border-t border-gray-200" />
                                </div>
                            </div>
                            <div class="grid grid-cols-4 gap-4">
                                <div class="text-xs text-gray-500">
                                    Equivalent
                                </div>
                                <div class="col-span-3 text-sm text-gray-600 pt-2 pb-3">
                                    {{ sanitize(text) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    Logic
                                </div>
                                <div class="col-span-3 text-sm text-gray-600 pt-2 pb-3">
                                    <code>
                                        {{ logic }}
                                    </code>
                                </div>
                                <div class="col-span-4">
                                    
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-300 p-2">
                            <a href="javascript:;" class="text-sm text-blue-400 mr-2 inline-flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg> Delete
                            </a>
                            <a href="javascript:;" class="text-sm text-blue-400 inline-flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                    <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                                </svg> Edit
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';
import Helpers from '../../Mixins/Helpers';
import { ref } from 'vue';

const text = ref("<p>The <strong>feelings</strong> I <strong>associate most closely</strong> with <strong>Nestle Milo</strong> are…</p>");

const logic = ref("(q2==2) AND (q3==3) AND (q4==1) AND (segment==1) AND (top_hz==56)");

const { sanitize } = Helpers();
</script>