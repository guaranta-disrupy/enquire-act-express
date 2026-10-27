<template>
    <div class="px-6 py-6">
        <!-- Header -->
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Themes</h1>
                <p class="mt-1 text-sm text-gray-500 max-w-2xl">
                    Choose the overall appearance of the survey. Start from a predefined
                    theme or one of your customized designs.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex items-center px-4 py-2 rounded-full bg-[#257556] text-white text-sm font-semibold shadow-sm hover:bg-[#257556] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#257556]"
                @click="openAddTheme"
            >
                <span class="text-lg leading-none mr-2">+</span>
                Add Theme
            </button>
        </div>

        <!-- Themes Grid -->
        <div
            class="mt-6 grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="theme in themes"
                :key="theme.id"
                class="cursor-pointer group"
            >
                <!-- Theme image / preview -->
                <div
                    class="relative aspect-[16/9] rounded-2xl overflow-hidden border border-gray-200 bg-gray-100 shadow-sm group-hover:shadow-md transition-shadow"
                >
                    <div
                        v-if="theme.config && (theme.config.surveyBackgroundImage || theme.config.surveyBackgroundColor)"
                        class="w-full h-full bg-cover bg-center"
                        :style="{
                            backgroundColor: theme.config.surveyBackgroundColor || '#FFFFFF',
                            backgroundImage: theme.config.surveyBackgroundImage
                                ? `url(${theme.config.surveyBackgroundImage})`
                                : 'none',
                            backgroundSize: 'cover',
                            backgroundPosition: 'center',
                            backgroundRepeat: 'no-repeat',
                            filter: `brightness(${theme.config.surveyBackgroundBrightness || 100}%) blur(${theme.config.surveyBackgroundBlur || 0}px)`,
                            opacity: theme.config.surveyBackgroundOpacity !== undefined ? theme.config.surveyBackgroundOpacity : 1,
                        }"
                    ></div>
                    <div
                        v-else
                        class="w-full h-full bg-cover bg-center"
                        :class="theme.bgClass"
                    ></div>
                    <div
                        v-if="theme.label"
                        class="absolute bottom-3 left-3 inline-flex items-center rounded-full bg-white/90 px-3 py-1 text-xs font-medium text-gray-700 shadow-sm"
                    >
                        {{ theme.label }}
                    </div>

                    <!-- Actions dropdown trigger -->
                    <div
                        class="absolute top-3 right-3"
                        data-theme-actions-root
                    >
                        <button
                            type="button"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/90 text-gray-500 shadow-sm hover:text-gray-700 hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#257556]"
                            @click.stop="toggleActions(theme.id)"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="w-4 h-4"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm6 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm6 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"
                                />
                            </svg>
                        </button>

                        <!-- Actions dropdown -->
                        <div
                            v-if="openThemeActionsId === theme.id"
                            class="absolute right-[38px] mt-[-35px] w-30 rounded-xl bg-white shadow-lg border border-gray-100 text-sm text-gray-700 py-2 z-20"
                        >
                            <div class="px-4 pb-0 text-xs uppercase tracking-wide text-gray-400">
                                Actions
                            </div>
                            <button
                                type="button"
                                class="block w-full text-left px-4 py-1 hover:bg-gray-50 text-xs"
                                @click.stop="applyTheme(theme)"
                            >
                                Apply
                            </button>
                            <button
                                type="button"
                                class="block w-full text-left px-4 py-1 hover:bg-gray-50 text-xs"
                                @click.stop="copyTheme(theme)"
                            >
                                Copy
                            </button>
                            <button
                                type="button"
                                class="block w-full text-left px-4 py-1 hover:bg-gray-50 text-xs"
                                @click.stop="editTheme(theme)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="block w-full text-left px-4 py-1 text-red-600 hover:bg-red-50 text-xs"
                                @click.stop="deleteTheme(theme)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Meta -->
                <div class="mt-3 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">
                            {{ theme.name }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ theme.type }}
                        </div>
                    </div>

                    <span
                        v-if="theme.applied"
                        class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700"
                    >
                        Applied
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Add / Edit Theme Modal -->
    <JetDialogModal
        :show="showThemeModal"
        @close="showThemeModal = false"
        maxWidth="7xl"
    >
        <template #title>
            <span class="text-lg font-semibold text-gray-900">
                {{ editingThemeId ? "Edit Theme" : "Add Theme" }}
            </span>
        </template>

        <template #content>
            <div class="space-y-6">
                <!-- Theme name -->
                <div>
                    <JetLabel value="Name" class="text-sm font-medium text-gray-700" />
                    <JetInput
                        v-model="themeForm.name"
                        type="text"
                        class="mt-1 w-full"
                        placeholder="Theme name"
                    />
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <!-- Left column -->
                    <div class="space-y-6">
                        <!-- Survey Background -->
                        <div class="border border-dashed border-gray-300 rounded-xl p-4">
                            <h3 class="text-sm font-semibold text-gray-800 mb-3">
                                Survey Background
                            </h3>
                            <div class="space-y-4">
                                <div
                                    class="w-full h-80 rounded-lg border border-gray-200 bg-white"
                                    :style="{
                                        backgroundColor: themeForm.surveyBackgroundColor,
                                        backgroundImage: themeForm.surveyBackgroundImage
                                            ? `url(${themeForm.surveyBackgroundImage})`
                                            : 'none',
                                        backgroundSize: 'cover',
                                        backgroundPosition: 'center',
                                        backgroundRepeat: 'no-repeat',
                                        filter: `brightness(${themeForm.surveyBackgroundBrightness}%) blur(${themeForm.surveyBackgroundBlur}px)`,
                                        opacity: themeForm.surveyBackgroundOpacity,
                                    }"
                                ></div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="md:col-span-2">
                                        <JetLabel value="Background image" class="text-xs" />
                                        <JetInput
                                            v-model="themeForm.surveyBackgroundImage"
                                            type="text"
                                            class="mt-1 w-full text-xs"
                                            placeholder="Image source"
                                        />
                                    </div>
                                    <div class="md:col-span-2">
                                        <JetLabel
                                            value="Background image brightness"
                                            class="text-xs"
                                        />
                                        <input
                                            v-model.number="themeForm.surveyBackgroundBrightness"
                                            type="range"
                                            min="0"
                                            max="200"
                                            class="w-full mt-1"
                                        />
                                    </div>
                                    <div class="md:col-span-2">
                                        <JetLabel
                                            value="Background image opacity"
                                            class="text-xs"
                                        />
                                        <div class="mt-1 flex items-center gap-2">
                                            <input
                                                v-model.number="themeForm.surveyBackgroundOpacity"
                                                type="range"
                                                min="0"
                                                max="1"
                                                step="0.01"
                                                class="flex-1"
                                            />
                                            <JetInput
                                                v-model.number="themeForm.surveyBackgroundOpacity"
                                                type="number"
                                                min="0"
                                                max="1"
                                                step="0.01"
                                                class="w-20 text-xs"
                                            />
                                        </div>
                                    </div>
                                    <div class="md:col-span-2">
                                        <JetLabel
                                            value="Background image blur (px)"
                                            class="text-xs"
                                        />
                                        <div class="mt-1 flex items-center gap-2">
                                            <input
                                                v-model.number="themeForm.surveyBackgroundBlur"
                                                type="range"
                                                min="0"
                                                max="1000"
                                                step="1"
                                                class="flex-1"
                                            />
                                            <JetInput
                                                v-model.number="themeForm.surveyBackgroundBlur"
                                                type="number"
                                                min="0"
                                                max="1000"
                                                step="1"
                                                class="w-20 text-xs"
                                            />
                                        </div>
                                    </div>
                                    <div class="md:col-span-2 flex items-center gap-2">
                                        <div class="flex-1">
                                            <JetLabel
                                                value="Background colour"
                                                class="text-xs"
                                            />
                                            <JetInput
                                                v-model="themeForm.surveyBackgroundColor"
                                                type="text"
                                                class="mt-1 w-full text-xs"
                                            />
                                        </div>
                                        <ColorPicker
                                            :pureColor="themeForm.surveyBackgroundColor"
                                            @pureColorChange="(color) => themeForm.surveyBackgroundColor = rgbToHex(color)"
                                            format="hex"
                                            shape="square"
                                            style="z-index: 10001"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Button -->
                        <div class="border border-dashed border-gray-300 rounded-xl p-4">
                            <h3 class="text-sm font-semibold text-gray-800 mb-3">
                                Button
                            </h3>
                            <div class="space-y-4">
                                <div
                                    class="w-full h-32 rounded-lg border border-gray-200 flex items-center justify-center"
                                >
                                    <button
                                        type="button"
                                        class="px-4 py-2 rounded-md text-sm font-semibold shadow flex items-center gap-2"
                                        :style="{
                                            backgroundColor: themeForm['--button-bg-color'],
                                            color: themeForm['--button-font-color'],
                                        }"
                                    >
                                        NEXT
                                        <svg
                                            aria-hidden="true"
                                            focusable="false"
                                            data-prefix="far"
                                            data-icon="arrow-circle-right"
                                            role="img"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 512 512"
                                            :style="{
                                                width: themeForm['--button-arrow-width'] || '2vmin',
                                                height: themeForm['--button-arrow-height'] || '2vmin',
                                                display: themeForm['--button-arrow-display'] || 'block',
                                                transform: `scaleX(${themeForm['--button-arrow-transform-x'] || 1})`,
                                                transformOrigin: 'center center',
                                            }"
                                        >
                                            <path
                                                :fill="themeForm['--button-arrow-color'] || themeForm['--button-font-color']"
                                                d="M8 256c0 137 111 248 248 248s248-111 248-248S393 8 256 8 8 119 8 256zm448 0c0 110.5-89.5 200-200 200S56 366.5 56 256 145.5 56 256 56s200 89.5 200 200zM266.9 126.1l121.4 121.4c4.7 4.7 4.7 12.3 0 17L266.9 385.9c-4.7 4.7-12.3 4.7-17 0l-19.6-19.6c-4.8-4.8-4.7-12.5.2-17.2l70.3-67.1H140c-6.6 0-12-5.4-12-12v-28c0-6.6 5.4-12 12-12h160.8l-70.3-67.1c-4.9-4.7-5-12.4-.2-17.2l19.6-19.6c4.7-4.7 12.3-4.7 17 0z"
                                            ></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <JetLabel value="Button colour" class="text-xs" />
                                            <JetInput
                                                v-model="themeForm['--button-bg-color']"
                                                type="text"
                                                class="mt-1 w-full text-xs"
                                            />
                                        </div>
                                        <ColorPicker
                                            :pureColor="themeForm['--button-bg-color']"
                                            @pureColorChange="(color) => themeForm['--button-bg-color'] = rgbToHex(color)"
                                            format="hex"
                                            shape="square"
                                            style="z-index: 10001"
                                        />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <JetLabel
                                                value="Button hover colour"
                                                class="text-xs"
                                            />
                                            <JetInput
                                                v-model="themeForm.buttonHoverColor"
                                                type="text"
                                                class="mt-1 w-full text-xs"
                                            />
                                        </div>
                                        <ColorPicker
                                            :pureColor="themeForm.buttonHoverColor"
                                            @pureColorChange="(color) => themeForm.buttonHoverColor = rgbToHex(color)"
                                            format="hex"
                                            shape="square"
                                            style="z-index: 10001"
                                        />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <JetLabel
                                                value="Button text colour"
                                                class="text-xs"
                                            />
                                            <JetInput
                                                v-model="themeForm['--button-font-color']"
                                                type="text"
                                                class="mt-1 w-full text-xs"
                                            />
                                        </div>
                                        <ColorPicker
                                            :pureColor="themeForm['--button-font-color']"
                                            @pureColorChange="(color) => themeForm['--button-font-color'] = rgbToHex(color)"
                                            format="hex"
                                            shape="square"
                                            style="z-index: 10001"
                                        />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <JetLabel
                                                value="Button arrow colour"
                                                class="text-xs"
                                            />
                                            <JetInput
                                                v-model="themeForm['--button-arrow-color']"
                                                type="text"
                                                class="mt-1 w-full text-xs"
                                            />
                                        </div>
                                        <ColorPicker
                                            :pureColor="themeForm['--button-arrow-color'] || themeForm['--button-font-color']"
                                            @pureColorChange="(color) => themeForm['--button-arrow-color'] = rgbToHex(color)"
                                            format="hex"
                                            shape="square"
                                            style="z-index: 10001"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Prime & Targets Attribute Choice -->
                        <div class="border border-dashed border-gray-300 rounded-xl p-4">
                            <h3 class="text-sm font-semibold text-gray-800 mb-3">
                                Prime &amp; Targets Attribute Choice
                            </h3>
                            <div class="space-y-4">
                                <!-- Preview -->
                                <div
                                    class="w-full h-64 rounded-lg border border-gray-200 flex items-center justify-center bg-white"
                                >
                                    <div class="relative w-52 h-52">
                                        <!-- Prime -->
                                        <div
                                            class="absolute inset-1/4 flex items-center justify-center rounded-full border-4 border-solid text-[10px] font-semibold text-center px-3"
                                            :style="{
                                                borderColor: themeForm['--prime-border-color'],
                                                backgroundColor: themeForm['--prime-bg-color'],
                                                color: themeForm['--prime-font-color'],
                                            }"
                                        >
                                            Prime Text
                                        </div>

                                        <!-- Targets (simple circular layout to mirror trial display) -->
                                        <div
                                            class="absolute -top-[-20px] left-[140px] -translate-x-1/2 text-[8px] w-16 h-16 rounded-full border-4 border-solid flex items-center justify-center  text-center bg-white"
                                            :style="{
                                                borderColor: themeForm['--target-border-color'],
                                                backgroundColor: themeForm['--target-bg-color'],
                                                color: themeForm['--target-text-color'],
                                            }"
                                        >
                                            target text 1
                                        </div>
                                        <div
                                            class="absolute top-[80px] left-[140px] w-16 h-16 text-[8px] rounded-full border-4 border-solid flex items-center justify-center text-center bg-white"
                                            :style="{
                                                borderColor: themeForm['--target-border-color'],
                                                backgroundColor: themeForm['--target-bg-color'],
                                                color: themeForm['--target-text-color'],
                                            }"
                                        >
                                            target text 2
                                        </div>
                                        <div
                                            class="absolute top-[148px] left-[140px] w-16 h-16 text-[8px]  rounded-full border-4 border-solid flex items-center justify-center text-center bg-white"
                                            :style="{
                                                borderColor: themeForm['--target-border-color'],
                                                backgroundColor: themeForm['--target-bg-color'],
                                                color: themeForm['--target-text-color'],
                                            }"
                                        >
                                            target text 3
                                        </div>
                                        <div
                                            class="absolute -bottom-3 left-1/2 -translate-x-1/2 w-16 h-16 text-[8px]  rounded-full border-4 border-solid flex items-center justify-center  text-center bg-white"
                                            :style="{
                                                borderColor: themeForm['--target-border-color'],
                                                backgroundColor: themeForm['--target-bg-color'],
                                                color: themeForm['--target-text-color'],
                                            }"
                                        >
                                            target text
                                        </div>
                                        <div
                                            class="absolute bottom-6 -left-2 w-16 h-16 rounded-full text-[8px]  border-4 border-solid flex items-center justify-center text-center bg-white"
                                            :style="{
                                                borderColor: themeForm['--target-border-color'],
                                                backgroundColor: themeForm['--target-bg-color'],
                                                color: themeForm['--target-text-color'],
                                            }"
                                        >
                                            target text
                                        </div>
                                        <div
                                            class="absolute top-6 -left-2 w-16 h-16 text-[8px]  rounded-full  border-4 border-solid flex items-center justify-center text-center bg-white"
                                            :style="{
                                                borderColor: themeForm['--target-border-color'],
                                                backgroundColor: themeForm['--target-bg-color'],
                                                color: themeForm['--target-text-color'],
                                            }"
                                        >
                                            target text
                                        </div>
                                    </div>
                                </div>

                                <!-- Prime colours -->
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <JetLabel
                                            value="Prime border colour"
                                            class="text-xs text-gray-600"
                                        />
                                        <div class="flex items-center gap-2">
                                            <JetInput
                                                v-model="themeForm['--prime-border-color']"
                                                type="text"
                                                class="w-28 text-xs"
                                            />
                                            <ColorPicker
                                                :pureColor="themeForm['--prime-border-color']"
                                                @pureColorChange="(color) => themeForm['--prime-border-color'] = rgbToHex(color)"
                                                format="hex"
                                                shape="square"
                                                style="z-index: 10001"
                                            />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <JetLabel
                                            value="Prime background colour"
                                            class="text-xs text-gray-600"
                                        />
                                        <div class="flex items-center gap-2">
                                            <JetInput
                                                v-model="themeForm['--prime-bg-color']"
                                                type="text"
                                                class="w-28 text-xs"
                                            />
                                            <ColorPicker
                                                :pureColor="themeForm['--prime-bg-color']"
                                                @pureColorChange="(color) => themeForm['--prime-bg-color'] = rgbToHex(color)"
                                                format="hex"
                                                shape="square"
                                                style="z-index: 10001"
                                            />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <JetLabel
                                            value="Prime font colour"
                                            class="text-xs text-gray-600"
                                        />
                                        <div class="flex items-center gap-2">
                                            <JetInput
                                                v-model="themeForm['--prime-font-color']"
                                                type="text"
                                                class="w-28 text-xs"
                                            />
                                            <ColorPicker
                                                :pureColor="themeForm['--prime-font-color']"
                                                @pureColorChange="(color) => themeForm['--prime-font-color'] = rgbToHex(color)"
                                                format="hex"
                                                shape="square"
                                                style="z-index: 10001"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Target colours -->
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <JetLabel
                                            value="Target border colour"
                                            class="text-xs text-gray-600"
                                        />
                                        <div class="flex items-center gap-2">
                                            <JetInput
                                                v-model="themeForm['--target-border-color']"
                                                type="text"
                                                class="w-28 text-xs"
                                            />
                                            <ColorPicker
                                                :pureColor="themeForm['--target-border-color']"
                                                @pureColorChange="(color) => themeForm['--target-border-color'] = rgbToHex(color)"
                                                format="hex"
                                                shape="square"
                                                style="z-index: 10001"
                                            />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <JetLabel
                                            value="Target background colour"
                                            class="text-xs text-gray-600"
                                        />
                                        <div class="flex items-center gap-2">
                                            <JetInput
                                                v-model="themeForm['--target-bg-color']"
                                                type="text"
                                                class="w-28 text-xs"
                                            />
                                            <ColorPicker
                                                :pureColor="themeForm['--target-bg-color']"
                                                @pureColorChange="(color) => themeForm['--target-bg-color'] = rgbToHex(color)"
                                                format="hex"
                                                shape="square"
                                                style="z-index: 10001"
                                            />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <JetLabel
                                            value="Target font colour"
                                            class="text-xs text-gray-600"
                                        />
                                        <div class="flex items-center gap-2">
                                            <JetInput
                                                v-model="themeForm['--target-text-color']"
                                                type="text"
                                                class="w-28 text-xs"
                                            />
                                            <ColorPicker
                                                :pureColor="themeForm['--target-text-color']"
                                                @pureColorChange="(color) => themeForm['--target-text-color'] = rgbToHex(color)"
                                                format="hex"
                                                shape="square"
                                                style="z-index: 10001"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Prime & Targets Likert Scale -->
                        <div class="border border-dashed border-gray-300 rounded-xl p-4">
                            <h3 class="text-sm font-semibold text-gray-800 mb-3">
                                Prime &amp; Targets Likert Scale
                            </h3>
                            <div class="space-y-4">
                                <!-- Preview -->
                                <div
                                    class="w-full h-64 rounded-lg border border-gray-200 flex items-center justify-center bg-white"
                                >
                                    <div class="relative w-52 h-52">
                                        <!-- Prime -->
                                        <div
                                            class="absolute inset-1/4 flex items-center justify-center rounded-full border-4 border-solid text-xs font-semibold text-center px-3"
                                            :style="{
                                                borderColor: themeForm['--likert-prime-border-color'],
                                                backgroundColor: themeForm['--likert-prime-bg-color'],
                                            }"
                                        >
                                            Prime Text
                                        </div>

                                        <!-- Target 1 -->
                                        <div
                                            class="absolute -top-[-55px] left-2 -translate-x-1/2 w-16 h-16 text-[8px] rounded-full border-4 border-solid flex items-center justify-center text-[10px] font-semibold text-center"
                                            :style="{
                                                borderColor: themeForm['--likert-target-1-border-color'],
                                                backgroundColor: themeForm['--likert-target-1-bg-color'],
                                                color: themeForm['--likert-target-1-text-color'],
                                            }"
                                        >
                                            target text 1
                                        </div>
                                        <!-- Target 2 -->
                                        <div
                                            class="absolute bottom-4 -left-2 w-16 h-16 text-[8px] rounded-full border-4 border-solid flex items-center justify-center text-[10px] font-semibold text-center"
                                            :style="{
                                                borderColor: themeForm['--likert-target-2-border-color'],
                                                backgroundColor: themeForm['--likert-target-2-bg-color'],
                                                color: themeForm['--likert-target-2-text-color'],
                                            }"
                                        >
                                            target text 2
                                        </div>
                                        <!-- Target 3 -->
                                        <div
                                            class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-16 h-16 text-[8px] rounded-full border-4 border-solid flex items-center justify-center text-[10px] font-semibold text-center"
                                            :style="{
                                                borderColor: themeForm['--likert-target-3-border-color'],
                                                backgroundColor: themeForm['--likert-target-3-bg-color'],
                                                color: themeForm['--likert-target-3-text-color'],
                                            }"
                                        >
                                            target text 3
                                        </div>
                                        <!-- Target 4 -->
                                        <div
                                            class="absolute bottom-4 -right-2 w-16 h-16 text-[8px] rounded-full border-4 border-solid flex items-center justify-center text-[10px] font-semibold text-center"
                                            :style="{
                                                borderColor: themeForm['--likert-target-4-border-color'],
                                                backgroundColor: themeForm['--likert-target-4-bg-color'],
                                                color: themeForm['--likert-target-4-text-color'],
                                            }"
                                        >
                                            target text 4
                                        </div>
                                        <!-- Target 5 -->
                                        <div
                                            class="absolute top-[55px] -right-6 w-16 h-16 text-[8px] rounded-full border-4 border-solid flex items-center justify-center text-[10px] font-semibold text-center"
                                            :style="{
                                                borderColor: themeForm['--likert-target-5-border-color'],
                                                backgroundColor: themeForm['--likert-target-5-bg-color'],
                                                color: themeForm['--likert-target-5-text-color'],
                                            }"
                                        >
                                            target text 5
                                        </div>
                                    </div>
                                </div>

                                <!-- Prime colours -->
                                <div class="space-y-3">
                                    <div class="text-xs font-semibold text-gray-700 mb-2">Prime</div>
                                    <div class="flex items-center justify-between">
                                        <JetLabel
                                            value="Prime border colour"
                                            class="text-xs text-gray-600"
                                        />
                                        <div class="flex items-center gap-2">
                                            <JetInput
                                                v-model="themeForm['--likert-prime-border-color']"
                                                type="text"
                                                class="w-28 text-xs"
                                            />
                                            <ColorPicker
                                                :pureColor="themeForm['--likert-prime-border-color']"
                                                @pureColorChange="(color) => themeForm['--likert-prime-border-color'] = rgbToHex(color)"
                                                format="hex"
                                                shape="square"
                                                style="z-index: 10001"
                                            />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <JetLabel
                                            value="Prime background colour"
                                            class="text-xs text-gray-600"
                                        />
                                        <div class="flex items-center gap-2">
                                            <JetInput
                                                v-model="themeForm['--likert-prime-bg-color']"
                                                type="text"
                                                class="w-28 text-xs"
                                            />
                                            <ColorPicker
                                                :pureColor="themeForm['--likert-prime-bg-color']"
                                                @pureColorChange="(color) => themeForm['--likert-prime-bg-color'] = rgbToHex(color)"
                                                format="hex"
                                                shape="square"
                                                style="z-index: 10001"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Target colours -->
                                <div class="space-y-4">
                                    <div class="text-xs font-semibold text-gray-700 mb-2">Targets</div>
                                    
                                    <!-- Target 1 -->
                                    <div class="space-y-2 border-l-2 border-gray-200 pl-3">
                                        <div class="text-xs font-medium text-gray-600">Target 1</div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Border colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-1-border-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-1-border-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-1-border-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Background colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-1-bg-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-1-bg-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-1-bg-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Font colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-1-text-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-1-text-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-1-text-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Target 2 -->
                                    <div class="space-y-2 border-l-2 border-gray-200 pl-3">
                                        <div class="text-xs font-medium text-gray-600">Target 2</div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Border colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-2-border-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-2-border-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-2-border-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Background colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-2-bg-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-2-bg-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-2-bg-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Font colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-2-text-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-2-text-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-2-text-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Target 3 -->
                                    <div class="space-y-2 border-l-2 border-gray-200 pl-3">
                                        <div class="text-xs font-medium text-gray-600">Target 3</div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Border colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-3-border-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-3-border-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-3-border-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Background colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-3-bg-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-3-bg-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-3-bg-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Font colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-3-text-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-3-text-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-3-text-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Target 4 -->
                                    <div class="space-y-2 border-l-2 border-gray-200 pl-3">
                                        <div class="text-xs font-medium text-gray-600">Target 4</div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Border colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-4-border-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-4-border-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-4-border-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Background colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-4-bg-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-4-bg-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-4-bg-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Font colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-4-text-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-4-text-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-4-text-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Target 5 -->
                                    <div class="space-y-2 border-l-2 border-gray-200 pl-3">
                                        <div class="text-xs font-medium text-gray-600">Target 5</div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Border colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-5-border-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-5-border-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-5-border-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Background colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-5-bg-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-5-bg-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-5-bg-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <JetLabel
                                                value="Font colour"
                                                class="text-xs text-gray-600"
                                            />
                                            <div class="flex items-center gap-2">
                                                <JetInput
                                                    v-model="themeForm['--likert-target-5-text-color']"
                                                    type="text"
                                                    class="w-28 text-xs"
                                                />
                                                <ColorPicker
                                                    :pureColor="themeForm['--likert-target-5-text-color']"
                                                    @pureColorChange="(color) => themeForm['--likert-target-5-text-color'] = rgbToHex(color)"
                                                    format="hex"
                                                    shape="square"
                                                    style="z-index: 10001"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right column -->
                    <div class="space-y-6">

        <!-- Instruction -->
        <div class="border border-dashed border-gray-300 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Round Title
                                </h3>
                                <div class="flex items-center gap-2">
                                    <JetLabel
                                        value="Text colour"
                                        class="text-xs text-gray-600"
                                    />
                                    <JetInput
                                        v-model="themeForm.instructionTextColor"
                                        type="text"
                                        class="w-24 text-xs"
                                    />
                                    <ColorPicker
                                        :pureColor="themeForm.instructionTextColor"
                                        @pureColorChange="(color) => themeForm.instructionTextColor = rgbToHex(color)"
                                        format="hex"
                                        shape="square"
                                        style="z-index: 10001"
                                    />
                                </div>
                            </div>
                            <div
                                class="w-full h-40 rounded-lg border border-gray-200 flex items-center justify-center text-sm font-semibold"
                                :style="{ color: themeForm.instructionTextColor }"
                            >
                            TEXT ROUND TITLE
                            </div>
                        </div>

                        <!-- Main Header -->
                        <div class="border border-dashed border-gray-300 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Main Header
                                </h3>
                                <div class="flex items-center gap-2">
                                    <JetLabel
                                        value="Text colour"
                                        class="text-xs text-gray-600"
                                    />
                                    <JetInput
                                        v-model="themeForm['--header-color']"
                                        type="text"
                                        class="w-24 text-xs"
                                    />
                                    <ColorPicker
                                        :pureColor="themeForm['--header-color']"
                                        @pureColorChange="(color) => themeForm['--header-color'] = rgbToHex(color)"
                                        format="hex"
                                        shape="square"
                                        style="z-index: 10001"
                                    />
                                </div>
                            </div>
                            <div
                                class="w-full h-40 rounded-lg border border-gray-200 flex items-center justify-center text-sm font-semibold"
                                :style="{ color: themeForm['--header-color'] }"
                            >
                                MAIN HEADER TEXT
                            </div>
                        </div>

                        <!-- Bottom Text -->
                        <div class="border border-dashed border-gray-300 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Bottom Text
                                </h3>
                                <div class="flex items-center gap-2">
                                    <JetLabel
                                        value="Text colour"
                                        class="text-xs text-gray-600"
                                    />
                                    <JetInput
                                        v-model="themeForm['--bottom-text-color']"
                                        type="text"
                                        class="w-24 text-xs"
                                    />
                                    <ColorPicker
                                        :pureColor="themeForm['--bottom-text-color']"
                                        @pureColorChange="(color) => themeForm['--bottom-text-color'] = rgbToHex(color)"
                                        format="hex"
                                        shape="square"
                                        style="z-index: 10001"
                                    />
                                </div>
                            </div>
                            <div
                                class="w-full h-40 rounded-lg border border-gray-200 flex items-center justify-center text-sm font-semibold"
                                :style="{ color: themeForm['--bottom-text-color'] }"
                            >
                                BOTTOM TEXT
                            </div>
                        </div>

                        <!-- Warning Messages -->
                        <div class="border border-dashed border-gray-300 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-800">
                                    Warning Messages
                                </h3>
                                <div class="flex items-center gap-2">
                                    <JetLabel
                                        value="Text colour"
                                        class="text-xs text-gray-600"
                                    />
                                    <JetInput
                                        v-model="themeForm.warningTextColor"
                                        type="text"
                                        class="w-24 text-xs"
                                    />
                                    <ColorPicker
                                        :pureColor="themeForm.warningTextColor"
                                        @pureColorChange="(color) => themeForm.warningTextColor = rgbToHex(color)"
                                        format="hex"
                                        shape="square"
                                        style="z-index: 10001"
                                    />
                                </div>
                            </div>
                            <div
                                class="w-full h-32 rounded-lg border border-gray-200 flex items-center justify-center"
                            >
                                <div
                                    class="px-6 py-2 rounded-md text-sm font-semibold border"
                                    :style="{
                                        color: themeForm.warningTextColor,
                                        backgroundColor:
                                            themeForm.warningBackgroundColor,
                                        borderColor: themeForm.warningBorderColor,
                                    }"
                                >
                                    Please select at least 1 item
                                </div>
                            </div>
                            <div class="mt-4 space-y-3">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1">
                                        <JetLabel
                                            value="Background colour"
                                            class="text-xs"
                                        />
                                        <JetInput
                                            v-model="themeForm.warningBackgroundColor"
                                            type="text"
                                            class="mt-1 w-full text-xs"
                                        />
                                    </div>
                                    <ColorPicker
                                        :pureColor="themeForm.warningBackgroundColor"
                                        @pureColorChange="(color) => themeForm.warningBackgroundColor = rgbToHex(color)"
                                        format="hex"
                                        shape="square"
                                        style="z-index: 10001"
                                    />
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1">
                                        <JetLabel
                                            value="Border colour"
                                            class="text-xs"
                                        />
                                        <JetInput
                                            v-model="themeForm.warningBorderColor"
                                            type="text"
                                            class="mt-1 w-full text-xs"
                                        />
                                    </div>
                                    <ColorPicker
                                        :pureColor="themeForm.warningBorderColor"
                                        @pureColorChange="(color) => themeForm.warningBorderColor = rgbToHex(color)"
                                        format="hex"
                                        shape="square"
                                        style="z-index: 10001"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <JetButton
                variant="default"
                class="mr-2 uppercase"
                size="sm"
                @click="showThemeModal = false"
            >
                Cancel
            </JetButton>
            <JetButton
                variant="primary"
                size="sm"
                class="uppercase"
                @click="saveTheme"
                :disabled="!themeForm.name"
            >
                {{ editingThemeId ? "Update Theme" : "Save Theme" }}
            </JetButton>
        </template>
    </JetDialogModal>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetButton from "@/Componentsv2/Button.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetLabel from "@/Jetstream/Label.vue";
import { ColorPicker } from "vue3-colorpicker";
import "vue3-colorpicker/style.css";

// Helper function to convert RGB/RGBA to hex
const rgbToHex = (color) => {
    if (!color) return "#000000";
    // If already hex, return as is
    if (color.startsWith("#")) return color;
    // If RGB/RGBA format
    const rgbMatch = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
    if (rgbMatch) {
        const r = parseInt(rgbMatch[1]).toString(16).padStart(2, "0");
        const g = parseInt(rgbMatch[2]).toString(16).padStart(2, "0");
        const b = parseInt(rgbMatch[3]).toString(16).padStart(2, "0");
        return `#${r}${g}${b}`;
    }
    return color;
};

const themes = ref([
    {
        id: 1,
        name: "Default",
        type: "Pre-defined",
        applied: true,
        label: "",
        bgClass: "theme-default",
    },
    {
        id: 2,
        name: "Aquarium",
        type: "Pre-defined",
        applied: false,
        label: "",
        bgClass: "theme-aquarium",
    },
    {
        id: 3,
        name: "D4D",
        type: "Pre-defined",
        applied: false,
        label: "",
        bgClass: "theme-d4d",
    },
    {
        id: 4,
        name: "chin",
        type: "Customed",
        applied: false,
        label: "Image",
        bgClass: "theme-custom-1",
    },
    {
        id: 5,
        name: "Nando",
        type: "Customed",
        applied: false,
        label: "",
        bgClass: "theme-custom-2",
    },
    {
        id: 6,
        name: "TFL design",
        type: "Customed",
        applied: false,
        label: "",
        bgClass: "theme-custom-3",
    },
    {
        id: 7,
        name: "Brand Manager",
        type: "Customed",
        applied: false,
        label: "",
        bgClass: "theme-brand-manager",
    },
    {
        id: 8,
        name: "Wild Nutrition Theme",
        type: "Customed",
        applied: false,
        label: "",
        bgClass: "theme-wild-nutrition",
    },
    {
        id: 9,
        name: "test",
        type: "Customed",
        applied: false,
        label: "",
        bgClass: "theme-test",
    },
    {
        id: 10,
        name: "name",
        type: "Customed",
        applied: false,
        label: "",
        bgClass: "theme-name",
    },
]);

const showThemeModal = ref(false);
const editingThemeId = ref(null);

const defaultThemeForm = () => ({
    name: "",
    surveyBackgroundImage: "",
    surveyBackgroundBrightness: 100,
    surveyBackgroundOpacity: 1,
    surveyBackgroundBlur: 0,
    surveyBackgroundColor: "#FFFFFF",
    "--header-color": "#000000",
    instructionTextColor: "#000000",
    "--bottom-text-color": "#000000",
    "--button-bg-color": "#e38733",
    buttonHoverColor: "#e17126",
    "--button-font-color": "#ffffff",
    "--button-arrow-color": "#ffffff",
    "--button-arrow-width": "2vmin",
    "--button-arrow-height": "2vmin",
    "--button-arrow-display": "block",
    "--button-arrow-transform-x": "1",
    warningTextColor: "#ffffff",
    warningBackgroundColor: "#f25050",
    warningBorderColor: "#b1201",
    "--prime-border-color": "#d1d5db",
    "--prime-bg-color": "#ffffff",
    "--prime-font-color": "#111827",
    "--target-border-color": "#e5e7eb",
    "--target-bg-color": "#ffffff",
    "--target-text-color": "#111827",
    "--likert-prime-border-color": "#d1d5db",
    "--likert-prime-bg-color": "#ffffff",
    "--likert-target-1-border-color": "#dc2626",
    "--likert-target-1-bg-color": "#dc2626",
    "--likert-target-1-text-color": "#ffffff",
    "--likert-target-2-border-color": "#ea580c",
    "--likert-target-2-bg-color": "#ea580c",
    "--likert-target-2-text-color": "#ffffff",
    "--likert-target-3-border-color": "#f59e0b",
    "--likert-target-3-bg-color": "#f59e0b",
    "--likert-target-3-text-color": "#ffffff",
    "--likert-target-4-border-color": "#84cc16",
    "--likert-target-4-bg-color": "#84cc16",
    "--likert-target-4-text-color": "#ffffff",
    "--likert-target-5-border-color": "#22c55e",
    "--likert-target-5-bg-color": "#22c55e",
    "--likert-target-5-text-color": "#ffffff",
});

const themeForm = ref(defaultThemeForm());

const resetThemeForm = () => {
    themeForm.value = defaultThemeForm();
};

const openAddTheme = () => {
    editingThemeId.value = null;
    resetThemeForm();
    showThemeModal.value = true;
};

const openThemeActionsId = ref(null);

const closeActions = () => {
    openThemeActionsId.value = null;
};

const toggleActions = (id) => {
    openThemeActionsId.value = openThemeActionsId.value === id ? null : id;
};

const applyTheme = (theme) => {
    themes.value.forEach((t) => {
        t.applied = t.id === theme.id;
    });
    closeActions();
};

const copyTheme = (theme) => {
    const maxId = themes.value.reduce((max, t) => Math.max(max, t.id), 0);
    themes.value.push({
        ...theme,
        id: maxId + 1,
        name: `${theme.name} copy`,
        applied: false,
        config: theme.config ? { ...theme.config } : undefined,
    });
    closeActions();
};

const editTheme = (theme) => {
    editingThemeId.value = theme.id;
    const base = theme.config || defaultThemeForm();
    themeForm.value = {
        ...defaultThemeForm(),
        ...base,
        name: theme.name,
    };
    showThemeModal.value = true;
    closeActions();
};

const saveTheme = () => {
    const trimmedName = (themeForm.value.name || "").trim();
    if (!trimmedName) return;

    if (editingThemeId.value) {
        const idx = themes.value.findIndex((t) => t.id === editingThemeId.value);
        if (idx !== -1) {
            const existing = themes.value[idx];
            themes.value[idx] = {
                ...existing,
                name: trimmedName,
                type: existing.type || "Customed",
                config: { ...themeForm.value },
            };
        }
    } else {
        const maxId = themes.value.reduce((max, t) => Math.max(max, t.id), 0);
        themes.value.push({
            id: maxId + 1,
            name: trimmedName,
            type: "Customed",
            applied: false,
            label: "",
            bgClass: "theme-custom-3",
            config: { ...themeForm.value },
        });
    }

    showThemeModal.value = false;
};

const deleteTheme = (theme) => {
    themes.value = themes.value.filter((t) => t.id !== theme.id);
    closeActions();
};

const handleClickOutside = (event) => {
    const dropdowns = document.querySelectorAll("[data-theme-actions-root]");
    let clickedInside = false;
    dropdowns.forEach((el) => {
        if (el.contains(event.target)) {
            clickedInside = true;
        }
    });
    if (!clickedInside) {
        closeActions();
    }
};

onMounted(() => {
    window.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
    window.removeEventListener("click", handleClickOutside);
});
</script>

<style scoped>
.theme-default {
    background: radial-gradient(circle at 20% 20%, #f3f4f6 0, #e5e7eb 40%, #ffffff 80%);
}

.theme-aquarium {
    background-image: linear-gradient(135deg, #3b82f6, #22d3ee);
}

.theme-d4d {
    background-image: radial-gradient(circle, #ffffff 10%, #e5e7eb 40%, #f9fafb 70%);
}

.theme-custom-1 {
    background-image: linear-gradient(135deg, #f97316, #fb923c);
}

.theme-custom-2 {
    background-image: linear-gradient(135deg, #e5e7eb, #d1d5db);
}

.theme-custom-3 {
    background-image: linear-gradient(135deg, #0f766e, #14b8a6);
}

.theme-brand-manager {
    background-image: linear-gradient(135deg, #0ea5e9, #1d4ed8);
}

.theme-wild-nutrition {
    background-image: linear-gradient(135deg, #15803d, #22c55e);
}

.theme-test {
    background-image: linear-gradient(135deg, #e11d48, #fb7185);
}

.theme-name {
    background-image: linear-gradient(135deg, #4b5563, #9ca3af);
}
</style>
