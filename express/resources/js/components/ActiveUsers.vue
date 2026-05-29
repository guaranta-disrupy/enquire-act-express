<template>
    <div class="active-users">
        <!-- Compact View - Just Avatars -->
        <div v-if="compact" class="flex items-center space-x-2 mt-[-10px]">
            <div class="flex -space-x-2">
                <div
                    v-for="user in displayUsers"
                    :key="user.id"
                    :title="user.name"
                    class="relative inline-block flex-shrink-0"
                >
                    <img
                        v-if="user.profile_photo_url"
                        :src="user.profile_photo_url"
                        :alt="user.name"
                        class="w-12 h-12 rounded-full border-2 border-gray-300 object-cover aspect-square"
                    />
                    <div
                        v-else
                        class="w-12 h-12 rounded-full border-2 border-gray-300 bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center text-white text-xs font-semibold aspect-square"
                    >
                        {{ getUserInitials(user.name) }}
                    </div>
                    <span
                        class="absolute bottom-0 right-0 block h-3 w-3 rounded-full bg-green-400 ring-2 ring-white"
                    ></span>
                </div>
            </div>
                <div class="relative inline-block flex-shrink-0">
                    <!-- <img
                        v-if="currentUser?.profile_photo_url"
                        :src="currentUser.profile_photo_url"
                        :alt="currentUser.name"
                        class="w-12 h-12 rounded-full border-2 border-gray-300 object-cover aspect-square"
                    /> -->
                    <div
                        class="w-12 h-12 rounded-full border-2 border-gray-400 bg-gradient-to-br from-yellow-100 to-green-100 flex items-center justify-center text-black font-semibold aspect-square"
                    >
                        {{ getUserInitials(currentUser?.name) }}
                    </div>
                </div>
        </div>

        <!-- Full View - List with Details -->
        <div v-else class="space-y-2">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Active Users
                </h3>
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"
                >
                    <span class="w-2 h-2 mr-1 bg-green-500 rounded-full animate-pulse"></span>
                    {{ activeUserCount + 1 }} online
                </span>
            </div>

            <!-- Current User -->
            <div class="flex items-center space-x-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="relative">
                    <img
                        v-if="currentUser?.profile_photo_url"
                        :src="currentUser.profile_photo_url"
                        :alt="currentUser.name"
                        class="w-10 h-10 rounded-full"
                    />
                    <div
                        v-else
                        class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center text-white text-sm font-semibold"
                    >
                        {{ getUserInitials(currentUser?.name) }}
                    </div>
                    <span
                        class="absolute bottom-0 right-0 block h-3 w-3 rounded-full bg-green-400 ring-2 ring-white dark:ring-gray-800"
                    ></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                        {{ currentUser?.name }} (You)
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ currentUser?.email }}
                    </p>
                </div>
            </div>

            <!-- Other Users -->
            <div v-if="otherUsers.length > 0" class="space-y-2">
                <div
                    v-for="user in otherUsers"
                    :key="user.id"
                    class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm"
                >
                    <div class="relative">
                        <img
                            v-if="user.profile_photo_url"
                            :src="user.profile_photo_url"
                            :alt="user.name"
                            class="w-20 h-20 rounded-full"
                        />
                        <div
                            v-else
                            class="w-20 h-20 rounded-full bg-gradient-to-br from-gray-500 to-gray-700 flex items-center justify-center text-white text-sm font-semibold"
                        >
                            {{ getUserInitials(user.name) }}
                        </div>
                        <span
                            class="absolute bottom-0 right-0 block h-3 w-3 rounded-full bg-green-400 ring-2 ring-white dark:ring-gray-800"
                        ></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                            {{ user.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ user.email }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm"
            >
                No other users are currently active
            </div>

            <!-- Connection Status -->
            <div v-if="!isConnected && !isJoining" class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    ⚠️ Disconnected from real-time updates
                </p>
            </div>

            <div v-if="error" class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <p class="text-sm text-red-800 dark:text-red-200">
                    ❌ {{ error }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    activeUsers: {
        type: Array,
        default: () => [],
    },
    currentUser: {
        type: Object,
        default: null,
    },
    activeUserCount: {
        type: Number,
        default: 0,
    },
    isConnected: {
        type: Boolean,
        default: false,
    },
    isJoining: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
    compact: {
        type: Boolean,
        default: false,
    },
    maxDisplay: {
        type: Number,
        default: 5,
    },
});

// Filter out current user from active users
const otherUsers = computed(() => {
    if (!props.currentUser) return props.activeUsers;
    return props.activeUsers.filter(user => user.id !== props.currentUser.id);
});

// Limit displayed users in compact mode
const displayUsers = computed(() => {
    return otherUsers.value.slice(0, props.maxDisplay);
});

// Get user initials for avatar fallback
const getUserInitials = (name) => {
    if (!name) return '?';
    const parts = name.split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};
</script>

<style scoped>
.active-users {
    /* Add any additional custom styles here */
}
</style>

