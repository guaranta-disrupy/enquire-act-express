<template>
  <div class="flex flex-col gap-4">
    <!-- Navigation Buttons Row -->
    <div class="flex flex-wrap gap-2 overflow-x-auto pb-2">
      <button
        v-if="form.config2.progress && showDisplayLayout"
        type="button"
        @click="handleTabChange('displayLayout')"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-all duration-200 whitespace-nowrap"
        :class="activeTab === 'displayLayout' ? 'bg-[#15919A] text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
      >
        <svg width="16" height="16" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
          <g clip-path="url(#clip0_621_14998)">
          <path d="M41.1415 0H8.8584C3.96572 0 0 3.96582 0 8.85772V15.625H50V8.85772C50 3.96582 46.0342 0 41.1415 0Z" :fill="activeTab === 'displayLayout' ? 'white' : '#15919A'"/>
          <path d="M0 41.1423C0 46.0342 3.96572 50 8.8584 50H28.9062V21.0938H0V41.1423Z" :fill="activeTab === 'displayLayout' ? 'white' : '#15919A'"/>
          <path d="M34.375 50H41.1415C46.0342 50 50 46.0342 50 41.1423V21.0938H34.375V50Z" :fill="activeTab === 'displayLayout' ? 'white' : '#15919A'"/>
          </g>
          <defs>
          <clipPath id="clip0_621_14998">
          <rect width="50" height="50" fill="white"/>
          </clipPath>
          </defs>
        </svg>
        <span>Display Layout</span>
      </button>

      <button
        type="button"
        @click="handleTabChange('responseForwarding')"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-all duration-200 whitespace-nowrap"
        :class="activeTab === 'responseForwarding' ? 'bg-[#15919A] text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2L2 7L12 12L22 7L12 2Z" :stroke="activeTab === 'responseForwarding' ? 'white' : '#15919A'" stroke-width="2" stroke-linejoin="round"/>
          <path d="M2 17L12 22L22 17" :stroke="activeTab === 'responseForwarding' ? 'white' : '#15919A'" stroke-width="2" stroke-linejoin="round"/>
          <path d="M2 12L12 17L22 12" :stroke="activeTab === 'responseForwarding' ? 'white' : '#15919A'" stroke-width="2" stroke-linejoin="round"/>
        </svg>
        <span>Response Forwarding</span>
      </button>

      <button
        v-if="form.config2.progress"
        type="button"
        @click="handleTabChange('progressSettings')"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-all duration-200 whitespace-nowrap"
        :class="activeTab === 'progressSettings' ? 'bg-[#15919A] text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M9 12L11 14L15 10" :stroke="activeTab === 'progressSettings' ? 'white' : '#15919A'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="12" cy="12" r="10" :stroke="activeTab === 'progressSettings' ? 'white' : '#15919A'" stroke-width="2"/>
        </svg>
        <span>Progress Settings</span>
      </button>

      <button
        v-if="form.config2.progress"
        type="button"
        @click="handleTabChange('buttonSettings')"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-all duration-200 whitespace-nowrap"
        :class="activeTab === 'buttonSettings' ? 'bg-[#15919A] text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="3" y="3" width="18" height="18" rx="2" :stroke="activeTab === 'buttonSettings' ? 'white' : '#15919A'" stroke-width="2"/>
          <path d="M9 9H15V15H9V9Z" :stroke="activeTab === 'buttonSettings' ? 'white' : '#15919A'" stroke-width="2"/>
        </svg>
        <span>Button Settings</span>
      </button>

      <button
        v-if="form.config2.progress"
        type="button"
        @click="handleTabChange('fullscreenSettings')"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-all duration-200 whitespace-nowrap"
        :class="activeTab === 'fullscreenSettings' ? 'bg-[#15919A] text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M8 3H5C3.89543 3 3 3.89543 3 5V8M21 3H18M21 8V5C21 3.89543 20.1046 3 19 3M3 16V19C3 20.1046 3.89543 21 5 21H8M16 21H19C20.1046 21 21 20.1046 21 19V16" :stroke="activeTab === 'fullscreenSettings' ? 'white' : '#15919A'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Full Screen Settings</span>
      </button>

      <button
        v-if="form.config2.progress"
        type="button"
        @click="handleTabChange('backgroundSettings')"
        class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-all duration-200 whitespace-nowrap"
        :class="activeTab === 'backgroundSettings' ? 'bg-[#15919A] text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
      >
        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M16.25 3.125H3.75C2.71447 3.125 1.875 3.96447 1.875 5V15C1.875 16.0355 2.71447 16.875 3.75 16.875H16.25C17.2855 16.875 18.125 16.0355 18.125 15V5C18.125 3.96447 17.2855 3.125 16.25 3.125Z" :stroke="activeTab === 'backgroundSettings' ? 'white' : '#15919A'" stroke-width="1.5" stroke-linejoin="round"/>
          <path d="M13.125 8.125C13.8154 8.125 14.375 7.56536 14.375 6.875C14.375 6.18464 13.8154 5.625 13.125 5.625C12.4346 5.625 11.875 6.18464 11.875 6.875C11.875 7.56536 12.4346 8.125 13.125 8.125Z" :stroke="activeTab === 'backgroundSettings' ? 'white' : '#15919A'" stroke-width="1.5" stroke-miterlimit="10"/>
          <path d="M11.875 13.1166L8.33359 9.58184C8.10824 9.35654 7.80535 9.22581 7.48684 9.21637C7.16832 9.20693 6.85822 9.3195 6.61992 9.53106L1.875 13.7498M8.75 16.8748L13.568 12.0568C13.7883 11.8361 14.0832 11.7056 14.3948 11.6912C14.7064 11.6767 15.0121 11.7793 15.252 11.9787L18.125 14.3748" :stroke="activeTab === 'backgroundSettings' ? 'white' : '#15919A'" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Background Image</span>
      </button>
    </div>

    <!-- Carousel Container -->
    <div class="relative overflow-hidden" ref="carouselContainer" style="min-height: 600px;">
      <transition-group 
        :name="slideDirection === 'reverse' ? 'slide-reverse' : 'slide'" 
        tag="div" 
        class="relative" 
        style="min-height: 600px;"
      >
        <!-- Display Layout -->
         
        <div
          v-if="form.config2.progress && showDisplayLayout && activeTab === 'displayLayout'"
          key="displayLayout"
          class="absolute inset-0 p-4 bg-white border border-gray-200 shadow-lg rounded-3xl overflow-y-auto"
        >

        <h3 class="text-sm font-semibold tracking-wide text-gray-500 mb-4">
          Use the layout selector to choose how primes and targets are arranged.
        </h3>
        
          <div class="w-full">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="group relative">
                <div class="w-full px-6 py-6 border-2 cursor-pointer text-center justify-center items-center shadow-md flex transition-all duration-300 rounded-2xl hover:shadow-lg hover:scale-[1.02]"
                     :class="selectedDisplayStyle === 'rounded' ? 'border-[#15919A] bg-gradient-to-br from-[#15919A]/10 to-[#15919A]/5 shadow-[#15919A]/20' : 'border-gray-200 hover:border-[#15919A]/50 bg-white'"
                     @click="selectDisplayStyle('rounded')">
                  <div class="flex flex-col items-center justify-center gap-4 font-bold text-center">
                    <div class="text-gray-800 group-hover:text-[#15919A] transition-colors duration-300">
                      Attribute Choice Rounded Targets
                    </div>
                    <div class="p-2 bg-gray-50 rounded-xl group-hover:bg-[#15919A]/5 transition-colors duration-300">
                      <svg width="82" height="82" viewBox="0 0 82 82" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <rect x="9.9082" y="11.9766" width="58.1836" height="58.1836" rx="29.0918" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
                      <rect x="30.9688" y="2.5" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
                      <rect x="2.53613" y="18.7158" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
                      <rect x="2.53613" y="46.3066" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
                      <rect x="30.9688" y="62.5225" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
                      <rect x="59.4043" y="46.3066" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
                      <rect x="59.4043" y="18.7158" width="16.0599" height="16.0599" rx="8.02996" fill="#D9D9D9" stroke="#ADADAD" stroke-width="5"/>
                      </svg>
                    </div>
                  </div>
                </div>
              </div>

              <div class="group relative">
                <div class="w-full px-6 py-6 border-2 cursor-pointer text-center justify-center items-center shadow-md flex transition-all duration-300 rounded-2xl hover:shadow-lg hover:scale-[1.02]"
                     :class="selectedDisplayStyle === 'box' ? 'border-[#15919A] bg-gradient-to-br from-[#15919A]/10 to-[#15919A]/5 shadow-[#15919A]/20' : 'border-gray-200 hover:border-[#15919A]/50 bg-white'"
                     @click="selectDisplayStyle('box')">
                  <div class="flex flex-col items-center justify-center gap-4 font-bold text-center">
                    <div class="text-gray-800 group-hover:text-[#15919A] transition-colors duration-300">
                     Attributes Choice Box Targets
                    </div>
                    <div class="p-2 bg-gray-50 rounded-xl group-hover:bg-[#15919A]/5 transition-colors duration-300">
                      <svg width="144" height="80" viewBox="0 0 144 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <rect x="38.25" y="4.25" width="69.5" height="8.5" rx="4.25" fill="#D9D9D9" stroke="#ADADAD" stroke-width="8.5"/>
                      <rect x="1.5" y="26" width="43" height="24" rx="2" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
                      <rect x="1.5" y="55" width="43" height="24" rx="2" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
                      <rect x="50.5" y="27" width="43" height="24" rx="2" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
                      <rect x="50.5" y="55" width="43" height="24" rx="1" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
                      <rect x="99.5" y="55" width="43" height="24" rx="1" fill="#D9D9D9" stroke="#ADADAD" stroke-width="2"/>
                      <mask id="path-8-inside-1_312_11230" fill="white">
                      <rect x="98.5" y="26" width="45" height="26" rx="1"/>
                      </mask>
                      <rect x="98.5" y="26" width="45" height="26" rx="1" fill="#D9D9D9" stroke="#ADADAD" stroke-width="4" mask="url(#path-8-inside-1_312_11230)"/>
                      </svg>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Response Forwarding -->
        <div
          v-if="activeTab === 'responseForwarding'"
          key="responseForwarding"
          class="absolute inset-0 p-4 bg-white border border-gray-200 shadow-lg rounded-3xl overflow-y-auto"
        >
        <h3 class="text-sm font-semibold tracking-wide text-gray-500 mb-4">
          Configure how responses are forwarded
        </h3>

          <!-- Forward responses toggle -->
          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl mb-4">
            <div>
              <span class="text-sm font-medium text-gray-700">Forward responses</span>
              <p class="text-xs text-gray-500 mt-1">Enable automatic response forwarding</p>
            </div>
            <Toggle v-model="form.config.forward_response" />
          </div>

          <!-- Response variable -->
          <div class="space-y-3 mb-4">
            <div class="flex flex-row gap-2">
              <JetLabel value="Response variable" class="text-sm font-medium text-gray-700" />
              <div class="relative group">
                <Tooltip text="Variable name where responses will be stored for forwarding." />
                <div
                  class="absolute bottom-full left--100 mb-1 px-1.5 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                  role="tooltip"
                  style="right: -90px;"
                >
                  Variable name where responses <br/>will be stored for forwarding.
                </div>
              </div>
            </div>
            <JetInput
              type="text"
              v-model="form.config.response_var"
              :disabled="!form.config.forward_response"
              placeholder="Enter variable..."
              class="w-full border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
            />
          </div>

          <!-- Data type -->
          <div class="space-y-3 mb-4">
            <JetLabel value="Data type" class="text-sm font-medium text-gray-700" />
            <select
              class="w-full border-gray-300 rounded-xl form-select focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
              v-model="form.config.response_type"
            >
              <option value="selection">Selection</option>
              <option value="rt">Reaction Time</option>
              <option value="rank">Rank</option>
              <option value="refined">Refined</option>
              <option value="coded">Coded</option>
            </select>
          </div>

          <!-- Redirect -->
          <div class="space-y-3 mb-4">
            <div class="flex flex-row gap-2">
              <JetLabel value="Redirect URL" class="text-sm font-medium text-gray-700" />
              <div class="relative group">
                <Tooltip text="Where to send users after completing the flow." />
                <div
                  class="absolute bottom-full left--100 mb-1 px-1.5 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                  role="tooltip"
                  style="right: -75px;"
                >
                  Where to send users after <br/>completing the flow.
                </div>
              </div>
            </div>
            <JetInput
              type="text"
              v-model="form.config.redirect"
              placeholder="Override project redirect"
              class="w-full border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
            />
          </div>

          <!-- Reference -->
          <div class="space-y-3">
            <div class="flex flex-row gap-2">
              <JetLabel value="Survey reference" class="text-sm font-medium text-gray-700" />
              <div class="relative group">
                <Tooltip text="Optional identifier for backend tracking or analytics." />
                <div
                  class="absolute bottom-full left--100 mb-1 px-1.5 py-2 rounded text-xs text-white bg-[#15919a] shadow z-20 whitespace-nowrap opacity-0 translate-y-1 pointer-events-none transition duration-150 ease-out group-hover:opacity-100 group-hover:translate-y-0"
                  role="tooltip"
                  style="right: -85px;"
                >
                  Optional identifier for backend <br/>tracking or analytics.
                </div>
              </div>
            </div>
            <JetInput
              type="text"
              v-model="form.config.reference"
              placeholder="Custom survey reference"
              class="w-full text-xs text-gray-600 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
            />
          </div>
        </div>

        <!-- Progress Settings -->
        <div
          v-if="form.config2.progress && activeTab === 'progressSettings'"
          key="progressSettings"
          class="absolute inset-0 p-4 bg-white border border-gray-200 shadow-lg rounded-3xl overflow-y-auto"
        >

        <h3 class="text-sm font-semibold tracking-wide text-gray-500 mb-4">
          Configure progress indicators and messages
        </h3>

          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl mb-4">
            <div>
              <span class="text-sm font-medium text-gray-700">Enable progress information</span>
              <p class="text-xs text-gray-500 mt-1">Show progress indicators to users</p>
            </div>
            <Toggle v-model="form.config2.progress.show" />
          </div>

          <!-- Textareas -->
          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <JetLabel value="Progress text" class="text-sm font-medium text-gray-700 mb-2" />
                <textarea
                  class="w-full p-4 text-sm border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 resize-none"
                  v-model="form.config2.progress.text"
                  placeholder="Enter progress text..."
                  rows="3"
                ></textarea>
              </div>

              <div>
                <JetLabel value="Saving progress text" class="text-sm font-medium text-gray-700 mb-2" />
                <textarea
                  class="w-full p-4 text-sm border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 resize-none"
                  v-model="form.config2.texts.saving"
                  placeholder="Enter saving progress text..."
                  rows="3"
                ></textarea>
                <jet-input-error :message="form.errors['config2.texts.saving']" />
              </div>
            </div>

            <div>
              <JetLabel value="Redirecting text" class="text-sm font-medium text-gray-700 mb-2" />
              <textarea
                class="w-full p-4 text-sm border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 resize-none"
                v-model="form.config2.texts.redirecting"
                placeholder="Enter redirecting text..."
                rows="3"
              ></textarea>
              <jet-input-error
                :message="form.errors['config2.texts.redirecting']"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <JetLabel value="Error starting the app" class="text-sm font-medium text-gray-700 mb-2" />
                <textarea
                  class="w-full p-4 text-sm border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 resize-none"
                  v-model="form.config2.texts.error_start"
                  placeholder="Enter error message for app start..."
                  rows="3"
                ></textarea>
                <jet-input-error
                  :message="form.errors['config2.texts.error_start']"
                />
              </div>

              <div>
                <JetLabel value="Error saving progress" class="text-sm font-medium text-gray-700 mb-2" />
                <textarea
                  class="w-full p-4 text-sm border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 resize-none"
                  v-model="form.config2.texts.error_saving"
                  placeholder="Enter error message for saving progress..."
                  rows="3"
                ></textarea>
                <jet-input-error
                  :message="form.errors['config2.texts.error_saving']"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Button Settings -->
        <div
          v-if="form.config2.progress && activeTab === 'buttonSettings'"
          key="buttonSettings"
          class="absolute inset-0 p-4 bg-white border border-gray-200 shadow-lg rounded-3xl overflow-y-auto"
        >
        <h3 class="text-sm font-semibold tracking-wide text-gray-500 mb-4">
          Configure button behavior and timing
        </h3>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- General Button Settings -->
            <div class="space-y-4">
              <div class="space-y-2">
                <JetLabel value="Button Delay (ms)" class="text-sm font-medium text-gray-700" />
                <input 
                  type="number" 
                  @change="handleButtonDelayChange()" 
                  v-model="form.config.buttons.button_delay" 
                  placeholder="0" 
                  class="w-full border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200" 
                  step="100" 
                />
                <p class="text-xs text-gray-500">Delay before buttons become interactive (in milliseconds)</p>
              </div>

              <div class="p-4 bg-gray-50 rounded-2xl">
                <div class="flex items-center justify-between">
                  <div class="text-sm font-medium text-gray-700">Countdown timer visible</div>
                  <Toggle v-model="form.config.buttons.is_countdown_visible" />
                </div>
              </div>
            </div>

            <!-- Button Round Labels -->
            <div class="space-y-4">
              <h2 class="text-sm font-bold text-[#323232] mb-2">Button Round label</h2>
              <div class="space-y-3">
                <div>
                  <JetLabel value="Continue button label" class="text-sm font-medium text-gray-700 mb-2" />
                  <textarea
                    class="w-full p-3 text-sm border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 resize-none"
                    v-model="form.config.buttons.final"
                    placeholder="Enter continue button label..."
                    rows="2"
                  ></textarea>
                  <jet-input-error
                    :message="form.errors['config.buttons.final']"
                  />
                </div>

                <div>
                  <JetLabel value="Next button label" class="text-sm font-medium text-gray-700 mb-2" />
                  <textarea
                    class="w-full p-3 text-sm border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 resize-none"
                    v-model="form.config.buttons.next"
                    placeholder="Enter next button label..."
                    rows="2"
                  ></textarea>
                  <jet-input-error
                    :message="form.errors['config.buttons.next']"
                  />
                </div>
              </div>
            </div>

            <!-- Unselect Settings -->
            <div class="space-y-4">
              <h2 class="text-sm font-bold text-[#323232] mb-2">Confirm Empty Selection Settings</h2>
              <div class="space-y-3">
                <div>
                  <JetLabel value="Unselect message" class="text-sm font-medium text-gray-700 mb-2" />
                  <textarea
                    class="w-full p-3 text-sm border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 resize-none"
                    v-model="form.config.unselect_settings.unselect_message"
                    placeholder="Enter unselect message..."
                    rows="2"
                  ></textarea>
                  <jet-input-error
                    :message="form.errors['config.unselect_settings.unselect_message']"
                  />
                </div>

                <div>
                  <JetLabel value="Cancel button label" class="text-sm font-medium text-gray-700 mb-2" />
                  <textarea
                    class="w-full p-3 text-sm border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 resize-none"
                    v-model="form.config.unselect_settings.unselect_cancel"
                    placeholder="Enter unselect cancel..."
                    rows="2"
                  ></textarea>
                  <jet-input-error
                    :message="form.errors['config.unselect_settings.unselect_cancel']"
                  />
                </div>

                <div>
                  <JetLabel value="Submit button label" class="text-sm font-medium text-gray-700 mb-2" />
                  <textarea
                    class="w-full p-3 text-sm border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 resize-none"
                    v-model="form.config.unselect_settings.unselect_submit"
                    placeholder="Enter unselect submit..."
                    rows="2"
                  ></textarea>
                  <jet-input-error
                    :message="form.errors['config.unselect_settings.unselect_submit']"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Full Screen Settings -->
        <div
          v-if="form.config2.progress && activeTab === 'fullscreenSettings'"
          key="fullscreenSettings"
          class="absolute inset-0 p-4 bg-white border border-gray-200 shadow-lg rounded-3xl overflow-y-auto"
        >

        <h3 class="text-sm font-semibold tracking-wide text-gray-500 mb-4">
          Configure full screen mode text and buttons in mobile devices
        </h3>

          <div class="flex flex-col gap-4">
            <div>
              <JetLabel value="Full Screen Text" class="text-sm font-medium text-gray-700 mb-2" />
              <textarea
                class="w-full p-4 text-sm border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 resize-none"
                v-model="form.config.fullscreen_text"
                placeholder="Enter full screen text..."
                rows="3"
              ></textarea>
              <jet-input-error
                :message="form.errors['config2.texts.error_saving']"
              />
            </div>

            <div>
              <JetLabel value="Full Screen Button" class="text-sm font-medium text-gray-700 mb-2" />
              <textarea
                class="w-full p-4 text-sm border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 resize-none"
                v-model="form.config.fullscreen_button"
                placeholder="Enter full screen button text..."
                rows="3"
              ></textarea>
              <jet-input-error
                :message="form.errors['config2.texts.error_saving']"
              />
            </div>
          </div>
        </div>

        <!-- Background Image Settings -->
        <div
          v-if="form.config2.progress && activeTab === 'backgroundSettings'"
          key="backgroundSettings"
          class="absolute inset-0 p-4 bg-white border border-gray-200 shadow-lg rounded-3xl overflow-y-auto"
        >

        <h3 class="text-sm font-semibold tracking-wide text-gray-500 mb-4">
          Configure background image for the survey
        </h3>

          <div class="flex flex-col gap-4">
            <div>
              <JetLabel value="Image URL" class="text-sm font-medium text-gray-700 mb-2" />
              <textarea
                class="w-full p-4 text-sm border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 resize-none"
                v-model="form.config.engine_bg_image"
                placeholder="Enter image URL..."
                rows="3"
              ></textarea>
              <jet-input-error
                :message="form.errors['config.engine_bg_image']"
              />
            </div>

            <div>
              <JetLabel value="Image Display" class="text-sm font-medium text-gray-700 mb-2" />
              <div v-if="form.config.engine_bg_image && form.config.engine_bg_image.trim()" class="mt-2">
                <div class="relative w-full border-2 border-gray-200 rounded-xl overflow-hidden bg-gray-50" style="min-height: 200px;">
                  <img
                    :src="form.config.engine_bg_image"
                    alt="Background image preview"
                    class="w-full h-[340px] object-contain"
                    @error="handleImageError"
                    @load="handleImageLoad"
                  />
                  <div v-if="imageError" class="absolute inset-0 flex items-center justify-center p-4">
                    <div class="text-center">
                      <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <p class="text-sm text-gray-500">Failed to load image</p>
                      <p class="text-xs text-gray-400 mt-1">Please check the URL</p>
                    </div>
                  </div>
                  <div v-if="imageLoading && !imageError" class="absolute inset-0 flex items-center justify-center bg-gray-50">
                    <div class="text-center">
                      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                      <p class="text-sm text-gray-500 mt-2">Loading image...</p>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="mt-2 p-8 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 flex items-center justify-center">
                <div class="text-center">
                  <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <p class="text-sm text-gray-500">Enter an image URL above to preview</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup>
import JetCheckbox from "@/Jetstream/Checkbox.vue";
import JetLabel from "@/Jetstream/Label.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import Toggle from "@/Jetstream/Toggle.vue";
import Tooltip from "./Tooltip.vue";

import { PrismEditor } from "vue-prism-editor";
import "vue-prism-editor/dist/prismeditor.min.css"; // import the styles somewhere

// import highlighting library (you can use any library you want just return html string)
import { highlight, languages } from "prismjs/components/prism-core";
import "prismjs/components/prism-clike";
import "prismjs/components/prism-javascript";
import "prismjs/components/prism-css";
import "prismjs/components/prism-markup";
import "prismjs/themes/prism-tomorrow.css";
// import syntax highlighting styles

import { ColorPicker } from "vue3-colorpicker";
import "vue3-colorpicker/style.css";

// QuillEditor imports
import { QuillEditor, Quill } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.bubble.css";

// Custom font size module for vmin values
const Size = Quill.import("attributors/style/size");
Size.whitelist = [
  "1vmin",
  "2vmin",
  "3vmin",
  "4vmin",
  "5vmin",
  "6vmin",
  "7vmin",
  "8vmin",
  "9vmin",
  "10vmin",
];
Quill.register(Size, true);

const modelValue = defineModel({ type: Boolean, default: false });

import { inject, ref, watch, nextTick, onMounted, onUnmounted, computed } from "vue";

const showNextPicker = ref(false);
const showFinalPicker = ref(false);
const showNextIconPicker = ref(false);
const showFinalIconPicker = ref(false);
const colorPickerPosition = ref({ top: 0, left: 0 });

// Image preview state
const imageLoading = ref(false);
const imageError = ref(false);

const props = defineProps([
    "survey",
]);
const $emit = defineEmits(["update:btn_delay"]);

console.log("props", props.survey.config.buttons);

const handleButtonDelayChange = () => {
 
  $emit("update:btn_delay", form.config.buttons.button_delay);
};

const form = inject("form");
// console.log("form", form);

const showDisplayLayout = computed(() => {
  if (!form) {
    return true;
  }

  let theme = null;

  // Check form.config.theme first (actively changed value) before form.config2.theme
  if (form.config && form.config.theme !== undefined && form.config.theme !== null && form.config.theme !== "") {
    theme = form.config.theme;
  } else if (form.config2 && form.config2.theme !== undefined && form.config2.theme !== null && form.config2.theme !== "") {
    theme = form.config2.theme;
  }

  if (theme === null) {
    return true;
  }

  return Number(theme) !== 2;
});
console.log("form", form);

// Carousel active tab state
const activeTab = ref('responseForwarding');
const slideDirection = ref("forward"); // 'forward' or 'reverse'

// Function to handle tab change with direction detection
const handleTabChange = (newTab) => {
  // Define tab order
  const tabOrder = ['displayLayout', 'responseForwarding', 'progressSettings', 'buttonSettings', 'fullscreenSettings', 'backgroundSettings'];
  
  const currentIndex = tabOrder.indexOf(activeTab.value);
  const newIndex = tabOrder.indexOf(newTab);
  
  if (newIndex > currentIndex) {
    slideDirection.value = "forward"; // Moving forward (right to left)
  } else if (newIndex < currentIndex) {
    slideDirection.value = "reverse"; // Moving backward (left to right)
  }
  
  activeTab.value = newTab;
};


// Ensure buttons configuration objects exist
if (!form.config.buttons) {
  form.config.buttons = {};
}
if (form.config.buttons.button_delay === undefined) {
  form.config.buttons.button_delay = 0;
}
if (form.config.buttons.is_countdown_visible === undefined) {
  form.config.buttons.is_countdown_visible = false;
}

// Ensure engine_bg_image exists
if (form.config.engine_bg_image === undefined) {
  form.config.engine_bg_image = "";
}

// Ensure unselect_settings configuration object exists
if (!form.config.unselect_settings) {
  form.config.unselect_settings = {};
}
if (form.config.unselect_settings.unselect_message === undefined) {
  form.config.unselect_settings.unselect_message = "Please confirm that none of these options apply.";
}
if (form.config.unselect_settings.unselect_cancel === undefined) {
  form.config.unselect_settings.unselect_cancel = "Cancel";
}
if (form.config.unselect_settings.unselect_submit === undefined) {
  form.config.unselect_settings.unselect_submit = "Submit";
}

// Initialize button settings with default values
if (!form.config2.buttons) {
  form.config2.buttons = {};
}
if (form.config2.buttons.finalShowIcon === undefined) {
  form.config2.buttons.finalShowIcon = true;
}
if (form.config2.buttons.nextShowIcon === undefined) {
  form.config2.buttons.nextShowIcon = true;
}

const changeColor = () => {
  console.log("form", form.config2.buttons.backgroundColor);
};

// QuillEditor configuration
const currentQuill = ref(null);

const editorOptions = ref({
  theme: "bubble",
  modules: {
    toolbar: [
      ["bold", "italic", "underline"],
      [{ color: [] }, { background: [] }],
      [{ align: [] }],
      ["blockquote"],
      [{ list: "ordered" }, { list: "bullet" }],
      ["link"],
      ["clean"],
      [
        {
          size: [
            "1vmin",
            "2vmin",
            "3vmin",
            "4vmin",
            "5vmin",
            "6vmin",
            "7vmin",
            "8vmin",
            "9vmin",
            "10vmin",
          ],
        },
      ],
    ],
  },
  readOnly: false,
  bounds: document.body,
  formats: [
    "bold",
    "italic",
    "underline",
    "color",
    "background",
    "align",
    "blockquote",
    "list",
    "link",
    "size",
  ],
});

const onEditorReady = (quill) => {
  currentQuill.value = quill;
  // Set the text color to match the button's text color
  if (form.config2.buttons?.textColor) {
    quill.format("color", form.config2.buttons.textColor);
  }
};

// Function to strip HTML tags from text
const stripHtmlTags = (html) => {
  if (!html) return "";
  const div = document.createElement("div");
  div.innerHTML = html;
  return div.textContent || div.innerText || "";
};

const handleNextColorPickerClick = (event) => {
  showNextPicker.value = !showNextPicker.value;
  // Close the final picker if it's open
  if (showFinalPicker.value) {
    showFinalPicker.value = false;
  }
};

const handleFinalColorPickerClick = (event) => {
  showFinalPicker.value = !showFinalPicker.value;
  // Close the next picker if it's open
  if (showNextPicker.value) {
    showNextPicker.value = false;
  }
};

const handleNextIconColorPickerClick = (event) => {
  showNextIconPicker.value = !showNextIconPicker.value;
  // Close other pickers if they're open
  if (showNextPicker.value) {
    showNextPicker.value = false;
  }
  if (showFinalPicker.value) {
    showFinalPicker.value = false;
  }
  if (showFinalIconPicker.value) {
    showFinalIconPicker.value = false;
  }
};

const handleFinalIconColorPickerClick = (event) => {
  showFinalIconPicker.value = !showFinalIconPicker.value;
  // Close other pickers if they're open
  if (showNextPicker.value) {
    showNextPicker.value = false;
  }
  if (showFinalPicker.value) {
    showFinalPicker.value = false;
  }
  if (showNextIconPicker.value) {
    showNextIconPicker.value = false;
  }
};

// Close color picker when clicking outside
const handleClickOutside = (event) => {
  const colorPickerContainer = event.target.closest(".property-row");
  if (!colorPickerContainer) {
    if (showNextPicker.value) {
      showNextPicker.value = false;
    }
    if (showFinalPicker.value) {
      showFinalPicker.value = false;
    }
    if (showNextIconPicker.value) {
      showNextIconPicker.value = false;
    }
    if (showFinalIconPicker.value) {
      showFinalIconPicker.value = false;
    }
  }
};

// Add event listener for clicking outside
onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});

// Watch for text color changes and update the editor
watch(
  () => form.config2.buttons?.textColor,
  (newColor) => {
    if (currentQuill.value && newColor) {
      currentQuill.value.format("color", newColor);
    }
  }
);

const highlighter = (code) => {
  return highlight(code, languages.js);
};

const highlighter2 = (code) => {
  return highlight(code, languages.css);
};

const highlighter3 = (code) => {
  return highlight(code, languages.markup);
};

// Initialize selectedDisplayStyle based on theme value
const getDisplayStyleFromTheme = (themeValue) => {
    switch (themeValue) {
        case 1: return 'rounded';
        case 2: return 'outside';
        case 3: return 'box';
        default: return 'rounded';
    }
};

const selectedDisplayStyle = ref(getDisplayStyleFromTheme(form.config.theme));

const selectDisplayStyle = (style) => {
    selectedDisplayStyle.value = style;
    // Map display style to theme value
    const themeValue = style === 'rounded' ? 1 : style === 'outside' ? 2 : 3;
    form.config.theme = themeValue;
};

// Watch for theme changes and update display style accordingly
watch(() => form.config.theme, (newTheme) => {
    selectedDisplayStyle.value = getDisplayStyleFromTheme(newTheme);
});

// Watch for engine_bg_image changes to reset image state
watch(() => form.config.engine_bg_image, (newUrl) => {
    if (newUrl && newUrl.trim()) {
        imageLoading.value = true;
        imageError.value = false;
    } else {
        imageLoading.value = false;
        imageError.value = false;
    }
});

// Image load handlers
const handleImageLoad = () => {
    imageLoading.value = false;
    imageError.value = false;
};

const handleImageError = () => {
    imageLoading.value = false;
    imageError.value = true;
};
</script>

<style scoped>
.grp-header {
  position: absolute;
  top: 0px;
  left: 16px;
  transform: translateY(-50%);
}

.ql-tooltip {
  left: unset !important;
  top: unset !important;
  width: unset !important;
  padding: unset !important;
}

.ql-bubble .ql-tooltip {
  left: unset !important;
  top: unset !important;
  width: 300px !important;
  padding: unset !important;
}

.button-quill-editor .ql-editor {
  padding: 0;
  line-height: 1.2;
  text-align: center;
}

.button-quill-editor .ql-container {
  border: none;
  font-size: inherit;
}

.button-quill-editor .ql-editor.ql-blank::before {
  color: rgba(255, 255, 255, 0.7);
  font-style: normal;
  content: "Click to edit button text...";
}

.button-quill-editor .ql-editor * {
  display: inline !important;
}

.button-quill-editor .ql-editor p {
  margin: 0;
  display: inline;
}

.button-quill-editor .ql-editor br {
  display: none;
}

.button-quill-editor .ql-bubble .ql-editor {
  white-space: nowrap;
  overflow: hidden;
}

.button-quill-editor .ql-bubble .ql-editor:focus {
  white-space: normal;
}

.button-quill-editor .ql-editor::before,
.button-quill-editor .ql-editor::after {
  display: none !important;
}

.ql-editor .ql-size-1vmin {
  font-size: 1vmin !important;
}

.ql-editor .ql-size-2vmin {
  font-size: 2vmin !important;
}

.ql-editor .ql-size-3vmin {
  font-size: 3vmin !important;
}

.ql-editor .ql-size-4vmin {
  font-size: 4vmin !important;
}

.ql-editor .ql-size-5vmin {
  font-size: 5vmin !important;
}

.ql-editor .ql-size-6vmin {
  font-size: 6vmin !important;
}

.ql-editor .ql-size-7vmin {
  font-size: 7vmin !important;
}

.ql-editor .ql-size-8vmin {
  font-size: 8vmin !important;
}

.ql-editor .ql-size-9vmin {
  font-size: 9vmin !important;
}

.ql-editor .ql-size-10vmin {
  font-size: 10vmin !important;
}

/* Size picker styling */
.ql-picker.ql-size {
  width: 110px !important;
}

.ql-picker.ql-size .ql-picker-label::before {
  content: "Size " attr(data-value) !important;
}

.ql-picker.ql-size .ql-picker-item::before {
  content: attr(data-value) !important;
}

.ql-editor {
  font-size: 2.5vmin;
  white-space: pre-wrap !important;
  word-wrap: break-word !important;
  line-height: 1.4 !important;
  overflow-wrap: break-word !important;
}

:deep( img) {
  display: inline-block;
  max-width: 100%;
  max-height: 100%;
  margin: 4px 0px;
  vertical-align: middle;
  border-radius: 4px;
}

/* Carousel slide transitions - Forward (right to left) */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.4s ease-in-out;
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  width: 100%;
}

.slide-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.slide-leave-to {
  opacity: 0;
  transform: translateX(-100%);
}

.slide-enter-to,
.slide-leave-from {
  opacity: 1;
  transform: translateX(0);
}

.slide-move {
  transition: transform 0.4s ease-in-out;
}

/* Carousel slide transitions - Reverse (left to right) */
.slide-reverse-enter-active,
.slide-reverse-leave-active {
  transition: all 0.4s ease-in-out;
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  width: 100%;
}

.slide-reverse-enter-from {
  opacity: 0;
  transform: translateX(-100%);
}

.slide-reverse-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.slide-reverse-enter-to,
.slide-reverse-leave-from {
  opacity: 1;
  transform: translateX(0);
}

.slide-reverse-move {
  transition: transform 0.4s ease-in-out;
}
</style>
