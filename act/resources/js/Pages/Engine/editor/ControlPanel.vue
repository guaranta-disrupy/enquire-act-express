<template>
    <div class="flex flex-col h-full">
        <!-- Sticky Navigation Controls -->
        <div
            class="flex-shrink-0 p-3 pt-0 bg-white sticky top-0 z-10 pr-4"
            v-if="!isToolbar"
        >
            <details class="control-group" open>
                <summary
                    class="font-semibold text-gray-800 cursor-pointer hover:text-[#15919A] flex items-center"
                >
                    <svg
                        class="w-4 h-4 mr-2 transition-transform duration-200 details-arrow"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                    Navigation
                </summary>
                <div class="mt-3 pl-2 grid gap-2 grid-cols-3">
                    <!-- Test Selector -->
                    <div class="col-span-2">
                        <label
                            for="test-selector"
                            class="block text-xs font-medium text-gray-700 mb-1"
                            >Round</label
                        >
                        <select
                            id="test-selector"
                            v-model="selectedTest"
                            @change="selectedTestChange(selectedTest)"
                            class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-xs focus:ring-[#15919A] focus:border-[#15919A] text-sm dark:text-gray-700 text-base"
                        >
                            <option
                                v-for="(test, index) in tests"
                                :key="index"
                                :value="index"
                            >
                                R# {{ index + 1 }}:
                                {{
                                    test.config.type === "descriptive"
                                        ? "Descriptive"
                                        : `${
                                              test.config.practice_trial
                                                  ? "Practice"
                                                  : "Main"
                                          } (${
                                              test.trials?.length || 0
                                          } trials)`
                                }}
                            </option>
                        </select>
                    </div>

                    <!-- Trial Selector -->
                    <div class="col-span-1">
                        <label
                            for="trial-selector"
                            class="block text-xs font-medium text-gray-700 mb-1"
                            >Trial</label
                        >
                        <select
                            id="trial-selector"
                            v-model="selectedTrial"
                            class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-xs focus:ring-[#15919A] focus:border-[#15919A] text-sm dark:text-gray-700 text-base"
                        >
                            <option
                                v-for="(trial, index) in trials"
                                :key="index"
                                :value="index"
                            >
                                {{ index + 1 }}
                            </option>
                        </select>
                    </div>
                </div>
                <!-- Prev/Next navigation -->
                <div class="mt-3 pl-2 grid gap-2 grid-cols-3">
                    <div class="col-span-2 flex gap-2 text-white">
                        <button
                            type="button"
                            @click="goToPrevious()"
                            :disabled="isAtFirstStep"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-700 hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed w-full active:scale-95 transition-all duration-200 uppercase"
                            title="Previous"
                        >
                            Previous
                        </button>
                        <button
                            type="button"
                            @click="goToNext()"
                            :disabled="isAtLastStep"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium bg-[#257556] hover:bg-[#1d5e45] disabled:opacity-50 disabled:cursor-not-allowed w-full active:scale-95 transition-all duration-200 uppercase"
                            title="Next"
                        >
                            Next
                        </button>
                    </div>
                    <div class="col-span-1">
                        <label
                            for="trial-selector"
                            class="block text-xs font-medium text-gray-700 mb-1"
                            >BRC</label
                        >
                        <select
                            id="trial-selector"
                            v-model="selectedBRC"
                            @change="
                                emit('update-brc', [
                                    selectedBRC,
                                    selectedTest,
                                    selectedTrial,
                                ])
                            "
                            class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-xs focus:ring-[#15919A] focus:border-[#15919A] text-sm dark:text-gray-700 text-base"
                        >
                            <option
                                v-for="el in maxTotalVariables"
                                :key="el"
                                :value="el"
                            >
                                {{ el }}
                            </option>
                        </select>
                    </div>
                </div>
            </details>
        </div>

        <!-- Scrollable Content -->
        <div class="flex-grow p-3 space-y-4 overflow-y-auto">
            <div v-if="isToolbar" class="scale-75">
                <div class="flex items-center gap-3">
                    <!-- Device Type Icons -->
                    <div class="flex-shrink-0 flex items-center gap-2">
                        <!-- Desktop Icon -->
                        <button
                            type="button"
                            @click="activeDevice = 'desktop'"
                            :class="[
                                'p-2 rounded-md transition-all duration-200',
                                activeDevice === 'desktop'
                                    ? 'bg-[#15919A] text-white shadow-md'
                                    : 'bg-white text-gray-600 hover:bg-gray-50 hover:text-[#15919A] border border-gray-300',
                            ]"
                            title="Desktop"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="lucide lucide-monitor-icon lucide-monitor"
                            >
                                <rect
                                    width="20"
                                    height="14"
                                    x="2"
                                    y="3"
                                    rx="2"
                                />
                                <line x1="8" x2="16" y1="21" y2="21" />
                                <line x1="12" x2="12" y1="17" y2="21" />
                            </svg>
                        </button>

                        <!-- Tablet Icon -->
                        <button
                            type="button"
                            @click="activeDevice = 'tablet'"
                            :class="[
                                'p-2 rounded-md transition-all duration-200',
                                activeDevice === 'tablet'
                                    ? 'bg-[#15919A] text-white shadow-md'
                                    : 'bg-white text-gray-600 hover:bg-gray-50 hover:text-[#15919A] border border-gray-300',
                            ]"
                            title="Tablet"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="lucide lucide-tablet-icon lucide-tablet"
                            >
                                <rect
                                    width="16"
                                    height="20"
                                    x="4"
                                    y="2"
                                    rx="2"
                                    ry="2"
                                />
                                <line x1="12" x2="12.01" y1="18" y2="18" />
                            </svg>
                        </button>

                        <!-- Mobile Icon -->
                        <button
                            type="button"
                            @click="activeDevice = 'mobile'"
                            :class="[
                                'p-2 rounded-md transition-all duration-200',
                                activeDevice === 'mobile'
                                    ? 'bg-[#15919A] text-white shadow-md'
                                    : 'bg-white text-gray-600 hover:bg-gray-50 hover:text-[#15919A] border border-gray-300',
                            ]"
                            title="Mobile"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="lucide lucide-tablet-smartphone-icon lucide-tablet-smartphone"
                            >
                                <rect
                                    width="10"
                                    height="14"
                                    x="3"
                                    y="8"
                                    rx="2"
                                />
                                <path
                                    d="M5 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2h-2.4"
                                />
                                <path d="M8 18h.01" />
                            </svg>
                        </button>
                    </div>

                    <!-- Resolution Dropdown -->
                    <div class="flex-1 w-[350px]">
                        <!-- Desktop Resolution -->
                        <div v-if="activeDevice === 'desktop'" class="relative">
                            <button
                                ref="desktopTrigger"
                                type="button"
                                @click="toggleDropdown('desktop')"
                                class="w-full px-4 py-3 text-left bg-white border border-gray-200 rounded-xl shadow-sm hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#15919A] focus:border-[#15919A] transition-all duration-200 flex items-center justify-between"
                            >
                                <span class="text-sm font-medium text-gray-900">
                                    {{ selectedDesktop.name }} —
                                    {{ selectedDesktop.aspectRatio }}
                                </span>
                                <svg
                                    class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                    :class="{
                                        'rotate-180': dropdownOpen.desktop,
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </button>

                            <Teleport to="body">
                                <div
                                    v-if="dropdownOpen.desktop"
                                    :style="dropdownStyles.desktop"
                                    class="scrollbar-thin fixed z-50 w-full bg-white dark:text-gray-700 border border-gray-200 rounded-lg shadow-lg max-h-[20vmin] overflow-auto"
                                >
                                    <div
                                        v-for="res in DESKTOP_RESOLUTIONS"
                                        :key="res.name"
                                        @click="
                                            selectResolution('desktop', res)
                                        "
                                        class="px-4 py-1 text-xs cursor-pointer hover:bg-[#15919A20] first:rounded-t-lg last:rounded-b-lg transition-colors duration-150"
                                        :class="{
                                            'bg-[#15919A] text-white hover:bg-[#0C7C84]':
                                                res.name ===
                                                selectedDesktop.name,
                                        }"
                                    >
                                        {{ res.name }} —
                                        {{ res.aspectRatio }}
                                    </div>
                                </div>
                            </Teleport>
                        </div>

                        <!-- Tablet Resolution -->
                        <div v-if="activeDevice === 'tablet'" class="relative">
                            <button
                                ref="tabletTrigger"
                                type="button"
                                @click="toggleDropdown('tablet')"
                                class="w-full px-4 py-3 text-left bg-white border border-gray-200 rounded-xl shadow-sm hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#15919A] focus:border-[#15919A] transition-all duration-200 flex items-center justify-between"
                            >
                                <span class="text-sm font-medium text-gray-900">
                                    {{ selectedTablet.name }}
                                </span>
                                <svg
                                    class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                    :class="{
                                        'rotate-180': dropdownOpen.tablet,
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </button>

                            <Teleport to="body">
                                <div
                                    v-if="dropdownOpen.tablet"
                                    :style="dropdownStyles.tablet"
                                    class="scrollbar-thin fixed z-50 w-full bg-white dark:text-gray-700 border border-gray-200 rounded-lg shadow-lg max-h-[80vmin] overflow-auto"
                                >
                                    <div
                                        v-for="res in TABLET_RESOLUTIONS"
                                        :key="res.name"
                                        @click="selectResolution('tablet', res)"
                                        class="px-4 py-1 text-xs cursor-pointer hover:bg-[#15919A20] first:rounded-t-lg last:rounded-b-lg transition-colors duration-150"
                                        :class="{
                                            'bg-[#15919A] text-white hover:bg-[#0C7C84]':
                                                res.name ===
                                                selectedTablet.name,
                                        }"
                                    >
                                        {{ res.name }}
                                    </div>
                                </div>
                            </Teleport>
                        </div>

                        <!-- Mobile Resolution -->
                        <div v-if="activeDevice === 'mobile'" class="relative">
                            <button
                                ref="mobileTrigger"
                                type="button"
                                @click="toggleDropdown('mobile')"
                                class="w-full px-4 py-3 text-left bg-white border border-gray-200 rounded-xl shadow-sm hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#15919A] focus:border-[#15919A] transition-all duration-200 flex items-center justify-between"
                            >
                                <span class="text-sm font-medium text-gray-900">
                                    {{ selectedMobile.name }} ({{
                                        selectedMobile.width
                                    }}
                                    × {{ selectedMobile.height }})
                                </span>
                                <svg
                                    class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                    :class="{
                                        'rotate-180': dropdownOpen.mobile,
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </button>
                            <Teleport to="body">
                                <div
                                    v-if="dropdownOpen.mobile"
                                    :style="dropdownStyles.mobile"
                                    class="scrollbar-thin fixed z-50 w-full bg-white dark:text-gray-700 border border-gray-200 rounded-lg shadow-lg max-h-[50vmin] overflow-auto"
                                >
                                    <div
                                        v-for="res in MOBILE_RESOLUTIONS"
                                        :key="res.name"
                                        @click="selectResolution('mobile', res)"
                                        class="px-4 py-1 text-xs cursor-pointer hover:bg-[#15919A20] first:rounded-t-lg last:rounded-b-lg transition-colors duration-150"
                                        :class="{
                                            'bg-[#15919A] text-white hover:bg-[#0C7C84]':
                                                res.name ===
                                                selectedMobile.name,
                                        }"
                                    >
                                        {{ res.name }} ({{ res.width }} ×
                                        {{ res.height }})
                                    </div>
                                </div>
                            </Teleport>
                        </div>
                    </div>

                    <!-- Orientation Toggle (for tablet/mobile) -->
                    <!-- temporarily disabled because landscape in phone is not supported -->
                    <div v-if="activeDevice === 'tablet'" class="flex-shrink-0">
                        <button
                            type="button"
                            @click="isRotated = !isRotated"
                            :class="[
                                'p-2 rounded-md transition-all duration-200',
                                'bg-white text-gray-600 hover:bg-gray-50 hover:text-[#15919A] border border-gray-300',
                            ]"
                            :title="
                                isRotated
                                    ? 'Switch to Portrait'
                                    : 'Switch to Landscape'
                            "
                        >
                            <svg
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <!-- Rounded rectangle (device) -->
                                <rect
                                    x="7"
                                    y="6"
                                    width="10"
                                    height="12"
                                    rx="2"
                                    ry="2"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    fill="none"
                                />

                                <!-- Clockwise curved arrow (top-left) -->
                                <path
                                    d="M4 8C4 5.5 5.5 4 8 4"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    fill="none"
                                />
                                <path
                                    d="M6 2L8 4L6 6"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    fill="none"
                                />

                                <!-- Counter-clockwise curved arrow (bottom-right) -->
                                <path
                                    d="M20 16C20 18.5 18.5 20 16 20"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    fill="none"
                                />
                                <path
                                    d="M18 22L16 20L18 18"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    fill="none"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Browser Selector -->
                    <div
                        class="flex items-center justify-center gap-2"
                        v-if="activeDevice !== 'desktop'"
                    >
                        <div class="flex items-center gap-2">
                            <button
                                v-for="browser in browsers"
                                :key="browser.name"
                                :title="browser.title"
                                type="button"
                                @click="
                                    editorStore.activeBrowser = browser.name
                                "
                                :class="[
                                    'w-9 h-9 p-1.5 bg-white rounded-md transition-scale duration-300 active:scale-75',
                                    editorStore.activeBrowser === browser.name
                                        ? 'ring-1 ring-offset-1 ring-[#15919A]'
                                        : 'hover:opacity-80',
                                ]"
                            >
                                <img
                                    :src="browser.icon"
                                    :alt="browser.title"
                                    class="w-full h-full"
                                />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Preview Settings -->
            <details v-if="0" class="control-group">
                <summary
                    class="font-semibold text-gray-800 cursor-pointer hover:text-[#15919A] flex items-center"
                >
                    <svg
                        class="w-4 h-4 mr-2 transition-transform duration-200 details-arrow"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                    Device Toolbar
                </summary>
                <div class="mt-3 space-y-3">
                    <!-- Segmented control -->
                    <div
                        class="flex items-center rounded-lg border border-gray-200 bg-gray-50 p-1 text-sm"
                        role="tablist"
                        aria-label="Preview device selector"
                    >
                        <button
                            role="tab"
                            :aria-selected="activeDevice === 'desktop'"
                            :tabindex="activeDevice === 'desktop' ? 0 : -1"
                            @click="activeDevice = 'desktop'"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-md py-2 font-medium transition-colors"
                            :class="
                                activeDevice === 'desktop'
                                    ? 'bg-[#15919A20] shadow text-[#15919A]'
                                    : 'text-gray-700 hover:text-gray-900'
                            "
                        >
                            <span>Desktop</span>
                        </button>
                        <button
                            role="tab"
                            :aria-selected="activeDevice === 'tablet'"
                            :tabindex="activeDevice === 'tablet' ? 0 : -1"
                            @click="activeDevice = 'tablet'"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-md py-2 font-medium transition-colors"
                            :class="
                                activeDevice === 'tablet'
                                    ? 'bg-[#15919A20] shadow text-[#15919A]'
                                    : 'text-gray-700 hover:text-gray-900'
                            "
                        >
                            <span>Tablet</span>
                        </button>
                        <button
                            role="tab"
                            :aria-selected="activeDevice === 'mobile'"
                            :tabindex="activeDevice === 'mobile' ? 0 : -1"
                            @click="activeDevice = 'mobile'"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-md py-2 font-medium transition-colors"
                            :class="
                                activeDevice === 'mobile'
                                    ? 'bg-[#15919A20] shadow text-[#15919A]'
                                    : 'text-gray-700 hover:text-gray-900'
                            "
                        >
                            <span>Mobile</span>
                        </button>
                    </div>

                    <!-- Resolution pickers -->
                    <select
                        v-if="activeDevice === 'desktop'"
                        v-model="selectedDesktop"
                        class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#15919A] focus:border-[#15919A] text-xs dark:text-gray-700 text-base"
                    >
                        <option
                            v-for="res in DESKTOP_RESOLUTIONS"
                            :key="res.name"
                            :value="res"
                        >
                            {{ res.name }} — {{ res.aspectRatio }}
                        </option>
                    </select>

                    <select
                        v-if="activeDevice === 'tablet'"
                        v-model="selectedTablet"
                        class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#15919A] focus:border-[#15919A] text-xs dark:text-gray-700 text-base"
                    >
                        <option
                            v-for="res in TABLET_RESOLUTIONS"
                            :key="res.name"
                            :value="res"
                        >
                            {{ res.name }}
                        </option>
                    </select>

                    <select
                        v-if="activeDevice === 'mobile'"
                        v-model="selectedMobile"
                        class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:ring-[#15919A] focus:border-[#15919A] text-xs dark:text-gray-700 text-base"
                    >
                        <option
                            v-for="res in MOBILE_RESOLUTIONS"
                            :key="res.name"
                            :value="res"
                        >
                            {{ res.name }} ({{ res.width }} × {{ res.height }})
                        </option>
                    </select>

                    <!-- Rotate toggle for tablet/mobile -->
                    <div
                        v-if="activeDevice !== 'desktop'"
                        class="flex items-center justify-between"
                    >
                        <span class="text-xs text-gray-500"
                            >Orientation:
                            <span
                                class="font-bold dark:text-[#15919A] text-[#15919A]"
                                >{{
                                    isRotated ? "Landscape" : "Portrait"
                                }}</span
                            ></span
                        >
                        <button
                            type="button"
                            @click="isRotated = !isRotated"
                            :aria-pressed="isRotated"
                            class="px-3 py-1 rounded-md text-xs font-medium border border-gray-300 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#15919A] dark:text-gray-700 text-base"
                            title="Rotate"
                        >
                            Rotate
                        </button>
                    </div>
                </div>
            </details>

            <div v-if="!isToolbar" class="space-y-4">
                <details
                    v-for="group in activeControlGroups"
                    :key="group.title"
                    class="control-group"
                    :open="!collapsedGroupTitles.has(group.title)"
                >
                    <summary
                        class="font-semibold text-gray-800 cursor-pointer hover:text-[#15919A] flex items-center justify-between"
                    >
                        <div class="flex items-center">
                            <svg
                                class="w-4 h-4 mr-2 transition-transform duration-200 details-arrow"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                            {{ group.title }}
                            <span class="text-xs text-gray-400 ml-1 font-normal"
                                >({{ activeDevice }})<span
                                    class="text-xs text-gray-800 uppercase ml-1 font-bold"
                                    >{{
                                        editorStore.activeDevice === "mobile" &&
                                        group.title === "Layout"
                                            ? ` - ${editorStore.activeBrowser}`
                                            : ""
                                    }}</span
                                ></span
                            >
                        </div>
                        <button
                            type="button"
                            class="focus:outline-none rounded-full px-1"
                            :class="
                                toggledGroups.has(group.title)
                                    ? 'bg-[#15919A30]'
                                    : ''
                            "
                            title="Reset to default"
                            @click.stop="handleResetClick(group)"
                        >
                            <svg
                                :class="
                                    toggledGroups.has(group.title)
                                        ? 'text-[#15919A] '
                                        : 'text-gray-400/70 hover:text-[#15919A]'
                                "
                                @click.stop="handleResetClick(group)"
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="lucide lucide-refresh-ccw-icon lucide-refresh-ccw"
                            >
                                <path
                                    d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"
                                />
                                <path d="M3 3v5h5" />
                                <path
                                    d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"
                                />
                                <path d="M16 16h5v5" />
                            </svg>
                        </button>
                    </summary>

                    <!-- NEW: Special rendering for grouped controls like Descriptive Images -->
                    <div v-if="group.grouped" class="mt-3 px-3 space-y-2">
                        <fieldset
                            v-for="imageGroup in group.groupedControls"
                            :key="imageGroup.imageNumber"
                            class="fieldset border-base-400 rounded-box w-xs border px-2"
                        >
                            <legend class="fieldset-legend">
                                Image {{ imageGroup.imageNumber }}
                            </legend>
                            <div
                                class="grid gap-x-2 gap-y-2 grid-cols-3 pb-1 dark:text-gray-700 text-base"
                            >
                                <StyleControl
                                    v-for="control in imageGroup.controls"
                                    :key="control.variable"
                                    class="col-span-1"
                                    :label="control.label"
                                    :variable="control.variable"
                                    :modelValue="
                                        editorStore.styles[control.variable]
                                    "
                                    @update:modelValue="
                                        (newValue) =>
                                            editorStore.updateStyle(
                                                control.variable,
                                                newValue
                                            )
                                    "
                                    @reset="
                                        editorStore.resetStyle(control.variable)
                                    "
                                />
                            </div>
                        </fieldset>
                    </div>

                    <!-- OLD: Fallback for non-grouped controls -->
                    <div v-else class="mt-3 px-3 grid gap-4 grid-cols-2 pb-1">
                        <template v-for="control in group.controls">
                            <GridRowsControl
                                v-if="
                                    group.title === 'Layout' &&
                                    control.variable.includes(
                                        'layout-grid-rows'
                                    )
                                "
                                :key="control.variable + '-grid-rows'"
                                :modelValue="
                                    editorStore.styles[control.variable]
                                "
                                @update:modelValue="
                                    (newValue) =>
                                        editorStore.updateStyle(
                                            control.variable,
                                            newValue
                                        )
                                "
                            />
                            <StyleControl
                                v-else
                                class="col-span-1"
                                :key="control.variable"
                                :label="control.label"
                                :variable="control.variable"
                                :modelValue="
                                    editorStore.styles[control.variable]
                                "
                                @update:modelValue="
                                    (newValue) =>
                                        editorStore.updateStyle(
                                            control.variable,
                                            newValue
                                        )
                                "
                                @reset="
                                    editorStore.resetStyle(control.variable)
                                "
                            />
                        </template>
                    </div>

                    <!-- Theme 2 Palette shown directly under Targets group -->
                    <div
                        v-if="
                            group.title === 'Targets' &&
                            editorStore.getTheme() === 2
                        "
                        class="mt-4 px-3"
                    >
                        <div class="text-xs font-semibold text-gray-700 mb-2">
                            Theme 2 Palette
                        </div>
                        <div class="grid gap-4 grid-cols-4 pb-1">
                            <StyleControl
                                v-for="item in paletteKeys"
                                :key="item.key"
                                class="col-span-1"
                                :label="item.label"
                                :variable="item.key"
                                :modelValue="editorStore.styles[item.key]"
                                @update:modelValue="
                                    (newValue) =>
                                        editorStore.updateStyle(
                                            item.key,
                                            newValue
                                        )
                                "
                                @reset="editorStore.resetStyle(item.key)"
                            />
                        </div>
                    </div>
                </details>
            </div>
        </div>

        <!-- Sticky Footer for Save Button -->
        <div v-if="!isToolbar" class="flex p-4 pr-7 border-t bg-white gap-2">
            <button
                v-if="hasUnsavedChanges"
                @click="discardChanges"
                class="flex items-center justify-between bg-gray-100 text-gray-500 font-bold py-2 px-4 rounded-lg transition-colors duration-200 focus:outline-none disabled:opacity-20 disabled:cursor-not-allowed uppercase border border-gray-300 hover:bg-gray-200"
            >
                <span>Discard</span>
            </button>
            <button
                id="saveBtn"
                @click="saveChanges"
                :disabled="isSaving || !hasUnsavedChanges"
                class="w-full flex items-center justify-center bg-[#15919A] text-white font-bold py-2 px-4 rounded-lg hover:bg-[#0C7C84] transition-colors duration-200 focus:outline-none disabled:opacity-20 disabled:cursor-not-allowed uppercase"
                :class="{
                    '': hasUnsavedChanges && !isSaving,
                }"
            >
                <svg
                    v-if="isSaving"
                    class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                <span v-if="isSaving">Saving...</span>
                <span v-else-if="saveSuccess">Saved!</span>
                <span v-else-if="hasUnsavedChanges">Save Changes</span>
                <span v-else>No changes to save</span>
            </button>
            <p v-if="saveError" class="text-red-500 text-sm mt-2">
                {{ saveError }}
            </p>
        </div>
    </div>
    <WarningModal
        :is-open="showUnsavedChangesModal"
        @save="onModalSave"
        @discard="onModalDiscard"
        @cancel="onModalCancel"
    />
</template>

<script setup>
import {
    computed,
    ref,
    watch,
    onMounted,
    reactive,
    nextTick,
    onUnmounted,
    toRaw,
    defineExpose,
} from "vue";
import Helpers from "@/Mixins/Helpers";
import { editorStore } from "./editorStore";
// import { useTestsStore } from "@/stores/tests";
import StyleControl from "./StyleControl.vue";
import {
    DESKTOP_RESOLUTIONS,
    TABLET_RESOLUTIONS,
    MOBILE_RESOLUTIONS,
} from "./resolutions";
import edgeBrowserIcon from "./assets/edge-browser-icon.svg";
import safariBrowserIcon from "./assets/safari-icon.svg";
import firefoxBrowserIcon from "./assets/firefox-browser-icon.svg";
import chromeBrowserIcon from "./assets/google-chrome-icon.svg";
import WarningModal from "./WarningModal.vue";
import GridRowsControl from "./GridRowsControl.vue";

// const testsStore = useTestsStore();

const { toast } = Helpers();

// Titles that should render with details collapsed by default
const collapsedGroupTitles = new Set([
    "General",
    "Layout",
    "Background Logo",
    "Descriptive Images",
    "Header Images",
    "Prime Image",
    "Target Image",
    "Prime/Target Container",
    "Target States",
    "Footer Images",
    "Unselect Modal",
    "Warning Text / Others",
    // "Footer Text",
    "Button",
    "Button Arrow",
    "Timer",
]);

// Track which groups have eye icon toggled
const toggledGroups = ref(new Set());

// Map control group titles to their corresponding CSS variables
const groupToVariableMap = {
    Header: "--header-show",
    // Add more mappings as needed for other groups
    // "Footer Images": "--footer-show",
    // "Background Logo": "--logo-show",
};

const props = defineProps({
    survey: {
        type: Object,
        required: true,
    },
    maxTotalVariables: {
        type: Number,
        required: true,
    },
    brc: {
        type: Number,
        required: true,
    },
    isToolbar: {
        type: Boolean,
        required: false,
    },
});

const emit = defineEmits(["set-resolution", "update-brc"]);

const selectedBRC = ref(props.brc);

const browsers = computed(() => {
    const isMobile = activeDevice.value === "mobile";
    const isTablet = activeDevice.value === "tablet";
    const isIPhone = isMobile && selectedMobile.value?.name?.includes("iPhone");
    const isIPad = isTablet && selectedTablet.value?.name?.includes("iPad");
    if (isIPhone || isIPad) {
        return [
            { name: "safari", title: "Safari", icon: safariBrowserIcon },
            { name: "firefox", title: "Firefox", icon: firefoxBrowserIcon },
            { name: "edge", title: "Edge", icon: edgeBrowserIcon },
            { name: "chrome", title: "Chrome", icon: chromeBrowserIcon },
        ];
    } else {
        return [
            { name: "firefox", title: "Firefox", icon: firefoxBrowserIcon },
            { name: "edge", title: "Edge", icon: edgeBrowserIcon },
            { name: "chrome", title: "Chrome", icon: chromeBrowserIcon },
        ];
    }
});

const activeDevice = computed({
    get: () => editorStore.activeDevice,
    set: (val) => (editorStore.activeDevice = val),
});

const isRotated = computed({
    get: () => editorStore.isRotated,
    set: (val) => (editorStore.isRotated = val),
});

const selectedDesktop = ref(DESKTOP_RESOLUTIONS[0]);
const selectedTablet = ref(TABLET_RESOLUTIONS[0]);
const selectedMobile = ref(MOBILE_RESOLUTIONS[0]);

// Custom dropdown state
const dropdownOpen = ref({
    desktop: false,
    tablet: false,
    mobile: false,
});

const dropdownStyles = reactive({
    desktop: {},
    tablet: {},
    mobile: {},
});

// Refs for trigger elements
const desktopTrigger = ref(null);
const tabletTrigger = ref(null);
const mobileTrigger = ref(null);

const triggers = {
    desktop: desktopTrigger,
    tablet: tabletTrigger,
    mobile: mobileTrigger,
};

const toggleDropdown = async (device) => {
    const wasOpen = dropdownOpen.value[device];
    const triggerEl = triggers[device].value;

    // Close all dropdowns first
    Object.keys(dropdownOpen.value).forEach((key) => {
        dropdownOpen.value[key] = false;
    });

    if (wasOpen || !triggerEl) return;

    // Now, open the target dropdown
    dropdownOpen.value[device] = true;

    // Recalculate position on open
    await nextTick();
    const rect = triggerEl.getBoundingClientRect();
    dropdownStyles[device] = {
        top: `${rect.bottom + window.scrollY + 4}px`,
        left: `${rect.left + window.scrollX}px`,
        width: `${rect.width}px`,
    };
};

const selectResolution = (device, resolution) => {
    if (device === "desktop") {
        selectedDesktop.value = resolution;
    } else if (device === "tablet") {
        selectedTablet.value = resolution;
    } else if (device === "mobile") {
        selectedMobile.value = resolution;
    }
    dropdownOpen.value[device] = false;
};

// Close dropdowns when clicking outside
const closeDropdowns = (event) => {
    // Check if the click is outside any of the trigger buttons
    if (
        !desktopTrigger.value?.contains(event.target) &&
        !tabletTrigger.value?.contains(event.target) &&
        !mobileTrigger.value?.contains(event.target)
    ) {
        Object.keys(dropdownOpen.value).forEach((key) => {
            dropdownOpen.value[key] = false;
        });
    }
};

const getCurrentResolution = () => {
    let base;
    if (activeDevice.value === "desktop") {
        base = selectedDesktop.value;
    } else if (activeDevice.value === "tablet") {
        base = selectedTablet.value;
    } else {
        base = selectedMobile.value;
    }

    if (activeDevice.value !== "desktop" && isRotated.value && base) {
        return { ...base, width: base.height, height: base.width };
    }
    return base;
};

const getCurrentOrientation = () => {
    if (activeDevice.value === "desktop") {
        return "landscape";
    }
    if (activeDevice.value === "tablet" && !isRotated.value) {
        return "tabletPortrait";
    }
    return isRotated.value ? "landscape" : "portrait";
};

// Method to explicitly sync the left panel
const syncLeftPanel = (testIndex) => {
    const testId = editorStore.surveyStructure?.tests?.[testIndex]?.id;
    if (testId) {
        window.top.postMessage(
            {
                type: "UPDATE_TEST",
                testId: testId,
            },
            "*"
        );
    }
};

watch(
    [activeDevice, selectedDesktop, selectedTablet, selectedMobile, isRotated],
    () => {
        emit("set-resolution", getCurrentResolution());
        // Update editor store orientation when device/orientation changes
        const orientation = getCurrentOrientation();
        editorStore.updateOrientation(orientation);
    },
    { immediate: true }
);

watch(selectedMobile, () => {
    if (!selectedMobile.value?.name?.includes("iPhone")) {
        editorStore.activeBrowser = "chrome";
        editorStore.selectedMobile = "not-iPhone";
    } else {
        editorStore.selectedMobile = "iPhone";
    }
});

watch(selectedTablet, () => {
    if (!selectedTablet.value?.name?.includes("iPad")) {
        editorStore.activeBrowser = "chrome";
        editorStore.selectedTablet = "not-iPad";
    } else {
        editorStore.selectedTablet = "iPad";
    }
});

watch(activeDevice, () => {
    // reset rotation when switching categories
    if (activeDevice.value === "desktop") {
        editorStore.isRotated = false;
    }

    // Reset dropdown selections to defaults when device changes
    if (activeDevice.value === "desktop") {
        selectedDesktop.value = DESKTOP_RESOLUTIONS[0];
    } else if (activeDevice.value === "tablet") {
        selectedTablet.value = TABLET_RESOLUTIONS[0];
    } else if (activeDevice.value === "mobile") {
        editorStore.isRotated = false;
        editorStore.updateOrientation("portrait");
        selectedMobile.value = MOBILE_RESOLUTIONS[0];
    }
});

// Initialize selection from provided tID (test id) when mounting
onMounted(() => {
    editorStore.setNavigation(
        props.survey.editor_start_test_index,
        props.survey.editor_start_trial_index
    );

    // Initialize editor-only highlight variables to transparent and keep eyes OFF
    Object.values(groupToVariableMap).forEach((cssVariable) => {
        if (cssVariable) {
            editorStore.updateStyle(cssVariable, "#00000000");
        }
    });
    // Ensure no eye is active on first load
    toggledGroups.value = new Set();

    // Add click outside listener for dropdowns
    document.addEventListener("click", closeDropdowns);

    // Listen for STYLES_UPDATED message from Chatbot
    const handleStylesUpdate = (event) => {
        if (event.data?.type === "STYLES_UPDATED") {
            // Reload styles from the database
            window.location.reload();
        }
    };

    window.addEventListener("message", handleStylesUpdate);

    // Cleanup listener on component unmount
    onUnmounted(() => {
        document.removeEventListener("click", closeDropdowns);
        window.removeEventListener("message", handleStylesUpdate);
    });
});

const showUnsavedChangesModal = ref(false);

const onModalSave = async () => {
    await saveChanges();
    showUnsavedChangesModal.value = false;
};

const onModalDiscard = () => {
    discardChanges();
    showUnsavedChangesModal.value = false;
};

const onModalCancel = () => {
    showUnsavedChangesModal.value = false;
};

// const saveStylesOnly = async () => {
//     isSaving.value = true;
//     saveError.value = null;
//     try {
//         editorStore.saveCurrentStylesToThemeOrientation();
//         const stylesToSave = editorStore.getAllStylesForSaving();
//         const response = await fetch(`/api/surveys/${props.survey.id}/styles`, {
//             method: "PUT",
//             headers: {
//                 "Content-Type": "application/json",
//                 Accept: "application/json",
//                 "X-CSRF-TOKEN": document
//                     .querySelector('meta[name="csrf-token"]')
//                     .getAttribute("content"),
//             },
//             body: JSON.stringify({
//                 styles: stylesToSave,
//             }),
//         });
//         if (!response.ok) {
//             const errorData = await response.json();
//             throw new Error(errorData.message || "Failed to save styles.");
//         }
//         editorStore.setStylesPristine();
//         saveSuccess.value = true;
//         setTimeout(() => (saveSuccess.value = false), 2000);
//     } catch (error) {
//         saveError.value = error.message;
//     } finally {
//         isSaving.value = false;
//     }
// };

// const discardStylesOnly = () => {
//     editorStore.initialize(props.survey, editorStore.currentOrientation);
// };

const isSaving = ref(false);
const saveSuccess = ref(false);
const saveError = ref(null);

const hasUnsavedChanges = computed(
    () => editorStore.areStylesDirty || editorStore.isDataDirty
);

const discardChanges = () => {
    editorStore.revertStyles();
    window.top.postMessage(
        {
            type: "DISCARD_DATA_CHANGES",
        },
        "*"
    );
    editorStore.isDataDirty = false;
    handleUnsavedChanges(false);
    toast.success("Changes have been discarded");
};

const saveChanges = async () => {
    isSaving.value = true;
    saveSuccess.value = false;
    saveError.value = null;
    try {
        // Step 1: Save the styles via API call
        editorStore.saveCurrentStylesToThemeOrientation();
        const stylesToSave = editorStore.getAllStylesForSaving();
        const response = await fetch(`/api/surveys/${props.survey.id}/styles`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({
                styles: stylesToSave,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || "Failed to save styles.");
        }

        editorStore.setStylesPristine();

        // Step 2: If data is dirty, message parent to save it. Otherwise,
        // we can assume the save was for styles only and show success.
        if (editorStore.isDataDirty) {
            window.top.postMessage(
                {
                    type: "SAVE_CHANGES",
                    testIndex: selectedTest.value,
                    trialIndex: selectedTrial.value,
                },
                "*"
            );
        } else {
            toast.success("Styles successfully updated");
        }

        // Always show success on the button, as styles are saved regardless.
        // The parent window will handle its own success indicator for data saving.
        saveSuccess.value = true;
        handleUnsavedChanges(false);
        setTimeout(() => (saveSuccess.value = false), 2000);
    } catch (error) {
        saveError.value = error.message;
    } finally {
        isSaving.value = false;
    }
};

const handleResetClick = (group) => {
    // Find the original group definition in allControlGroups
    const originalGroup = allControlGroups.find((g) => g.title === group.title);

    if (!originalGroup) return;

    // ============================================
    // SPECIAL CASES - Groups with dynamic variables
    // ============================================

    // Layout: Reset all layout-grid-rows variables (device/browser specific)
    if (group.title === "Layout") {
        const layoutVariables = [
            "--layout-grid-rows-portrait",
            "--layout-grid-rows-landscape",
            "--layout-grid-rows-portrait-iphone-safari",
            "--layout-grid-rows-portrait-iphone-chrome",
            "--layout-grid-rows-portrait-iphone-firefox",
            "--layout-grid-rows-portrait-iphone-edge",
            "--layout-grid-rows-portrait-android-chrome",
            "--layout-grid-rows-portrait-android-firefox",
            "--layout-grid-rows-portrait-android-edge",
            "--layout-grid-rows-landscape-iphone",
            "--layout-grid-rows-landscape-ipad",
            "--layout-grid-rows-landscape-tablet",
            "--layout-grid-rows-landscape-android",
            "--layout-grid-rows-portrait-iPad-safari",
            "--layout-grid-rows-portrait-iPad-chrome",
            "--layout-grid-rows-portrait-iPad-firefox",
            "--layout-grid-rows-portrait-iPad-edge",
            "--layout-grid-rows-portrait-tablet-chrome",
            "--layout-grid-rows-portrait-tablet-firefox",
            "--layout-grid-rows-portrait-tablet-edge",
        ];
        layoutVariables.forEach((variable) => {
            editorStore.resetStyle(variable);
        });
        return;
    }

    // Footer Text: Reset all margin-top variables (device/browser specific)
    if (group.title === "Footer Text") {
        const footerMarginVariables = [
            "--bottom-text-container-margin-top-landscape-ipad",
            "--bottom-text-container-margin-top-landscape-tablet",
            "--bottom-text-container-margin-top-landscape-android",
            "--bottom-text-container-margin-top-landscape-iphone",
            "--bottom-text-container-margin-top-landscape",
            "--bottom-text-container-margin-top-portrait-ipad-safari",
            "--bottom-text-container-margin-top-portrait-ipad-firefox",
            "--bottom-text-container-margin-top-portrait-ipad-edge",
            "--bottom-text-container-margin-top-portrait-ipad-chrome",
            "--bottom-text-container-margin-top-portrait-tablet-firefox",
            "--bottom-text-container-margin-top-portrait-tablet-edge",
            "--bottom-text-container-margin-top-portrait-tablet-chrome",
            "--bottom-text-container-margin-top-portrait-iphone-safari",
            "--bottom-text-container-margin-top-portrait-iphone-chrome",
            "--bottom-text-container-margin-top-portrait-iphone-edge",
            "--bottom-text-container-margin-top-portrait-iphone-firefox",
            "--bottom-text-container-margin-top-portrait-android-chrome",
            "--bottom-text-container-margin-top-portrait-android-edge",
            "--bottom-text-container-margin-top-portrait-android-firefox",
            "--bottom-text-container-margin-top-portrait",
        ];
        footerMarginVariables.forEach((variable) => {
            editorStore.resetStyle(variable);
        });
        // Also reset other Footer Text controls
        originalGroup.controls.forEach((control) => {
            if (!control.variable.includes("margin-top")) {
                editorStore.resetStyle(control.variable);
            }
        });
        return;
    }

    // Warning Text / Others: Reset all warning margin variables
    if (group.title === "Warning Text / Others") {
        const warningMarginVariables = [
            "--warning-margin-bottom",
            "--warning-margin-bottom-ipad",
            "--warning-margin-bottom-tablet",
        ];
        warningMarginVariables.forEach((variable) => {
            editorStore.resetStyle(variable);
        });
        // Also reset other Warning Text / Others controls
        originalGroup.controls.forEach((control) => {
            if (!control.variable.includes("warning-margin-bottom")) {
                editorStore.resetStyle(control.variable);
            }
        });
        return;
    }

    // Prime/Target Container: Reset all scale variables (device specific)
    if (group.title === "Prime/Target Container") {
        const scaleVariables = [
            "--prime-target-container-scale",
            "--prime-target-container-scale-ipad",
            "--prime-target-container-scale-tablet",
        ];
        scaleVariables.forEach((variable) => {
            editorStore.resetStyle(variable);
        });
        // Also reset other Prime/Target Container controls
        originalGroup.controls.forEach((control) => {
            if (!control.variable.includes("scale")) {
                editorStore.resetStyle(control.variable);
            }
        });
        return;
    }

    // Targets: Handle Theme 2 palette variables
    if (group.title === "Targets") {
        // Reset all regular target controls
        originalGroup.controls.forEach((control) => {
            editorStore.resetStyle(control.variable);
        });

        // If Theme 2, also reset palette variables
        if (editorStore.getTheme() === 2) {
            const testsArr = editorStore.surveyStructure?.tests || [];
            const currentTest = testsArr[editorStore.currentTestIndex];
            const currentTrial =
                currentTest?.trials?.[editorStore.currentTrialIndex];
            const baseCount = Array.isArray(currentTrial?.targets)
                ? currentTrial.targets.length
                : Array.isArray(currentTest?.targets)
                ? currentTest.targets.length
                : 2;
            const count = Math.min(Math.max(baseCount, 2), 7);
            for (let i = 1; i <= count; i++) {
                editorStore.resetStyle(`--t2-palette-${count}-${i}`);
            }
        }
        return;
    }

    // ============================================
    // GROUPS WITH GROUPED CONTROLS
    // ============================================

    // Header Images: Has groupedControls structure
    if (
        group.title === "Header Images" &&
        originalGroup.grouped &&
        originalGroup.groupedControls
    ) {
        originalGroup.groupedControls.forEach((groupedControl) => {
            groupedControl.controls.forEach((control) => {
                editorStore.resetStyle(control.variable);
            });
        });
        return;
    }

    // ============================================
    // REGULAR GROUPS - Reset all controls from original definition
    // ============================================
    // These groups are handled generically:
    // - General
    // - Background Logo
    // - Descriptive Text
    // - Descriptive Images (dynamically grouped in UI, but reset all from controls array)
    // - Header
    // - Prime
    // - Prime Image
    // - Target Image
    // - Target States
    // - Button
    // - Button Arrow
    // - Timer
    // - Footer Images
    // - Unselect Modal

    if (originalGroup.controls && originalGroup.controls.length > 0) {
        originalGroup.controls.forEach((control) => {
            // Skip layout-grid-rows placeholder variable
            if (control.variable === "layout-grid-rows") {
                return;
            }
            editorStore.resetStyle(control.variable);
        });
    }
};

const isCurrentTestDescriptive = computed(() => {
    const test =
        editorStore.surveyStructure?.tests?.[editorStore.currentTestIndex];
    return test?.config?.type === "descriptive";
});

const descriptiveImageCount = computed(() => {
    if (typeof document === "undefined") return 0; // Guard for non-browser env
    const test =
        editorStore.surveyStructure?.tests?.[editorStore.currentTestIndex];
    if (test?.config?.type !== "descriptive") {
        return 0;
    }
    const titleHtml = test?.config?.title;
    if (!titleHtml) {
        return 0;
    }
    try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(titleHtml, "text/html");
        return doc.querySelectorAll("img").length;
    } catch (e) {
        console.error(
            "Error parsing descriptive title HTML for image count:",
            e
        );
        return 0;
    }
});

// Navigation computed properties
const tests = computed(() => editorStore.surveyStructure?.tests || []);
const trials = computed(
    () => tests.value[editorStore.currentTestIndex]?.trials || []
);

const selectedTest = computed({
    get: () => {
        return editorStore.currentTestIndex;
    },
    set: (value) => {
        if (hasUnsavedChanges.value) {
            showUnsavedChangesModal.value = true;
        } else {
            const numericValue = Number(value);
            editorStore.setNavigation(numericValue, 0);
        }
    },
});

const selectedTestChange = (selectedTest) => {
    console.log("**** warning here *******");
    if (hasUnsavedChanges.value) {
        showUnsavedChangesModal.value = true;
        handleUnsavedChanges(true);
    } else {
        syncLeftPanel(selectedTest);
    }
};

const selectedTrial = computed({
    get: () => editorStore.currentTrialIndex,
    set: (value) => {
        console.log("**** warning here *******");
        if (hasUnsavedChanges.value) {
            showUnsavedChangesModal.value = true;
            handleUnsavedChanges(true);
        } else {
            editorStore.setNavigation(
                editorStore.currentTestIndex,
                Number(value)
            );
        }
    },
});

// Linear navigation across tests and trials (descriptive counts as one step)
const getTestStepCount = (test) => {
    if (!test) return 0;
    return test?.config?.type === "descriptive" ? 1 : test?.trials?.length || 0;
};

const totalSteps = computed(() => {
    return (tests.value || []).reduce((sum, t) => sum + getTestStepCount(t), 0);
});

const currentStepIndex = computed(() => {
    let idx = 0;
    for (let i = 0; i < editorStore.currentTestIndex; i++) {
        idx += getTestStepCount(tests.value[i]);
    }
    const currentTest = tests.value[editorStore.currentTestIndex];
    if (!currentTest) return idx;
    if (currentTest?.config?.type === "descriptive") {
        return idx; // descriptive has a single step
    }
    return idx + (editorStore.currentTrialIndex || 0);
});

const isAtFirstStep = computed(() => currentStepIndex.value <= 0);
const isAtLastStep = computed(
    () =>
        totalSteps.value === 0 || currentStepIndex.value >= totalSteps.value - 1
);

function setFromLinearPosition(targetStep) {
    let remaining = targetStep;
    for (let tIndex = 0; tIndex < tests.value.length; tIndex++) {
        const count = getTestStepCount(tests.value[tIndex]);
        if (remaining < count) {
            if (tests.value[tIndex]?.config?.type === "descriptive") {
                editorStore.setNavigation(tIndex, 0);
            } else {
                editorStore.setNavigation(tIndex, remaining);
            }
            syncLeftPanel(tIndex); // Sync left panel after calculating new position
            return;
        }
        remaining -= count;
    }
    // Fallback to last position if out of range
    const lastIndex = tests.value.length - 1;
    const lastIsDesc = tests.value[lastIndex]?.config?.type === "descriptive";
    editorStore.setNavigation(
        lastIndex,
        lastIsDesc
            ? 0
            : Math.max((tests.value[lastIndex]?.trials?.length || 1) - 1, 0)
    );
}

function goToNext() {
    if (isAtLastStep.value) return;
    console.log("**** warning here *******");
    if (hasUnsavedChanges.value) {
        showUnsavedChangesModal.value = true;
        handleUnsavedChanges(true);
    } else {
        setFromLinearPosition(currentStepIndex.value + 1);
    }
}

function goToPrevious() {
    if (isAtFirstStep.value) return;
    console.log("**** warning here *******");
    if (hasUnsavedChanges.value) {
        showUnsavedChangesModal.value = true;
        handleUnsavedChanges(true);
    } else {
        setFromLinearPosition(currentStepIndex.value - 1);
    }
}

function handleUnsavedChanges(status) {
    window.top.postMessage(
        { type: "HAS_UNSAVED_CHANGES", status: status },
        "*"
    );
}

watch(hasUnsavedChanges, () => {
    handleUnsavedChanges(hasUnsavedChanges.value);
});

defineExpose({
    saveChanges,
    discardChanges,
});

const allControlGroups = [
    {
        title: "General",
        controls: [
            { label: "Font Family", variable: "--font-family" },
            {
                label: "Background Color",
                variable: "--engine-background-color",
            },
        ],
    },
    {
        title: "Background Logo",
        controls: [
            { label: "Width", variable: "--logo-width" },
            { label: "Opacity", variable: "--logo-opacity" },
            { label: "Blur", variable: "--logo-blur" },
            { label: "Position", variable: "--logo-position" },
            { label: "Top", variable: "--logo-top" },
            { label: "Bottom", variable: "--logo-bottom" },
            { label: "Left", variable: "--logo-left" },
            { label: "Right", variable: "--logo-right" },
            // {
            //     label: "Background Repeat",
            //     variable: "--engine-background-repeat",
            // },
            // {
            //     label: "Background Position",
            //     variable: "--engine-background-position",
            // },
            // { label: "Background Size", variable: "--engine-background-size" },
            // { label: "Box Sizing", variable: "--engine-background-box-sizing" },
            // { label: "Margin", variable: "--logo-margin" },
        ],
    },
    {
        title: "Layout",
        controls: [
            {
                label: "Grid Rows",
                variable: "layout-grid-rows", // This will be dynamically resolved
            },
        ],
    },
    {
        title: "Descriptive Text",
        controls: [
            { label: "Font Size", variable: "--descriptive-font-size" },
            { label: "Font Weight", variable: "--descriptive-font-weight" },
            {
                label: "Width",
                variable: "--descriptive-container-width",
            },
            {
                label: "Max Width",
                variable: "--descriptive-container-max-width",
            },
            {
                label: "Padding X",
                variable: "--descriptive-container-padding-x",
            },
            {
                label: "Padding Y",
                variable: "--descriptive-container-padding-y",
            },
            { label: "Text Align", variable: "--descriptive-text-align" },
            { label: "Line Height", variable: "--descriptive-line-height" },
            { label: "Margin Top", variable: "--descriptive-margin-top" },
            { label: "Font Color", variable: "--descriptive-color" },
        ],
    },
    {
        title: "Descriptive Images",
        controls: [
            { label: "Image 1 Width", variable: "--descriptive-img-1-width" },
            {
                label: "Image 1 Margin Top",
                variable: "--descriptive-img-1-margin-top",
            },
            {
                label: "Image 1 Margin Bottom",
                variable: "--descriptive-img-1-margin-bottom",
            },
            {
                label: "Image 1 Margin X",
                variable: "--descriptive-img-1-margin-horizontal",
            },
            {
                label: "Image 1 Display",
                variable: "--descriptive-img-1-display",
            },
            { label: "Image 2 Width", variable: "--descriptive-img-2-width" },
            {
                label: "Image 2 Margin Top",
                variable: "--descriptive-img-2-margin-top",
            },
            {
                label: "Image 2 Margin Bottom",
                variable: "--descriptive-img-2-margin-bottom",
            },
            {
                label: "Image 2 Margin X",
                variable: "--descriptive-img-2-margin-horizontal",
            },
            {
                label: "Image 2 Display",
                variable: "--descriptive-img-2-display",
            },
            { label: "Image 3 Width", variable: "--descriptive-img-3-width" },
            {
                label: "Image 3 Margin Top",
                variable: "--descriptive-img-3-margin-top",
            },
            {
                label: "Image 3 Margin Bottom",
                variable: "--descriptive-img-3-margin-bottom",
            },
            {
                label: "Image 3 Margin X",
                variable: "--descriptive-img-3-margin-horizontal",
            },
            {
                label: "Image 3 Display",
                variable: "--descriptive-img-3-display",
            },
            { label: "Image 4 Width", variable: "--descriptive-img-4-width" },
            {
                label: "Image 4 Margin Top",
                variable: "--descriptive-img-4-margin-top",
            },
            {
                label: "Image 4 Margin Bottom",
                variable: "--descriptive-img-4-margin-bottom",
            },
            {
                label: "Image 4 Margin X",
                variable: "--descriptive-img-4-margin-horizontal",
            },
            {
                label: "Image 4 Display",
                variable: "--descriptive-img-4-display",
            },
            { label: "Image 5 Width", variable: "--descriptive-img-5-width" },
            {
                label: "Image 5 Margin Top",
                variable: "--descriptive-img-5-margin-top",
            },
            {
                label: "Image 5 Margin Bottom",
                variable: "--descriptive-img-5-margin-bottom",
            },
            {
                label: "Image 5 Margin X",
                variable: "--descriptive-img-5-margin-horizontal",
            },
            {
                label: "Image 5 Display",
                variable: "--descriptive-img-5-display",
            },
        ],
    },
    {
        title: "Header",
        controls: [
            { label: "Font Size", variable: "--header-font-size" },
            { label: "Max Width", variable: "--header-max-width" },
            { label: "Padding X", variable: "--header-padding-vertical" },
            { label: "Padding Y", variable: "--header-padding-horizontal" },
            { label: "Line Height", variable: "--header-line-height" },
            { label: "Font Color", variable: "--header-text-color" },
        ],
    },
    {
        title: "Header Images",
        controls: [
            { label: "Width", variable: "--header-img-width" },
            { label: "Margin X", variable: "--header-img-margin-x" },
            { label: "Margin Y", variable: "--header-img-margin-y" },
        ],
        grouped: true,
        groupedControls: [
            {
                imageNumber: "All",
                controls: [
                    { label: "Width", variable: "--header-img-width" },
                    { label: "Margin X", variable: "--header-img-margin-x" },
                    { label: "Margin Y", variable: "--header-img-margin-y" },
                    { label: "Display", variable: "--header-img-display" },
                ],
            },
        ],
    },
    {
        title: "Prime/Target Container",
        controls: [
            { label: "Width", variable: "--wrapper-width" },
            { label: "Height", variable: "--wrapper-height" },
            { label: "Margin Top", variable: "--wrapper-margin-top" },
            { label: "Margin Bottom", variable: "--wrapper-margin-bottom" },
            { label: "Grid Repeat (D3)", variable: "--target-grid-repeat" },
            { label: "Grid Gap (D3)", variable: "--target-grid-gap" },
            {
                label: "Scale",
                variable: "--prime-target-container-scale",
            },
            {
                label: "Scale",
                variable: "--prime-target-container-scale-ipad",
            },
            {
                label: "Scale",
                variable: "--prime-target-container-scale-tablet",
            },
        ],
    },
    {
        title: "Prime",
        controls: [
            { label: "Font Size", variable: "--prime-font-size" },
            { label: "Font Weight", variable: "--prime-font-weight" },
            { label: "Line Height", variable: "--prime-line-height" },
            { label: "Margin", variable: "--prime-margin" },
            { label: "Width", variable: "--prime-width" },
            { label: "Height", variable: "--prime-height" },
            { label: "Border Width", variable: "--prime-border-width" },
            { label: "Border Style", variable: "--prime-border-style" },
            { label: "Text Wrap Style", variable: "--prime-text-wrap-style" },
            { label: "Border Color", variable: "--prime-border-color" },
            { label: "Background Color", variable: "--prime-bg-color" },
            { label: "Font Color", variable: "--prime-font-color" },
        ],
    },
    {
        title: "Prime Image",
        controls: [
            { label: "Width", variable: "--prime-img-width" },
            { label: "Height", variable: "--prime-img-height" },
            { label: "Margin Top", variable: "--prime-img-margin-top" },
            { label: "Margin Bottom", variable: "--prime-img-margin-bottom" },
            { label: "Display", variable: "--prime-img-display" },
            { label: "Margin X", variable: "--prime-img-margin-x" },
        ],
    },
    {
        title: "Targets",
        controls: [
            { label: "Font Size", variable: "--target-font-size" },
            { label: "Font Weight", variable: "--target-font-weight" },
            { label: "Line Height", variable: "--target-line-height" },
            { label: "Padding", variable: "--target-padding" },
            { label: "Width", variable: "--target-width" },
            { label: "Height", variable: "--target-height" },
            { label: "Inner Width", variable: "--target-img-width" },
            { label: "InnerHeight", variable: "--target-img-height" },
            {
                label: "1st Letter Transform",
                variable: "--target-text-transform",
            },
            { label: "Border Style", variable: "--target-border-style" },
            { label: "Text Wrap Style", variable: "--target-text-wrap-style" },
            { label: "Border Color", variable: "--target-border-color" },
            { label: "Font Color", variable: "--target-text-color" },
            { label: "Background Color", variable: "--target-bg-color" },
        ],
    },
    {
        title: "Target Image",
        controls: [
            { label: "Width", variable: "--target-img-width" },
            { label: "Height", variable: "--target-img-height" },
            { label: "Margin Top", variable: "--target-img-margin-top" },
            { label: "Margin Bottom", variable: "--target-img-margin-bottom" },
        ],
    },
    {
        title: "Target States",
        controls: [
            {
                label: "Hover Border Color",
                variable: "--target-hover-border-color",
            },
            {
                label: "Selected BG Color",
                variable: "--target-selected-bg-color",
            },
            {
                label: "Selected Border (D1)",
                variable: "--target-selected-border-color",
            },
            {
                label: "Selected Text (D1)",
                variable: "--target-selected-text-color",
            },
            {
                label: "Selected Text (D2)",
                variable: "--target-selected-text-color-theme2",
            },
            {
                label: "Selected Border (D3)",
                variable: "--target-selected-border-color",
            },
            {
                label: "Selected Text (D3)",
                variable: "--target-selected-text-color",
            },
        ],
    },
    {
        title: "Button",
        controls: [
            {
                label: "Padding Y",
                variable: "--button-padding-vertical",
            },
            {
                label: "Padding X",
                variable: "--button-padding-horizontal",
            },
            { label: "Margin Bottom", variable: "--button-margin-bottom" },
            {
                label: "Margin Top",
                variable: "--button-margin-top-landscape",
            },
            { label: "Font Size", variable: "--button-font-size" },
            { label: "Font Color", variable: "--button-font-color" },
            { label: "Background Color", variable: "--button-bg-color" },
        ],
    },
    {
        title: "Button Arrow",
        controls: [
            {
                label: "Width",
                variable: "--button-arrow-width",
            },
            { label: "Height", variable: "--button-arrow-height" },
            { label: "Direction", variable: "--button-flex-direction" },
            { label: "Gap", variable: "--button-flex-gap" },
            { label: "Flip", variable: "--button-arrow-transform-x" },
            { label: "Display", variable: "--button-arrow-display" },
        ],
    },
    {
        title: "Timer",
        controls: [
            { label: "Width", variable: "--timer-container-width" },
            { label: "Height", variable: "--timer-container-height" },
            // { label: "SVG Width", variable: "--timer-svg-width" },
            // { label: "SVG Height", variable: "--timer-svg-height" },
            { label: "Text Font Size", variable: "--timer-text-font-size" },
            {
                label: "Circle Stroke Width",
                variable: "--timer-circle-stroke-width",
            },
            { label: "Position Top", variable: "--timer-position-top" },
            { label: "Position Right", variable: "--timer-position-right" },
            { label: "Position Left", variable: "--timer-position-left" },
            { label: "Position Bottom", variable: "--timer-position-bottom" },
            {
                label: "Container BG Color",
                variable: "--timer-container-bg-color",
            },
            {
                label: "Circle Stroke Color",
                variable: "--timer-circle-stroke-color",
            },
            {
                label: "Circle Track Color",
                variable: "--timer-circle-track-stroke-color",
            },
            { label: "Text Color", variable: "--timer-text-color" },
        ],
    },
    {
        title: "Footer Text",
        controls: [
            { label: "Font Style", variable: "--bottom-text-font-style" },
            { label: "Font Weight", variable: "--bottom-text-font-weight" },
            { label: "Font Size", variable: "--bottom-text-font-size" },
            { label: "Line Height", variable: "--bottom-text-line-height" },
            { label: "Width", variable: "--bottom-text-width" },

            {
                label: "Margin Top",
                variable: "--bottom-text-container-margin-top-landscape-ipad",
            },
            {
                label: "Margin Top",
                variable: "--bottom-text-container-margin-top-landscape-tablet",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-landscape-android",
            },
            {
                label: "Margin Top",
                variable: "--bottom-text-container-margin-top-landscape-iphone",
            },
            {
                label: "Margin Top",
                variable: "--bottom-text-container-margin-top-landscape",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-ipad-safari",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-ipad-firefox",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-ipad-edge",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-ipad-chrome",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-tablet-firefox",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-tablet-edge",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-tablet-chrome",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-iphone-safari",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-iphone-chrome",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-iphone-edge",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-iphone-firefox",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-android-chrome",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-android-edge",
            },
            {
                label: "Margin Top",
                variable:
                    "--bottom-text-container-margin-top-portrait-android-firefox",
            },
            {
                label: "Margin Top",
                variable: "--bottom-text-container-margin-top-portrait",
            },
            { label: "Font Color", variable: "--bottom-text-color" },
        ],
    },
    {
        title: "Footer Images",
        controls: [
            { label: "Display", variable: "--bottom-img-display" },
            { label: "Width", variable: "--bottom-img-width" },
            { label: "Margin Top", variable: "--bottom-img-margin-top" },
            { label: "Margin Bottom", variable: "--bottom-img-margin-bottom" },
            { label: "Margin Left", variable: "--bottom-img-margin-left" },
            { label: "Margin Right", variable: "--bottom-img-margin-right" },
        ],
    },
    {
        title: "Unselect Modal",
        controls: [
            { label: "Width", variable: "--unselect-modal-width" },
            { label: "Height", variable: "--unselect-modal-height" },
            {
                label: "Border Radius",
                variable: "--unselect-modal-border-radius",
            },
            { label: "Scale", variable: "--unselect-modal-scale" },
            {
                label: "Cancel Font Size",
                variable: "--unselect-modal-cancel-font-size",
            },
            {
                label: "Submit Font Size",
                variable: "--unselect-modal-submit-font-size",
            },
            {
                label: "Message Font Size",
                variable: "--unselect-modal-message-font-size",
            },
            {
                label: "Message Text Align",
                variable: "--unselect-modal-message-text-align",
            },
            {
                label: "Buttons Direction",
                variable: "--unselect-modal-actions-flex-direction",
            },
            {
                label: "Buttons Alignment",
                variable: "--unselect-modal-actions-justify-content",
            },
        ],
    },
    {
        title: "Warning Text / Others",
        controls: [
            {
                label: "Empty Selection Margin Bottom",
                variable: "--warning-margin-bottom",
            },
            {
                label: "Empty Selection Margin Bottom",
                variable: "--warning-margin-bottom-ipad",
            },
            {
                label: "Empty Selection Margin Bottom",
                variable: "--warning-margin-bottom-tablet",
            },
            {
                label: "Survey Completion Redirect Font Color",
                variable: "--redirect-text-color",
            },
            {
                label: "Loading Infinity Color",
                variable: "--loading-infinity-color",
            },
            {
                label: "SSR GIF Logo",
                variable: "--logo-display",
            },
        ],
    },
];

const activeControlGroups = computed(() => {
    let filteredGroups;

    if (isCurrentTestDescriptive.value) {
        const descriptiveTitles = [
            "General",
            "Background Logo",
            "Descriptive Text",
            "Descriptive Images",
            "Button",
            "Button Arrow",
            "Timer",
        ];
        filteredGroups = allControlGroups.filter((group) =>
            descriptiveTitles.includes(group.title)
        );
    } else {
        const standardTrialTitlesToExclude = [
            "Descriptive Text",
            "Button",
            "Descriptive Images",
            "Button Arrow",
            "Timer",
        ];
        filteredGroups = allControlGroups.filter(
            (group) => !standardTrialTitlesToExclude.includes(group.title)
        );
    }

    // Filter Layout controls based on active device and Target States based on theme
    const mappedGroups = filteredGroups.map((group) => {
        if (group.title === "Layout") {
            return {
                ...group,
                controls: [
                    {
                        label: "Grid Rows",
                        variable: layoutGridRowsVariable.value,
                    },
                ],
            };
        }
        if (group.title === "Footer Text") {
            return {
                ...group,
                controls: group.controls.filter((control) => {
                    if (control.label.includes("Margin Top")) {
                        return (
                            control.variable ===
                            bottomTextContainerMarginTopVariable.value
                        );
                    }
                    return true;
                }),
            };
        }
        // In Theme 2, hide Targets Background Color (palette drives BG)
        if (group.title === "Targets") {
            const currentTheme = editorStore.getTheme();
            if (currentTheme === 2) {
                return {
                    ...group,
                    controls: group.controls.filter(
                        (control) =>
                            control.variable !== "--target-bg-color" &&
                            control.variable !== "--target-border-color"
                    ),
                };
            }
        }
        if (group.title === "Prime/Target Container") {
            const currentTheme = editorStore.getTheme();
            return {
                ...group,
                controls: group.controls.filter((control) => {
                    if (control.label.includes("D3")) {
                        const shouldShow = currentTheme === 3;
                        return shouldShow;
                    }
                    if (control.label.includes("Scale")) {
                        const device = editorStore.activeDevice; // 'mobile' or 'tablet'

                        if (device === "tablet") {
                            const tabletType =
                                editorStore.selectedTablet === "iPad"
                                    ? "ipad"
                                    : "tablet";
                            return (
                                control.variable ===
                                `--prime-target-container-scale-${tabletType}`
                            );
                        } else {
                            return (
                                control.variable ===
                                "--prime-target-container-scale"
                            );
                        }
                    }
                    return true;
                }),
            };
        }
        if (group.title === "Warning Text / Others") {
            const device = editorStore.activeDevice;
            return {
                ...group,
                controls: group.controls.filter((control) => {
                    if (device === "tablet") {
                        const tabletType =
                            editorStore.selectedTablet === "iPad"
                                ? "ipad"
                                : "tablet";
                        return (
                            control.variable ===
                            `--warning-margin-bottom-${tabletType}`
                        );
                    } else {
                        return (
                            control.variable === "--warning-margin-bottom" ||
                            control.variable === "--redirect-text-color" ||
                            control.variable === "--loading-infinity-color" ||
                            control.variable === "--logo-display"
                        );
                    }
                }),
            };
        }
        if (group.title === "Target States") {
            const currentTheme = editorStore.getTheme();
            return {
                ...group,
                controls: group.controls.filter((control) => {
                    // Show theme-specific controls only
                    if (control.label.includes("D1")) {
                        const shouldShow = currentTheme === 1;
                        return shouldShow;
                    }
                    if (control.label.includes("D2")) {
                        const shouldShow = currentTheme === 2;
                        return shouldShow;
                    }
                    if (control.label.includes("D3")) {
                        const shouldShow = currentTheme === 3;
                        return shouldShow;
                    }
                    return true;
                }),
            };
        }
        if (group.title === "Descriptive Images") {
            const count = descriptiveImageCount.value;
            if (count === 0) {
                return null; // Hide group if no images
            }

            const groupedControls = [];
            for (let i = 1; i <= count; i++) {
                const imageControls = group.controls.filter((control) => {
                    const match = control.variable.match(
                        /--descriptive-img-(\d+)-/
                    );
                    return match && parseInt(match[1], 10) === i;
                });

                if (imageControls.length > 0) {
                    groupedControls.push({
                        imageNumber: i,
                        // Simplify labels for a cleaner UI
                        controls: imageControls.map((c) => ({
                            ...c,
                            label: c.label.replace(`Image ${i} `, ""),
                        })),
                    });
                }
            }

            return {
                ...group,
                grouped: true,
                groupedControls: groupedControls,
                controls: [], // Important: clear the flat list
            };
        }
        return group;
    });

    return mappedGroups.filter(Boolean); // Filter out null groups
});

const layoutGridRowsVariable = computed(() => {
    const device = editorStore.activeDevice; // 'mobile' or 'tablet'
    const browser = editorStore.activeBrowser; // 'safari', 'chrome', etc.

    if (editorStore.currentOrientation === "landscape") {
        if (device === "mobile") {
            const mobileType =
                editorStore.selectedMobile === "iPhone" ? "iphone" : "android";
            return `--layout-grid-rows-landscape-${mobileType}`;
        } else if (device === "tablet") {
            const tabletType =
                editorStore.selectedTablet === "iPad" ? "ipad" : "tablet";
            return `--layout-grid-rows-landscape-${tabletType}`;
        } else {
            return "--layout-grid-rows-landscape";
        }
    }

    if (device === "mobile") {
        const mobileType =
            editorStore.selectedMobile === "iPhone" ? "iphone" : "android";
        return `--layout-grid-rows-portrait-${mobileType}-${browser}`;
    }

    if (device === "tablet") {
        const tabletType =
            editorStore.selectedTablet === "iPad" ? "iPad" : "tablet";
        return `--layout-grid-rows-portrait-${tabletType}-${browser}`;
    }

    return "--layout-grid-rows-portrait"; // Fallback for portrait
});

const bottomTextContainerMarginTopVariable = computed(() => {
    const device = editorStore.activeDevice; // 'mobile' or 'tablet'
    const browser = editorStore.activeBrowser; // 'safari', 'chrome', etc.

    if (editorStore.currentOrientation === "landscape") {
        if (device === "mobile") {
            const mobileType =
                editorStore.selectedMobile === "iPhone" ? "iphone" : "android";
            return `--bottom-text-container-margin-top-landscape-${mobileType}`;
        } else if (device === "tablet") {
            const tabletType =
                editorStore.selectedTablet === "iPad" ? "ipad" : "tablet";
            return `--bottom-text-container-margin-top-landscape-${tabletType}`;
        } else {
            return "--bottom-text-container-margin-top-landscape";
        }
    }

    if (device === "mobile") {
        const mobileType =
            editorStore.selectedMobile === "iPhone" ? "iphone" : "android";
        return `--bottom-text-container-margin-top-portrait-${mobileType}-${browser}`;
    }

    if (device === "tablet") {
        const tabletType =
            editorStore.selectedTablet === "iPad" ? "ipad" : "tablet";
        return `--bottom-text-container-margin-top-portrait-${tabletType}-${browser}`;
    }

    return "--bottom-text-container-margin-top-portrait"; // Fallback for portrait
});

// Palette keys for Theme 2 based on current test's target count
const paletteKeys = computed(() => {
    const testsArr = editorStore.surveyStructure?.tests || [];
    const currentTest = testsArr[editorStore.currentTestIndex];
    // Theme 2 layout uses the current trial's target count; fall back to test.targets
    const currentTrial = currentTest?.trials?.[editorStore.currentTrialIndex];
    const baseCount = Array.isArray(currentTrial?.targets)
        ? currentTrial.targets.length
        : Array.isArray(currentTest?.targets)
        ? currentTest.targets.length
        : 2;
    const count = Math.min(Math.max(baseCount, 2), 7);
    const keys = [];
    for (let i = 1; i <= count; i++) {
        keys.push({ key: `--t2-palette-${count}-${i}`, label: `Target ${i}` });
    }
    return keys;
});
</script>

<style scoped>
.control-group {
    padding: 0.5rem;
    background-color: #f9fafb;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}

.control-group:hover {
    border: 1px solid #15919a80;
    transition: border 0.3s ease-in-out;
}

/* Optimize scroll performance */
.flex-grow {
    will-change: scroll-position;
    transform: translateZ(0); /* Force hardware acceleration */
}

/* Reduce repaints during scroll */
.overflow-y-auto {
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}

/* Rotate arrow when details is open */
.control-group[open] .details-arrow {
    transform: rotate(90deg);
}

/* Device Toolbar Styles */
.device-toolbar {
    padding: 1rem;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 1rem;
}

.device-toolbar select {
    min-width: 120px;
}

.device-toolbar select:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(21, 145, 154, 0.1);
}

.device-toolbar button {
    min-width: 40px;
    min-height: 40px;
}

.device-toolbar button:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(21, 145, 154, 0.1);
}
@keyframes jiggle {
    /* Jiggle for the first 2 seconds of a 5-second cycle */
    0%,
    4%,
    8%,
    12%,
    16%,
    20%,
    24%,
    28%,
    32%,
    36% {
        transform: rotate(-1.5deg);
    }
    2%,
    6%,
    10%,
    14%,
    18%,
    22%,
    26%,
    30%,
    34%,
    38% {
        transform: rotate(1.5deg);
    }
    /* Pause for the remaining 3 seconds */
    40%,
    100% {
        transform: rotate(0deg);
    }
}

.animate-jiggle {
    animation: jiggle 5s ease-in-out infinite;
}
</style>
