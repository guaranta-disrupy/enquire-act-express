<template>
    <RightSideBar :show="Options.show" @close="Options.show = false">
        <template #title>
            <div class="text-xl font-bold mt-2">Translate Survey Content into Other Languages</div>
            <div class="flex items-start gap-2 pt-4">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                    width="15" height="15" viewBox="0 0 15 15" fill="none">
                    <rect width="14.5526" height="14.5526" fill="url(#pattern0_252_110)" />
                    <defs>
                        <pattern id="pattern0_252_110" patternContentUnits="objectBoundingBox" width="1" height="1">
                            <use xlink:href="#image0_252_110" transform="scale(0.015625)" />
                        </pattern>
                        <image id="image0_252_110" width="64" height="64" preserveAspectRatio="none"
                            xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAAAXNSR0IArs4c6QAABR9JREFUeAHtW71uFDEQPlEglJ3llAJoEU+BUkWUpKVMQYNEE16BKE0iIQJrC1AqpHRUFGmR8gIhokobXZ/1XoCGNAff/oSNd7xr73rvTrmcdLpd/4xnPo/HM2PfYHDzuUFgqggk7+48jKNwRYmltVjQM3zT5yhcQd1Umel7sN/vgwdKButK0p4SdKwEXShBk4Yv2hynfWSwDhp98+mV/mRzcDsW4YtY0LcGQZuAuKwHLdAEba/M+iSWz/a2EnTuS3CGzrmSwfbcaUUs6XUi6A/D8OUs+qzDWBjT5wS2ogXjpSSd+BTOiVY69tJaK+a7doIqOjA7UiLcTyRtjCU9Ge8OH00+3KPNzcEtfPGMMtQlEW2grRI0sqYvg+2u8lj3VzvLQxUFBxbMnSUifBuLu4+tiWsN0Rc0lKCzxvHA087yUCPh9xX7dCLpewMzI8z05N/s+hodtECzSSvAW2++BAg3rfdE0pZPwXUAcyC2aidA0ol3EKBadTOfCDrqouq6oE3v2dKgIxMQKa8+l0P9mg/3+5x1ExgYMzeW/FYbBQemvk7lddYeBsqJWA+NcyPJg9B1d0j3eYPvPg/CF3jWgiA6+AlmoxfuF4O3/U01S9JYRfRTyfBNWzpFP+NykHRStHH6havJGRkYvK5rXkl6qdM+E/TKiUGtcbpDCN4wOrvNCDZMvr0Pa68EfdEBUIK+ajI5v4I3hu4EsjgFUCbDh33emSumgxLBjs5oHIW7TFPnIvCo007fbQ0iYm5DSDvqqvqFNOOPw+U4uqKuP359ovtFfZffbHtk44hzq3wCEg8cgnBFuzDG9VXR0lPsNFxdl7Lcba5sjZCtka4hk3Pma/YbGfDQINeCSgAF2WrJp1kdZt+fpz2/VoBSpck3qDWGaQKTAcCH5S/xNpVH044AGY0M5Nlbfe2MjB3mvIINnyXtGdnOU9caAN29PgyYiOA5+5W06j18zSU0eIfHdQBU8vZIURk7WFQkkla5XUUrO+wDBMNucMGynSY8mPWPPB3bwbJQCTrUhNU0LDsocXZXLcYH79zYLNjZcVX11AbJSouxjE2UCE45JvSyWASfjURaVoB3fRy8Q9YKSVPoi4xtpbFDgbUG9AAAeOcAYJ2v7JCyqgFIWzvIW2maL63DJk3oQwPAOwcAZK0w2hcAlYEGgwGE1RmbOQB9LYFZAuC2BNIz++oS6GoEZwmAkxHsaxucKQAu2yAY5S4w9BEGT8sGZOeMFa3mHaEcANzi0BwVP65wWROmBYC7K5xdZdEAIO/B0PQAYDJDtcEQ7vNUNIAmvsPhaQDQKhzuIyHy3xHSl1bje6cAqVVCBOvUd0qMm21Oy7iytgFS65RYBoDfpCgnmEtZ2XjaPhvC4IlVUtR3WrwpBqgHIzi1Fbpo1zktDkI+D0YyG2AXEutgIJlSCGb72/lgBAPBGPo+GoMwbErMlCprcW3WZPmdj8YAQp+Ho7az6dIOqo+DW12L8N7WmA76PB53Ec6mrcHrm0AGm/5sG1OIDFTn6bDEtOdn2tDx6M1kEOcFhFrhbU+E2ekvFS70JSngsPDX5ABCup83XIy+thcli9UAEOouTGZGh67nVdkChIW+LF2AgN+63SHXhHJiJbsuH1lcl08vRc/xdfkrICzyHybKQMDVNMUOjDaUNaPVM8Zq7d6WGff5nGaTsn+RLN6fpspAIp+AxIMhs9RqxkELNK2uuZWZmfVzrhWL9cfJJtDhSyzMX2ebwLipnwME/gJkwzPLrDJfPwAAAABJRU5ErkJggg==" />
                    </defs>
                </svg>
                <div class="text-sm text-gray-500 font-normal">Please finalise all edits before translating to avoid
                    unnecessary retranslations.
                </div>
            </div>

        </template>
        <template #content>
            <div class="w-full h-full pb-4">
                <div class="w-full h-full flex flex-col gap-2 items-end justify-center">
                    <button
                        class="flex items-center justify-end bg-[var(--mcolor)] text-white text-sm font-bold rounded-lg p-2"
                        @click="addLanguage.show = true">
                        <div class="text-md font-bold flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                width="25" height="25" viewBox="0 0 20 20" fill="none">
                                <rect width="20" height="20" fill="url(#pattern0_261_129)" />
                                <defs>
                                    <pattern id="pattern0_261_129" patternContentUnits="objectBoundingBox" width="1"
                                        height="1">
                                        <use xlink:href="#image0_261_129" transform="scale(0.00390625)" />
                                    </pattern>
                                    <image id="image0_261_129" width="256" height="256" preserveAspectRatio="none"
                                        xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQAAAAEACAYAAABccqhmAAAKpklEQVR4AezZi3ETSRQFUGsjMZEAkSxEgokENhIgEpyJt3tLLrQqS5oZzfR0v3coDZKl+fQ9T7oWxV8P/hAgkFZAAaQdveAEHh4UgHcBgcQCCiDx8EXPLVDTK4CqYCOQVEABJB282ASqgAKoCjYCSQUUQNLBi51b4DW9AniVcE8goYACSDh0kQm8CiiAVwn3BBIKKICEQxc5t8BpegVwquExgWQCCiDZwMUlcCqgAE41PCaQTEABJBu4uLkFztMrgHMRPxNIJKAAEg1bVALnAgrgXMTPBBIJKIBEwxY1t8Bb6RXAWyqeI5BEQAEkGbSYBN4SUABvqXiOQBIBBZBk0GLmFriUXgFckvE8gQQCCiDBkEUkcElAAVyS8TyBBAIKIMGQRcwtcC29Arim4zUCwQUUQPABi0fgmoACuKbjNQLBBRRA8AGLl1vgVnoFcEvI6wQCCyiAwMMVjcAtAQVwS8jrBAILKIDAwxUtt8CU9ApgipJ9CAQVUABBBysWgSkCCmCKkn0IBBVQAEEHK1ZuganpFcBUKfsRCCigAAIOVSQCUwUUwFQp+xEIKKAAAg5VpNwCc9IrgDla9iUQTEABBBuoOATmCCiAOVr2JRBMQAEEG6g4uQXmplcAc8XsTyCQgAIINExRCMwVUABzxexPIJCAAgg0TFFyCyxJrwCWqDmGQBABBRBkkGIQWCKgAJaoOYZAEAEFEGSQYuQWWJpeASyVcxyBAAIKIMAQRSCwVEABLJVzHIEAAgogwBBFyC1wT3oFcI+eYwkMLqAABh+g5RO4R0AB3KPnWAKDCyiAwQdo+bkF7k2vAO4VHPD4l5eXD2X7UbZ6+13+qo+/DBjFku8UUAB3Ao52eP2wlzX/KNuHstXbY/mrPn4qr30rj90SCSiARMMuH/D6W75+2C+l/nTc59Lrng8moACCDfRGnKcbr9eXP9W/bP0LrLFCBbCG4gDnKL/Zr/3mP03wOGPf0+M8HlBAAQw4tIVLrv/WX3iow6IKKICok70vl7K4z2+YoxXAMKOyUAJ/BNZ6pADWknQeAgMKKIABh2bJBNYSUABrSToPgQEFFMCAQ7Pk3AJrplcAa2o6F4HBBBTAYAOzXAJrCiiANTWdi8BgAgpgsIFZbm6BtdMrgLVFnY/AQAIKYKBhWSqBtQUUwNqizkdgIAEFMNCwLDW3wBbpFcAWqs5JYBABBTDIoCyTwBYCCmALVeckMIiAAhhkUJaZW2Cr9ApgK1nnJTCAgAIYYEiWSGArAQWwlazzEhhAQAEMMCRLzC2wZXoFsKWucxPoXEABdD4gyyOwpYAC2FLXuQl0LqAAOh+Q5eUW2Dq9Atha2PkJdCygADoejqUR2FpAAWwt7PwEOhZQAB0Px9JyC7RIrwBaKLsGgU4FFECng7EsAi0EFEALZdcg0KmAAuh0MJaVW6BVegXQStp1CHQooAA6HIolEWgloABaSbsOgQ4FFECHQ7Gk3AIt0yuAltquRaAzAQXQ2UAsh0BLAQXQUtu1CHQmoAA6G4jl5BZonV4BtBZ3PQIdCSiAjoZhKQRaCyiA1uKuR6AjAQXQ0TAsJbfAHukVwB7qrkmgEwEF0MkgLIPAHgIKYA911yTQiYAC6GQQlpFbYK/0CmAvedcl0IGAAuhgCJZAYC8BBXAm//Ly8li2T9G2EvN92abe3kfLf8zzYSpAlv0UwHHS5Q3ypWwv5cffZfsWcPtUMk291X0jGvwoM/5dti9TIVrst+c1FEDRL2+IH+XuqWxu8QUeS8SnMvNacOVh7lv6AihvhPq1sG653wn50v/3z7x8sf+fOH0BFI6/y+aWUyD97BXAw4Pf/g9p/zzunXzv6yuAvSfg+nsKKIA99Tu59nMn67CM9gLf21+yryv6BvDw8E9fI7GahgLpZ5++AA6HQ/0t4L8AG37qOrnUzzL7n3uupYdrpy+A4xDqbwIlcMRIcPdUPvwfE+S8GVEBFKLyZngu29fy8N1x+1zuo231m06JNelW942Wv+b5WOZcb3XWkyCi76QATiZc3hm1COr2vTwOtZWYv8o29fYrWv5jnvRf+c/fAArgXMTPBBoI9HIJBdDLJKyDwA4CCmAHdJck0IuAAuhlEtZBYAcBBbADukvmFugpvQLoaRrWQqCxgAJoDO5yBHoSUAA9TcNaCDQWUACNwV0ut0Bv6RVAbxOxHgINBRRAQ2yXItCbgALobSLWQ6ChgAJoiO1SuQV6TK8AepyKNRFoJKAAGkG7DIEeBRRAj1OxJgKNBBRAI2iXyS3Qa3oF0OtkrItAAwEF0ADZJQj0KqAAep2MdRFoIKAAGiC7RG6BntMrgJ6nY20ENhZQABsDOz2BngUUQM/TsTYCGwsogI2BnT63QO/pFUDvE7I+AhsKKIANcZ2aQO8CCqD3CVkfgQ0FFMCGuE6dW2CE9ApghClZI4GNBBTARrBOS2AEAQUwwpSskcBGAgpgI1inzS0wSnoFMMqkrJPABgIKYANUpyQwioACGGVS1klgAwEFsAGqU+YWGCm9AhhpWtZKYGUBBbAyqNMRGElAAYw0LWslsLKAAlgZ1OlyC4yWXgGMNjHrJbCigAJYEdOpCIwmoABGm5j1ElhRQAGsiOlUuQVGTK8ARpyaNRNYSUABrATpNARGFFAAI07NmgmsJKAAVoJ0mtwCo6ZXAKNOzroJrCCgAFZAdAoCowoogFEnN3/dz/MPcUR0AQUQfcJ/8s0pgJ9/DvPolsDIryuAkae30doPh8OcsthoFU7bQkABtFDu4BrHD/XnCUt5mrCPXYIIKIAgg5wYo361v/YB/1mK4uvEc9ktgIACCDDEqRHKh/u5bPUDXr8J1DKoh9av+/Xxx/Lax/qEbbrA6HsqgNEnuGD95YP+vWz1A1/uDu8Oh0N9XEtgwdkcMrKAAhh5etZO4E4BBXAnoMMJjCygAEaenrXvKhDh4gogwhRlILBQQAEshHMYgQgCCiDCFGUgsFBAASyEc1hugSjpFUCUScpBYIGAAliA5hACUQQUQJRJykFggYACWIDmkNwCkdIrgEjTlIXATAEFMBPM7gQiCSiASNOUhcBMAQUwE8zuuQWipVcA0SYqD4EZAgpgBpZdCUQTUADRJioPgRkCCmAGll1zC0RMrwAiTlUmAhMFFMBEKLsRiCigACJOVSYCEwUUwEQou+UWiJpeAUSdrFwEJggogAlIdiEQVUABRJ2sXAQmCCiACUh2yS0QOb0CiDxd2QjcEFAAN4C8TCCygAKIPF3ZCNwQUAA3gLycWyB6egUQfcLyEbgioACu4HiJQHQBBRB9wvIRuCKgAK7geCm3QIb0CiDDlGUkcEFAAVyA8TSBDAIKIMOUZSRwQUABXIDxdG6BLOkVQJZJy0ngDQEF8AaKpwhkEVAAWSYtJ4E3BBTAGyieyi2QKb0CyDRtWQmcCSiAMxA/EsgkoAAyTVtWAmcCCuAMxI+5BbKlVwDZJi4vgRMBBXCC4SGBbAIKINvE5SVwIqAATjA8zC2QMb0CyDh1mQkcBRTAEcIdgYwCCiDj1GUmcBRQAEcId7kFsqZXAFknLzeBIqAACoIbgawCCiDr5OUmUAQUQEFwyy2QOb0CyDx92dMLKID0bwEAmQUUQObpy55eQAGkfwvkBsie/l8AAAD//5dUaZMAAAAGSURBVAMAccSJH5QOvPwAAAAASUVORK5CYII=" />
                                </defs>
                            </svg>
                            <div>Add Language</div>
                        </div>
                    </button>
                    <div
                        class="w-full h-full max-h-[calc(100vh-280px)] min-h-[0px] grow-[1] flex flex-col gap-2 overflow-y-auto">

                        <div>
                            <div class="font-medium text-[var(--txtcolor)]">Original</div>
                            <div class="flex items-center gap-2">
                                <div class="bg-gray-100 rounded-lg w-full py-4 px-4">

                                    <div class="flex flex-row items-center gap-4">
                                        <div class="flex items-center gap-3 flex-1">
                                            <div
                                                class="rounded-lg border-2 border-[var(--mcolor)] bg-[#EEE3D3] w-14 h-14 flex items-center justify-center">
                                                <span class="text-[var(--mcolor)] font-bold text-sm">ORG</span>
                                            </div>
                                            <div class="text-lg font-bold text-gray-800">Original</div>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <button @click="downloadOriginalQuestionnaire(' ')"
                                                class="text-sm text-orange-500 hover:text-orange-600 bg-transparent border-none cursor-pointer">Download</button>
                                            <a :href="page.props.engine_url + '?code=' + $page.props.questionnaire.code + '&preview=1&gtq=1&id=id-test'"
                                                target="_blank"
                                                class="flex items-center gap-1 px-3 py-1 border-2 border-[var(--mcolor)] bg-[#F3F4F6] rounded-full text-xs text-[var(--mcolor)] hover:bg-[var(--mcolor)] hover:text-white transition-colors font-normal">
                                                <span>Preview</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6">
                                                    </path>
                                                    <polyline points="15 3 21 3 21 9"></polyline>
                                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-end justify-between">
                            <div class="font-medium text-[var(--txtcolor)] -mb-2">Languages</div>
                            <div class="mr-2">
                                <SortAndSearch v-model:sortValue="sortOption" v-model:searchValue="searchQuery" />
                            </div>
                        </div>
                        <div v-if="AppliedLanguages.length === 0"
                            class="w-full bg-gray-100 rounded-lg py-6 px-4 flex flex-col items-center justify-center text-center text-gray-500 text-sm">
                            <div class="w-full h-auto flex justify-center mb-3">
                                <img :src="EMPTY_ICON"
                                    class="w-[100px] h-auto !max-h-[unset] pointer-events-none select-none" />
                            </div>
                            <div class="mb-1 font-bold">No added languages yet!</div>
                            <div>Use the "Add Language" button above to create your first translation.</div>
                        </div>
                        <div v-else v-for="(language, index) in filteredAndSortedLanguages" :key="index">
                            <div class="flex items-center gap-2">
                                <div class="bg-gray-100 rounded-lg w-full py-4 px-4 group">
                                    <div class="flex flex-row gap-4">
                                        <div class="flex items-center gap-3 flex-1">
                                            <div
                                                class="rounded-lg border-2 border-[var(--mcolor)] bg-[#EEE3D3] w-14 h-14 flex items-center justify-center">
                                                <span class="text-[var(--mcolor)] font-bold text-sm">{{
                                                    language.languageInfo.Code }}</span>
                                            </div>
                                            <div class="flex flex-col">
                                                <div class="text-md font-bold text-gray-800">{{
                                                    language.languageInfo.EnglishName }}</div>
                                                <div class="text-sm text-gray-400">{{ language.languageInfo.NativeName
                                                }}</div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end gap-2">
                                            <a :href="page.props.engine_url + '?code=' + $page.props.questionnaire.code + '&preview=1&gtq=1' + '&langc=' + language.key + '&id=id-test'"
                                                target="_blank"
                                                class="flex items-center gap-1 px-3 py-1 border-2 border-[var(--mcolor)] bg-[#F3F4F6] rounded-full text-xs text-[var(--mcolor)] hover:bg-[var(--mcolor)] hover:text-white transition-all duration-200 font-normal opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto">
                                                <span>Preview</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6">
                                                    </path>
                                                    <polyline points="15 3 21 3 21 9"></polyline>
                                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                                </svg>
                                            </a>
                                            <div class="flex items-center gap-4">
                                                <button @click="confirmDeleteLanguage(language)"
                                                    class="text-xs text-red-600 hover:text-red-700 bg-transparent border-none cursor-pointer font-normal">Remove</button>
                                                <button @click="downloadOriginalQuestionnaire(language.key)"
                                                    class="text-xs text-orange-500 hover:text-orange-600 bg-transparent border-none cursor-pointer font-normal">Download</button>
                                                <button @click="uploadTranslationCSV(language.key)"
                                                    class="text-xs text-orange-500 hover:text-orange-600 bg-transparent border-none cursor-pointer font-normal">Upload</button>
                                                <button
                                                    @click="aiTranslate(language.key, language.languageInfo.EnglishName, language.languageInfo.RTL)"
                                                    :disabled="translatingLanguages[language.key]"
                                                    class="text-xs text-[#422AD5] hover:text-violet-600 bg-transparent border-none cursor-pointer font-normal disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <span
                                                        v-if="translatingLanguages[language.key]">Translating...</span>
                                                    <span v-else>AI Translate</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

    </RightSideBar>
    <teleport to="body">
        <SSRModal :show="addLanguage.show" :icon="'edit'"
            @close="addLanguage.show = false; manualTranslationStep = 0">
            <template #header>
                <div class="text-xl font-bold text-center">Add Language</div>
            </template>
            <template #content>
                <div class="w-full h-full">
                    <div class="flex flex-col">
                        <div class="text-sm text-[#323232] font-bold">Select Language</div>
                        <SingleSelect class="w-full mb-4" :options="languageListComputed"
                            :modelValue="formData.language" @update:modelValue="formData.language = $event"
                            placeholder="Select Language" :searchable="true" />

                        <div class="flex items-stretch gap-1">
                            <!-- Translate Using AI Section -->
                            <div
                                class="flex-1 flex items-left flex-col gap-4 p-4 bg-gray-100 border border-gray-200 rounded-md">
                                <h3 class="text-sm font-bold text-gray-700">Translate Using AI</h3>
                                <span class="text-xs text-gray-500">Automatically translate your questionnaire into the
                                    selected language using AI. Please note that the AI translation may be incorrect or
                                    may require manual adjustments.</span>
                                <button
                                    class="w-full bg-[var(--mcolor)] text-white px-4 py-3 rounded-md flex items-center justify-center gap-2 font-semibold hover:bg-[var(--mcolor2)] transition-colors"
                                    @click="aiTranslate()">
                                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                        width="28" height="28" viewBox="0 0 25 25" fill="none">
                                        <rect width="25" height="25" fill="url(#pattern0_179_206)" />
                                        <defs>
                                            <pattern id="pattern0_179_206" patternContentUnits="objectBoundingBox"
                                                width="1" height="1">
                                                <use xlink:href="#image0_179_206" transform="scale(0.015625)" />
                                            </pattern>
                                            <image id="image0_179_206" width="64" height="64" preserveAspectRatio="none"
                                                xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAAAXNSR0IArs4c6QAAA3tJREFUeAHtmoFxpDAMRVPKlZJSrpOUklJSSkrxoT1rxxH6QkbCwMbM7BgcQPLTl+x19u1tHpPASxAopbyXUv68xGB6B1FK+Vg+dHz/Ogg04Dp4bv72AgzdX0r5VD7voZd2PLyMmqPPAL46Ho/fylZFOyQKSvTZjSH2H/TYomiHOFCVJ0w/LsfVAs16KeVwAGQD2OZuSs3jZwW2JtpDASy2ZN4L88/Lr10Q6pxKhD2fp7XmhOh7nu0CVXOeBtVzUDr0FWUjt3oMu+71luEEnwicD0SCMdfg6aYtAI5cd9uqN26nxcUAkISzD1sJFwNABS8TwvZC6UoAKEVq8aPCGgERrgGfWr4Cba6qO8pl7Z2or85OeyDYkpcGgQJOB8B+LtC9abFd8PilbXt1AORrhQAE+OjezvV20O35HQBUCBRh7fhux9N9fiMAtBeg1YSP7kG3D9wFAPm8hF9+L9gvfYZwJwAVQquC+DfAGwLgWUGdqTiw7hYA0IpNuM/t1MgbJ4D/G53h6Hpe4A1sXQ5rewzxnJdOXFEBhk+rZbccT/e1YcwT1K57PM7V6KP3xqc96cTVtsSWkcu5XsLITwMJBV1LT+p1qizBaq81HVv5ocF5+lsvmvM0ABvyZ5O0EDpHBeyBaDMB0EaI50iz6Qn88x7gWYozzuizC/nF8DlK44StizYLACp+7dq/NT0+DVrrzXkWAG2g1Ec/htCO8cVQ84L2/wzRuP5kyP8xSDAzjC+G4F9gYSmixRjTW8Cj9AjDZxuntUb0f3zdBSo4pxhm0vJGF6kkdU1wVI5bwEBkVxudQ4rhaACG/NUKj2ClqeAEAGjlp+0FUB/aGs8phiMBGNEHbpjdOcUQmMihKwrBYgtNbcCNze7wdEz77tpxFABt5afZ9/apdUNwty9BkfkxH9tv8P01Wf4MKL4yBEUm/mLBxZjTeTB725haF6soL+PyqhCM6HcpDag1Vgyrcyg3CU640BiQu6JnqCjmo/FikiXNwwQCzdPcD3+lASK3WvmJrFldHrYy3FABQfAcqpwN+e9KMQQzrNQECAiAuvJbhdfZsUQC1ayudFLNVYmherClghUAI/qre1WHQCdQQawYsq3q9J6frK0GdVS0jJoVK4YMgdqqBpIbFUGPKjQA2nPdxa/1q/FNU+SuuiLfP68ngUlgEpgEJoFJ4DUI/AO7eXDs3DH7CgAAAABJRU5ErkJggg==" />
                                        </defs>
                                    </svg>
                                    <span>AI Translate</span>
                                </button>

                            </div>

                            <!-- OR Separator -->
                            <div class="flex items-center justify-center">
                                <span class="text-sm font-semibold text-gray-500">OR</span>
                            </div>

                            <!-- Translate Manually Section -->
                            <div
                                class="flex-1 flex items-left justify-center flex-col gap-4 p-2 bg-gray-100 border border-gray-200 rounded-md">
                                <h3 class="text-sm font-bold text-gray-700">Translate Manually</h3>
                                <span class="text-xs text-gray-500">Follow the steps below:</span>

                                <!-- Steps with indicators -->
                                <div class="flex flex-col w-full">
                                    <!-- Step 1 -->
                                    <div class="flex items-start gap-2">
                                        <!-- Vertical line and circle indicator -->
                                        <div class="flex flex-col items-center pt-2.5">
                                            <div :class="[
                                                'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold',
                                                manualTranslationStep >= 1
                                                    ? 'bg-[var(--mcolor)] text-white'
                                                    : 'border-[var(--mcolor)] border-2 text-[var(--mcolor)]'
                                            ]">1</div>
                                            <div class="w-0.5 h-7 bg-[var(--mcolor)]"></div>
                                        </div>
                                        <!-- Step content -->
                                        <div class="flex-1">
                                            <button
                                                class="w-full bg-white border border-[var(--mcolor)] gap-2 px-1 py-1 rounded-md flex items-center justify-center font-semibold hover:bg-[rgba(209,129,11,0.05)] transition-colors"
                                                @click="downloadOriginalQuestionnaire(null)">
                                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="ml-1 text-[var(--mcolor)]">
                                                    <path d="M12 15V3M12 15L7 10M12 15L17 10" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                    <path
                                                        d="M2 17L2 19C2 20.1046 2.89543 21 4 21L20 21C21.1046 21 22 20.1046 22 19L22 17"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                                <span class="text-left text-[10px]"><span
                                                        class="text-[var(--mcolor)]">Download Original Questionnaire
                                                        (.csv)</span></span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Step 2 -->
                                    <div class="flex items-start gap-2">
                                        <!-- Vertical line and circle indicator -->
                                        <div class="flex flex-col items-center">
                                            <div :class="[
                                                'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold',
                                                manualTranslationStep >= 2
                                                    ? 'bg-[var(--mcolor)] text-white'
                                                    : 'border-[var(--mcolor)] border-2 text-[var(--mcolor)]'
                                            ]">2</div>
                                            <div class="w-0.5 h-10 bg-[var(--mcolor)]"></div>
                                        </div>
                                        <!-- Step content -->
                                        <div class="flex">
                                            <span class="text-xs text-gray-500">Open the file and translate all the
                                                survey text into the selected language.</span>
                                        </div>
                                    </div>

                                    <!-- Step 3 -->
                                    <div class="flex items-start gap-2">
                                        <!-- Vertical line and circle indicator -->
                                        <div class="flex flex-col items-center">
                                            <div :class="[
                                                'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold',
                                                manualTranslationStep >= 3
                                                    ? 'bg-[var(--mcolor)] text-white'
                                                    : 'border-[var(--mcolor)] border-2 text-[var(--mcolor)]'
                                            ]">3</div>
                                        </div>
                                        <!-- Step content -->
                                        <div class="flex-1">
                                            <button
                                                class="w-full bg-[var(--mcolor)] text-white gap-1 px-1 py-1 rounded-md flex items-center justify-center font-semibold hover:bg-[var(--mcolor2)] transition-colors"
                                                @click="uploadTranslationCSV()">
                                                <svg class="-ml-1" xmlns="http://www.w3.org/2000/svg"
                                                    xmlns:xlink="http://www.w3.org/1999/xlink" width="28" height="28"
                                                    viewBox="0 0 20 20" fill="none">
                                                    <rect width="20" height="20" fill="url(#pattern0_179_211)" />
                                                    <defs>
                                                        <pattern id="pattern0_179_211"
                                                            patternContentUnits="objectBoundingBox" width="1"
                                                            height="1">
                                                            <use xlink:href="#image0_179_211"
                                                                transform="scale(0.015625)" />
                                                        </pattern>
                                                        <image id="image0_179_211" width="64" height="64"
                                                            preserveAspectRatio="none"
                                                            xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAAAXNSR0IArs4c6QAAAgxJREFUeAHtmouRwyAMRFNKSrlSrpOUklJSSkrRRTl2rHD4p7FBullmMnGwIdqHQOZzuTCRAAmQAAmQAAmQgIOAiHyLyEPOSU8R+XKY1afIicItzpgQtGWslSKiht4P/Gh9SPEgvCy7wTrtBkf7XAFp/uINOE53sAYeLV7rs/UbCnE8wRrYEYCyiAFhIIAYEAYDGA8hAICxEIIAGAchEIAxEIIB6A8hIACFcD8jJDfr7ABAX7V1orXlo+I1/R8ATeozmUU8AdAD2AU4BnAQnBknj88+OwzusZhRYCKQrwuUhdXnnhavn530JxsEq1VlN4SUACrx0OCCgMJpXoRmxEPHbggomALAinho2QUBhcIDqMTr6q5upCDpPoPmIW2GgAKhATTEY7oL+3Xae/VAQAVhAbTEaygrc33Y/95d8kBABSEBVG7+sanRAlDA1J7wqGO//R0dAMR8iJ/zAAgznrA6FoQGUIQqhCvE4XvOA8z9P2Vwz36HB2CNtddrAOyzS9cEMBHINRmiB/wueaP93IcsUEHIMLjSd/XlB4kAlmAt3QPBjB6g4RE7P5tCXgtEWgAtMZ48ApgI5AqDntZulZn0J1sTbInx5I0CYA9K3jyGH1GmepnqZ0eHo7Jbjt3Wh7T7niR9uZ71AuOJQy77DYDWdQuEuiV6EtB1hn6ub8XzmgRIgARIgARIID2BHz351l/XWPU4AAAAAElFTkSuQmCC" />
                                                    </defs>
                                                </svg>
                                                <span class="text-left text-[8px]">Upload Translated CSV File</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </template>
            <template #footer>
                <button
                    class="text-red-600 font-bold text-sm hover:text-red-700 hover:bg-red-50 px-3 py-1.5 rounded-md transition-colors"
                    :theme="'ssrCancel'" color="ssrGray"
                    @click="addLanguage.show = false; manualTranslationStep.value = 0">
                    Cancel
                </button>
            </template>
        </SSRModal>
    </teleport>
    <teleport to="body">
        <SSRModal
            :show="translationPreviewModal.show"
            :icon="'edit'"
            :maxWidth="'full'"
            @close="closeTranslationPreview"
        >
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <div class="flex flex-col">
                        <div class="text-lg font-bold">
                            Review Translation — {{
                                translationPreviewModal.language?.languageInfo?.EnglishName ||
                                translationPreviewModal.language?.key ||
                                ''
                            }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Compare the original questionnaire with the translated version. Click
                            <span class="font-semibold">Edit Translation</span> to make detailed changes.
                        </div>
                    </div>
                    <button
                        type="button"
                        class="flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 text-gray-500 hover:text-gray-800 transition-colors"
                        @click="closeTranslationPreview"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </template>
            <template #content>
                <div class="w-full">
                    <!-- Header row to identify Original and Translated -->
                    <div
                        class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] gap-4 md:gap-6 items-center mb-4 pb-4 border-b border-gray-200"
                    >
                        <div class="text-center md:text-left">
                            <div class="text-sm font-semibold text-gray-700 mb-1">Original Questionnaire</div>
                            <div class="text-xs text-gray-500">{{ page.props.questionnaire?.name }} · English</div>
                        </div>
                        <div class="hidden md:block"></div>
                        <div class="text-center md:text-left">
                            <div class="text-sm font-semibold text-violet-700 mb-2">
                                {{
                                    translationPreviewModal.language?.languageInfo?.EnglishName
                                        ? `${translationPreviewModal.language.languageInfo.EnglishName} Translation`
                                        : 'Translated Questionnaire'
                                }}
                            </div>
                            <div class="flex items-center gap-3 justify-center md:justify-end">
                                <SingleSelect
                                    v-model="currentPreviewLanguageValue"
                                    :options="previewLanguageOptions"
                                    :searchable="true"
                                    class="w-[180px]"
                                    placeholder="Select language"
                                />
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold bg-violet-700 text-white border border-violet-700 shadow-md hover:bg-violet-800 hover:shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-violet-700 disabled:hover:shadow-md whitespace-nowrap flex-shrink-0"
                                    @click="proceedToEditTranslationFromPreview"
                                    :disabled="!currentPreviewLanguageValue"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="w-4 h-4 flex-shrink-0"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M12 20h9"></path>
                                        <path
                                            d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"
                                        ></path>
                                    </svg>
                                    <span class="whitespace-nowrap">Edit Translation</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-for="(question, qIndex) in previewQuestions"
                        :key="`preview-row-${qIndex}`"
                        class="mb-6"
                    >
                        <div
                            class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] gap-4 md:gap-6 items-start"
                        >
                        <!-- Original card -->
                        <div
                            class="bg-white rounded-lg overflow-hidden color-[var(--txtcolor)] min-h-[300px] pb-[20px] relative border border-[#e5e7eb]"
                        >
                            <div class="relative qCont flex flex-col flex-grow bg-gray-0">
                                <div class="relative grid">
                                    <div class="w-full pt-3">
                                        <div class="flex justify-items-center items-center">
                                            <div
                                                class="relative grid grid-cols-[auto_1fr] gap-2 w-auto max-w-[calc(100%-150px)]"
                                            >
                                                <div class="flex gap-1 rounded-[0px_0px_15px_0px] px-8">
                                                    <div
                                                        class="mt-2 mr-2 font-bold text-xs text-[var(--txtcolor)] rounded-full h-8 w-8 flex justify-center"
                                                    >
                                                        <div v-html="qt_icons[question.question_type_id]"></div>
                                                    </div>

                                                    <div class="flex flex-row items-center gap-2">
                                                        <div
                                                            class="text-secondary text-xs text-white font-medium px-2 py-0.5 rounded-full bg-[#ee920c] bg-opacity-100"
                                                        >
                                                            {{ getQNumber(question) }}
                                                        </div>
                                                        <h3
                                                            v-if="getChartTitle(question, ' ')"
                                                            class="truncate font-medium grow-[1] text-[#ee920c]"
                                                        >
                                                            {{ getChartTitle(question, ' ') }}
                                                        </h3>

                                                        <div
                                                            class="text-xs font-medium px-2 py-0.5 rounded-full flex items-center bg-[#e0f2fe] text-[#0369a1] border-[#bae6fd] border-[1px]"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1"
                                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                                            </svg>
                                                            {{ getVariableName(question) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="pt-2 mx-8">
                                            <div
                                                class="mx-8 text-sm text-[var(--txtcolor)] qImage w-[95%] ml-11"
                                                dir="ltr"
                                                v-html="getQuestionContentForLang(question, ' ')"
                                            >
                                            </div>

                                            <!-- Preview of answer options for the original language -->
                                            <!-- Image Selection (question_type_id == 3) -->
                                            <div
                                                v-if="question.question_type_id == 3 && question.choices && question.choices.length"
                                                class="mt-4 ml-11"
                                                dir="ltr"
                                            >
                                                <div class="grid gap-1" :class="getImageGridCols(question)">
                                                    <div
                                                        v-for="(choice, cIndex) in question.choices"
                                                        :key="`orig-img-q-${qIndex}-c-${cIndex}`"
                                                        class="flex flex-col items-center"
                                                    >
                                                        <div
                                                            class="flex rounded-xl border border-slate-200 w-20 h-20 bg-contain bg-no-repeat bg-center"
                                                            :style="{ backgroundImage: `url(${choice.src})` }"
                                                        ></div>
                                                        <p
                                                            v-if="!choice?.hide_name"
                                                            class="MChoice w-20 mt-1 text-center text-xs"
                                                            v-html="getChoiceContentForLang(choice, ' ')"
                                                        ></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Text Selection (question_type_id == 2) -->
                                            <ul
                                                v-else-if="question.question_type_id == 2 && question.choices && question.choices.length"
                                                class="mt-4 ml-11 space-y-2 text-sm text-[var(--txtcolor)]"
                                            >
                                                <li
                                                    v-for="(choice, cIndex) in question.choices"
                                                    :key="`orig-q-${qIndex}-c-${cIndex}`"
                                                    class="flex items-center gap-2"
                                                >
                                                    <span
                                                        class="inline-flex w-3.5 h-3.5 rounded border border-gray-300 bg-white"
                                                    ></span>
                                                    <span
                                                        class="MChoice"
                                                        v-html="getChoiceContentForLang(choice, ' ')"
                                                    ></span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Center arrow -->
                        <div class="hidden md:flex items-center justify-center h-full">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-violet-600">
                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                <g id="SVGRepo_iconCarrier">
                                    <path d="M5 12H19M19 12L13 6M19 12L13 18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                </g>
                            </svg>
                        </div>

                        <!-- Translated card -->
                        <div
                            class="bg-white rounded-lg overflow-hidden color-[var(--txtcolor)] min-h-[300px] pb-[20px] relative border border-[#e5e7eb]"
                            :dir="previewIsRTL ? 'rtl' : 'ltr'"
                        >
                            <div class="relative qCont flex flex-col flex-grow bg-gray-0">
                                <div class="relative grid">
                                    <div class="w-full pt-3" dir="ltr">
                                        <div class="flex justify-items-center items-center">
                                            <div
                                                class="relative grid grid-cols-[auto_1fr] gap-2 w-auto max-w-[calc(100%-150px)]"
                                            >
                                                <div class="flex gap-1 rounded-[0px_0px_15px_0px] px-8">
                                                    <div
                                                        class="mt-2 mr-2 font-bold text-xs text-[var(--txtcolor)] rounded-full h-8 w-8 flex justify-center"
                                                    >
                                                        <div v-html="qt_icons[question.question_type_id]"></div>
                                                    </div>

                                                    <div class="flex flex-row items-center gap-2">
                                                        <div
                                                            class="text-secondary text-xs text-white font-medium px-2 py-0.5 rounded-full bg-violet-600 bg-opacity-100"
                                                        >
                                                            {{ getQNumber(question) }}
                                                        </div>
                                                        <h3
                                                            v-if="getChartTitle(question, previewLanguageCode)"
                                                            class="truncate font-medium grow-[1] text-violet-700"
                                                        >
                                                            {{ getChartTitle(question, previewLanguageCode) }}
                                                        </h3>

                                                        <div
                                                            class="text-xs font-medium px-2 py-0.5 rounded-full flex items-center bg-[#e0f2fe] text-[#0369a1] border-[#bae6fd] border-[1px]"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1"
                                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                                            </svg>
                                                            {{ getVariableName(question) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="pt-2 mx-8">
                                            <div
                                                class="mx-8 text-sm text-[var(--txtcolor)] qImage w-[95%]"
                                                :class="previewIsRTL ? 'mr-11 text-right' : 'ml-11 text-left'"
                                                :dir="previewIsRTL ? 'rtl' : 'ltr'"
                                                v-html="getQuestionContentForLang(question, previewLanguageCode)"
                                            >
                                            </div>
                                            <!-- Preview of answer options for the translated language -->
                                            <!-- Image Selection (question_type_id == 3) -->
                                            <div
                                                v-if="question.question_type_id == 3 && question.choices && question.choices.length"
                                                class="mt-4"
                                                :class="previewIsRTL ? 'mr-11' : 'ml-11'"
                                                :dir="previewIsRTL ? 'rtl' : 'ltr'"
                                            >
                                                <div class="grid gap-1" :class="getImageGridCols(question)">
                                                    <div
                                                        v-for="(choice, cIndex) in question.choices"
                                                        :key="`tr-img-q-${qIndex}-c-${cIndex}`"
                                                        class="flex flex-col items-center"
                                                    >
                                                        <div
                                                            class="flex rounded-xl border border-slate-200 w-20 h-20 bg-contain bg-no-repeat bg-center"
                                                            :style="{ backgroundImage: `url(${choice.src})` }"
                                                        ></div>
                                                        <p
                                                            v-if="!choice?.hide_name"
                                                            class="MChoice w-20 mt-1 text-center text-xs"
                                                            v-html="getChoiceContentForLang(choice, previewLanguageCode)"
                                                        ></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Text Selection (question_type_id == 2) -->
                                            <ul
                                                v-else-if="question.question_type_id == 2 && question.choices && question.choices.length"
                                                class="mt-4 space-y-2 text-sm text-[var(--txtcolor)]"
                                                :class="previewIsRTL ? 'mr-11' : 'ml-11'"
                                                :dir="previewIsRTL ? 'rtl' : 'ltr'"
                                            >
                                                <li
                                                    v-for="(choice, cIndex) in question.choices"
                                                    :key="`tr-q-${qIndex}-c-${cIndex}`"
                                                    class="flex items-center gap-2"
                                                    :class="previewIsRTL ? 'flex-row-reverse justify-end' : ''"
                                                >
                                                    <span
                                                        class="inline-flex w-3.5 h-3.5 rounded border border-gray-300 bg-white flex-shrink-0"
                                                    ></span>
                                                    <span
                                                        class="MChoice"
                                                        :class="previewIsRTL ? 'text-right' : 'text-left'"
                                                        v-html="getChoiceContentForLang(choice, previewLanguageCode)"
                                                    ></span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </template>
            
        </SSRModal>
    </teleport>
    <teleport to="body">
        <DeleteLanguageModal :modal="deleteLanguageModal" @close="deleteLanguageModal.delete = false"
            @deleted="handleLanguageDeleted" />
    </teleport>
    <teleport to="body">
        <SSRModal :show="editTranslationModal.show" :icon="'edit'" :maxWidth="'full'" @close="cancelEditTranslationModal">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <div class="text-lg font-bold">
                        Edit Translation — {{ editTranslationModal.language?.languageInfo?.EnglishName ||
                            editTranslationModal.language?.key || '' }}
                    </div>
                    <button
                        type="button"
                        class="flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500 hover:text-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="editTranslationModal.closing ? 'px-3 py-2' : 'w-8 h-8'"
                        @click="cancelEditTranslationModal"
                        :disabled="editTranslationModal.closing"
                    >
                        <template v-if="editTranslationModal.closing">
                            <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                        </template>
                        <template v-else>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </template>
                    </button>
                </div>
            </template>
            <template #content>
                <div class="w-full max-h-[85vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Original -->
                        <div class="border rounded-lg bg-white">
                            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-lg sticky top-0 z-10">
                                <div class="text-sm font-bold text-gray-700">Original Questionnaire</div>
                                <div class="text-xs text-gray-500">{{ page.props.questionnaire?.name }}</div>
                            </div>
                            <div ref="originalScrollContainer" class="p-4">
                                <template v-for="row in editTranslationRows" :key="row.key">
                                    <template v-if="row.isGroupHeader">
                                        <div class="mt-6 first:mt-0 mb-4 pb-3 border-b-2 border-gray-300 min-h-[48px] flex items-end">
                                            <div class="text-sm font-bold text-gray-800">{{ row.groupTitle }}</div>
                                        </div>
                                    </template>
                                    <div v-else 
                                        :ref="el => { 
                                            if (el) {
                                                originalRowRefs[row.key] = el;
                                            } else {
                                                delete originalRowRefs[row.key];
                                            }
                                        }"
                                        class="mt-2 rounded-lg px-3 py-2 transition-all duration-200 flex flex-col"
                                        :class="[
                                            row.indent ? 'ml-4' : '',
                                            editTranslationModal.activeRowKey === row.key
                                                ? 'bg-blue-50 border-2 border-blue-400 shadow-md'
                                                : 'bg-gray-100'
                                        ]">
                                        <div class="text-[11px] font-semibold mb-1 min-h-[16px] flex items-center"
                                            :class="editTranslationModal.activeRowKey === row.key ? 'text-blue-700' : 'text-gray-500'">
                                            {{ row.label }}
                                        </div>
                                        <div class="flex-shrink-0">
                                            <TE2
                                                class="w-full"
                                                :height="'48px'"
                                                :readonly="true"
                                                :disablePasteColor="true"
                                                :placeholder="''"
                                                :modelValue="row.original || ''"
                                            />
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Translated (editable) -->
                        <div class="border rounded-lg bg-white">
                            <div class="px-4 py-3 border-b bg-gray-50 rounded-t-lg sticky top-0 z-10">
                                <div class="text-sm font-bold text-gray-700">
                                    Translation — {{ editTranslationModal.language?.languageInfo?.EnglishName ||
                                        editTranslationModal.language?.key || '' }}
                                </div>
                                <div class="text-xs text-gray-500">Edit the translated text, then click “Save Changes” to
                                    save.</div>
                            </div>
                            <div class="p-4"
                                :dir="editTranslationModal.rtl ? 'rtl' : 'ltr'">
                                <template v-for="row in editTranslationRows" :key="row.key">
                                    <template v-if="row.isGroupHeader">
                                        <div class="mt-6 first:mt-0 mb-4 pb-3 border-b-2 border-gray-300 min-h-[48px] flex items-end">
                                            <div class="text-sm font-bold text-gray-800">{{ row.groupTitle }}</div>
                                        </div>
                                    </template>
                                    <div v-else 
                                        class="mt-2 rounded-lg px-3 py-2 transition-all duration-300 flex flex-col"
                                        :class="[
                                            row.indent ? 'mr-4 md:mr-0 md:ml-4' : '',
                                            row.hasChanged 
                                                ? 'bg-yellow-50 border-2 border-yellow-400 shadow-sm' 
                                                : 'bg-gray-100'
                                        ]">
                                        <div class="text-[11px] font-semibold mb-1 min-h-[16px] flex items-center gap-2"
                                            :class="row.hasChanged ? 'text-yellow-700' : 'text-gray-500'">
                                            <span>{{ row.label }}</span>
                                            <span v-if="row.hasChanged" 
                                                class="px-1.5 py-0.5 text-[10px] bg-yellow-400 text-yellow-900 rounded font-bold animate-pulse">
                                                UPDATED
                                            </span>
                                        </div>
                                        <div class="flex-shrink-0" @focusin="editTranslationModal.activeRowKey = row.key" 
                                             @focusout="handleTranslationBlur"
                                             @click="editTranslationModal.activeRowKey = row.key">
                                            <TE2 class="w-full" :height="'48px'" :disablePasteColor="true"
                                                :placeholder="row.original ? 'Enter translation…' : ''"
                                                :modelValue="row.translated"
                                                @update:modelValue="updateEditTranslation(row.csvIndex, $event)" />
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="w-full flex items-center justify-between gap-3">
                    <!-- Left side intentionally empty now that close is in header -->
                    <div></div>

                    <div class="flex items-center gap-2">
                        <a v-if="editTranslationModal.language?.key"
                            :href="page.props.engine_url + '?code=' + $page.props.questionnaire.code + '&preview=1&gtq=1' + '&langc=' + editTranslationModal.language.key + '&id=id-test'"
                            target="_blank"
                            class="flex items-center gap-1 px-3 py-1 border-2 border-[var(--mcolor)] bg-[#F3F4F6] rounded-full text-xs text-[var(--mcolor)] hover:bg-[var(--mcolor)] hover:text-white transition-colors font-normal">
                            <span>Preview</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                        </a>

                        <button
                            class="text-sm font-bold px-4 py-2 rounded-md border border-[var(--mcolor)] text-[var(--mcolor)] hover:bg-[rgba(209,129,11,0.08)] transition-colors"
                            type="button"
                            :disabled="!editTranslationModal.language?.key || editTranslationForm.processing || editTranslationModal.loading"
                            @click="retranslateFromModal"
                            title="Re-run AI translation for this language (may overwrite existing translations)."
                        >
                            Re-translate
                        </button>
                        <button
                            class="bg-[var(--mcolor)] text-white text-sm font-bold px-5 py-2 rounded-md hover:bg-[var(--mcolor2)] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            type="button"
                            :disabled="!editTranslationModal.language?.key || editTranslationForm.processing || editTranslationModal.loading"
                            @click="saveEditedTranslation"
                        >
                            <span v-if="editTranslationForm.processing">Saving…</span>
                            <span v-else>Save Changes</span>
                        </button>
                    </div>
                </div>

            </template>
        </SSRModal>
    </teleport>
    <teleport to="body">
        <div v-if="isTranslating"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]">
            <div class="flex flex-col items-center gap-4">
                <AILoading text="Translating" />
                <button @click="cancelTranslation"
                    class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-md transition-colors duration-200 shadow-lg">
                    Cancel Translation
                </button>
            </div>
        </div>
    </teleport>
    <teleport to="body">
        <div v-if="editTranslationModal.loading"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]">
            <AILoading text="Loading translation" />
        </div>
    </teleport>
</template>

<script setup>
import { ref, defineExpose, computed, nextTick, watch } from 'vue';
import RightSideBar from "@/S4/RightSideBar.vue";
import SSRModal from "@/S4/SSRModal.vue";
import SingleSelect from "@/S4/SingleSelect.vue";
import AILoading from "@/S4/AI/AILoading.vue";
import SortAndSearch from "@/S4/SortAndSearch.vue";
import TE2 from "@/S4/TE2.vue";
import DeleteLanguageModal from "@/Pages/Questions/modals/DeleteLanguage.vue";
import EMPTY_ICON from "@/../assets/icons/emptyLang.svg";
import icons from "@/qt_icons.js";
const qt_icons = icons.qt_icons;
import { useForm, router } from "@inertiajs/vue3";
import { usePage } from "@inertiajs/vue3";
import LanguageJson from '@/../json/language.json';
import axios from 'axios';
import { useToast } from "vue-toastification";
import { faSpinner } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';



const page = usePage();
const toast = useToast({
    timeout: 3000
});

// console.log("Questionnaire", page.props.questionnaire);
const formDownloadCSV = useForm({
    questionnaire_code: page.props.questionnaire.code
})

const Options = ref({
    show: false
})

const deleteLanguageModal = ref({
    delete: false,
    data: null,
});

const addLanguage = ref({
    show: false
})

const editTranslationModal = ref({
    show: false,
    loading: false,
    language: null,
    rtl: false,
    csvData: [],
    previousTranslations: null, // Snapshot of translations before re-translation for change detection
    activeRowKey: null, // Track which row is currently being edited for highlighting
    closing: false, // Track if modal is closing
});

// Refs for scroll synchronization
const originalScrollContainer = ref(null);
const originalRowRefs = ref({});

const translationPreviewModal = ref({
    show: false,
    language: null,
    closing: false,
});

const faIcon = {
    spinner: faSpinner
};

const previewQuestions = computed(() => {
    return page.props.questionnaire?.questions || [];
});

const previewLanguageCode = computed(() => {
    const lang = translationPreviewModal.value.language;
    return (
        lang?.key ||
        lang?.languageInfo?.Code ||
        ' '
    );
});

const previewIsRTL = computed(() => {
    return !!translationPreviewModal.value.language?.languageInfo?.RTL;
});

const previewLanguageOptions = computed(() => {
    return AppliedLanguages.value.map(lang => ({
        value: lang.key,
        text: `${lang.languageInfo.EnglishName} (${lang.languageInfo.Code})`
    }));
});

const currentPreviewLanguageValue = computed({
    get: () => {
        return translationPreviewModal.value.language?.key || null;
    },
    set: (value) => {
        if (!value) return;
        const selectedLang = AppliedLanguages.value.find(lang => lang.key === value);
        if (selectedLang) {
            translationPreviewModal.value.language = selectedLang;
        }
    }
});

// Helper function to update URL when switching languages (similar to switchLanguage in Index.vue)
const updateLanguageUrl = (languageCode) => {
    try {
        // Get the base route
        const base = route(page.props.pageSTR, {
            questionnaire_code: page.props.questionnaire.code
        });

        // Build the new URL with langc parameter
        const url = new URL(base, window.location.origin);
        if (languageCode && languageCode !== ' ' && languageCode.trim() !== '') {
            url.searchParams.set('langc', languageCode);
        } else {
            // Remove langc parameter for original language
            url.searchParams.delete('langc');
        }

        // Preserve split screen state in the URL if it exists
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('split') === '1') {
            url.searchParams.set('split', '1');
        }

        // Update URL without page reload
        window.history.pushState({}, '', url.pathname + url.search);
    } catch (e) {
        console.error('Failed to update language URL', e);
    }
};

const getQuestionContentForLang = (question, lang) => {
    if (!question) return '';
    const isOriginal =
        !lang ||
        lang === ' ' ||
        (typeof lang === 'string' && lang.trim() === '');

    if (isOriginal) {
        return question?.question || '';
    }

    const translated = question?.setup?.language?.[lang]?.question;
    return translated && translated.length ? translated : (question?.question || '');
};

const getChoiceContentForLang = (choice, lang) => {
    if (!choice) return '';
    const isOriginal =
        !lang ||
        lang === ' ' ||
        (typeof lang === 'string' && lang.trim() === '');

    if (isOriginal) {
        return choice?.name || '';
    }

    const translated = choice?.language?.[lang]?.name;
    return translated && translated.length ? translated : (choice?.name || '');
};

const getImageGridCols = (question) => {
    if (!question?.setup?.imageSelection?.grid) return 'grid-cols-5';
    return `grid-cols-${question.setup.imageSelection.grid}`;
};

const getChartTitle = (question, lang = null) => {
    if (!question) return '';
    const isOriginal =
        !lang ||
        lang === ' ' ||
        (typeof lang === 'string' && lang.trim() === '');

    if (isOriginal) {
        return question?.setup?.chartTitle || '';
    }

    const translated = question?.setup?.language?.[lang]?.chartTitle;
    return translated && translated.length ? translated : (question?.setup?.chartTitle || '');
};

const getQNumber = (question) => {
    if (!question || !page.props.questionnaire?.setup) return '';
    const variableStart = parseInt(page.props.questionnaire.setup.variable_start || 1);
    const order = parseInt(question.order || 1);
    return 'Q' + (variableStart + order - 1);
};

const getVariableName = (question) => {
    if (!question || !page.props.questionnaire?.setup) return '';
    const variable = page.props.questionnaire.setup.variable || '';
    const variableStart = parseInt(page.props.questionnaire.setup.variable_start || 1);
    const order = parseInt(question.order || 1);
    return variable + (variableStart + order - 1);
};

// Helper to get question title from Q number (e.g., "Q1" -> "Brand usage")
const getQuestionTitleByQNumber = (qNumber) => {
    if (!qNumber || !page.props.questionnaire?.questions || !page.props.questionnaire?.setup) return '';
    
    const variableStart = parseInt(page.props.questionnaire.setup.variable_start || 1);
    const qMatch = qNumber.match(/^Q(\d+)/i);
    if (!qMatch) return '';
    
    const qNum = parseInt(qMatch[1]);
    const questionOrder = qNum - variableStart + 1;
    
    const question = page.props.questionnaire.questions.find(q => parseInt(q.order) === questionOrder);
    if (!question) return '';
    
    // Try to get chartTitle from setup, then title, then question text
    let setup = question.setup || {};
    // Handle case where setup might be a JSON string
    if (typeof setup === 'string') {
        try {
            setup = JSON.parse(setup);
        } catch (e) {
            setup = {};
        }
    }
    const chartTitle = setup?.chartTitle || '';
    const title = question.title || '';
    const questionText = question.question || '';
    
    // Use chartTitle if available, otherwise title, otherwise first part of question text
    return chartTitle || title || (questionText ? questionText.substring(0, 50) : '');
};

const editTranslationForm = useForm({
    language: null,
    languageInfo: {},
    csvData: [],
});

// Manual translation step sequence state
const manualTranslationStep = ref(0) // 0 = inactive, 1 = step 1 active, 2 = step 2 active, 3 = step 3 active

const translatingLanguages = ref({})
const abortController = ref(null)
// Track cancelled translations to prevent saving even if request completes
const cancelledLanguages = ref({})

// Sorting and search
const sortOption = ref('name-asc')
const searchQuery = ref('')

const isTranslating = computed(() => {
    return Object.values(translatingLanguages.value).some(value => value === true);
})

const cleanupSystemMessages = async (languageKey) => {
    try {
        await axios.post(
            route('cleanup.systemMessages', { code: page.props.questionnaire.code }),
            { language: languageKey },
            {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        );
        // console.log('System messages cleaned up for cancelled translation', languageKey);
    } catch (error) {
        console.error('Failed to cleanup system messages:', error);
    }
}

const cancelTranslation = async () => {
    if (abortController.value) {
        abortController.value.abort();
        abortController.value = null;
    }

    const currentTranslating = Object.keys(translatingLanguages.value).find(
        key => translatingLanguages.value[key] === true
    );

    if (currentTranslating) {
        translatingLanguages.value[currentTranslating] = false;
        // Mark this language as cancelled to prevent saving even if request completes
        cancelledLanguages.value[currentTranslating] = true;

        // Clean up system messages that might have been saved by the async job
        await cleanupSystemMessages(currentTranslating);
    }

    // console.log('Translation cancelled by user');
}

const formData = useForm({
    language: ' ',
    languageInfo: {},
    csvData: []
})

// Watch for language changes and reset steps if they were already active
watch(() => formData.language, (newLang, oldLang) => {
    // Only reset if:
    // 1. Steps are already active (user has started the download process)
    // 2. Language actually changed (not initial selection)
    // 3. Old language was not the default empty value
    if (manualTranslationStep.value >= 1 && newLang !== oldLang && oldLang && oldLang !== ' ') {
        manualTranslationStep.value = 0;
    }
})

// Watch for modal opening and reset steps when modal is opened
watch(() => addLanguage.value.show, (isOpen) => {
    if (isOpen) {
        // Reset steps when modal is opened
        manualTranslationStep.value = 0;
    }
})

// Watch for active row changes and scroll to corresponding row in Original column
watch(() => editTranslationModal.value.activeRowKey, async (newKey) => {
    if (newKey && originalRowRefs.value[newKey]) {
        // Wait for next tick to ensure DOM is updated
        await nextTick();
        
        const rowElement = originalRowRefs.value[newKey];
        if (rowElement && originalScrollContainer.value) {
            // Scroll the row into view with smooth behavior
            rowElement.scrollIntoView({
                behavior: 'smooth',
                block: 'center', // Center the row in the viewport
                inline: 'nearest'
            });
        }
    }
})

const LanguageList = LanguageJson.languages;

const languageListComputed = computed(() => {
    // Collect all language codes that are already applied to this questionnaire
    const appliedLanguageCodes = new Set(
        Object.keys(page.props.questionnaire.setup?.language || {}).filter(key => {
            // Exclude the original language placeholder (space / empty)
            return key && key.trim() !== '' && key !== ' ';
        })
    );

    return LanguageList
        // Hide languages that already exist as translations (manual or AI)
        .filter(item => !appliedLanguageCodes.has(item.Code))
        .map(item => ({
            value: item.Code,
            text: item.EnglishName + ' (' + item.NativeName + ')' + ' <small class="text-xs px-2" style="border-radius: 50px; background-color: rgba(209, 129, 11, 0.15); color: var(--mcolor2); border: 1px solid rgba(209, 129, 11, 0.3);">' + item.Code + '</small>',
            englishName: item.EnglishName,
            code: item.Code
        }))
        .sort((a, b) => {
            // Sort alphabetically by EnglishName
            return (a.englishName || a.code).localeCompare(b.englishName || b.code);
        });
})



const AppliedLanguages = computed(() => {
    let list = []
    if (!page.props.questionnaire.setup?.language) {
        return list;
    }
    Object.keys(page.props.questionnaire.setup?.language).forEach(key => {
        // Skip empty string (Original language) - it's shown separately
        if (key === ' ' || !key || key.trim() === '') {
            return;
        }
        let languageInfo = LanguageList.find(item => item.Code === key);
        // Fallback if language not found in LanguageList
        if (!languageInfo) {
            languageInfo = {
                EnglishName: key,
                Code: key,
                NativeName: key
            };
        }
        list.push({
            key: key,
            languageInfo: languageInfo,
            data: page.props.questionnaire.setup.language[key],
            // Use the order in the object as a proxy for date uploaded (earlier = older)
            order: Object.keys(page.props.questionnaire.setup?.language).indexOf(key)
        })
    })
    return list;
})

const filteredAndSortedLanguages = computed(() => {
    let filtered = AppliedLanguages.value;

    // Apply search filter
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase().trim();
        filtered = filtered.filter(lang => {
            const englishName = (lang.languageInfo.EnglishName || '').toLowerCase();
            const nativeName = (lang.languageInfo.NativeName || '').toLowerCase();
            const code = (lang.languageInfo.Code || '').toLowerCase();
            return englishName.includes(query) || nativeName.includes(query) || code.includes(query);
        });
    }

    // Apply sorting
    const sorted = [...filtered];
    switch (sortOption.value) {
        case 'name-asc':
            sorted.sort((a, b) => {
                return (a.languageInfo.EnglishName || a.languageInfo.Code).localeCompare(b.languageInfo.EnglishName || b.languageInfo.Code);
            });
            break;
        case 'name-desc':
            sorted.sort((a, b) => {
                return (b.languageInfo.EnglishName || b.languageInfo.Code).localeCompare(a.languageInfo.EnglishName || a.languageInfo.Code);
            });
            break;
        case 'date-asc':
            // Oldest first (lower order number = older)
            sorted.sort((a, b) => a.order - b.order);
            break;
        case 'date-desc':
            // Newest first (higher order number = newer)
            sorted.sort((a, b) => b.order - a.order);
            break;
    }

    return sorted;
})

const _fn = {
    open: () => {
        Options.value.show = true;
    },
    close: () => {
        Options.value.show = false;
    },
    toggle: () => {
        Options.value.show = !Options.value.show;
    },
    isOpen: () => {
        return Options.value.show;
    },
    openAddLanguage: () => {
        Options.value.show = true;
        // Use nextTick to ensure the sidebar is open before opening the modal
        setTimeout(() => {
            addLanguage.value.show = true;
        }, 100);
    }
}


const stripBom = (text) => text.replace(/^\uFEFF/, '');

const parseCSV = (text) => {
    const sanitizedText = stripBom(text);
    const lines = sanitizedText.split(/\r?\n/).filter(line => line.trim() !== '');
    const result = [];

    for (const line of lines) {
        const row = [];
        let current = '';
        let inQuotes = false;

        for (let i = 0; i < line.length; i++) {
            const char = line[i];

            if (char === '"') {
                if (inQuotes && line[i + 1] === '"') {
                    current += '"';
                    i++;
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (char === ',' && !inQuotes) {
                row.push(current.trim());
                current = '';
            } else {
                current += char;
            }
        }
        row.push(current.trim());
        result.push(row);
    }

    return result;
}

const decodeCSVText = (arrayBuffer) => {
    const bytes = new Uint8Array(arrayBuffer);
    const bytesContainHighBit = bytes.some(byte => byte > 0x7f);

    const tryDecode = (encoding) => {
        try {
            return new TextDecoder(encoding).decode(bytes);
        } catch (error) {
            console.warn(`Encoding ${encoding} not supported in this environment.`, error);
            return null;
        }
    };

    const hasNonAscii = (text) => /[^\u0000-\u007F]/.test(text);

    const isReadable = (text) => {
        if (!text) return false;
        if (text.includes('\uFFFD')) return false;
        if (bytesContainHighBit && !hasNonAscii(text)) return false;
        return true;
    };

    if (bytes.length >= 2) {
        const [first, second, third] = bytes;

        if (first === 0xff && second === 0xfe) {
            const decoded = tryDecode('utf-16le');
            if (isReadable(decoded)) return decoded;
        }

        if (first === 0xfe && second === 0xff) {
            const decoded = tryDecode('utf-16be');
            if (isReadable(decoded)) return decoded;
        }

        if (first === 0xef && second === 0xbb && third === 0xbf) {
            const decoded = tryDecode('utf-8');
            if (isReadable(decoded)) return decoded;
        }
    }

    const encodingsToTry = ['utf-8', 'utf-16le', 'utf-16be', 'euc-kr', 'ks_c_5601-1987', 'windows-949'];

    for (const encoding of encodingsToTry) {
        const decoded = tryDecode(encoding);
        if (isReadable(decoded)) {
            // console.info(`CSV decoded using ${encoding}`);
            return decoded;
        }
    }

    return tryDecode('utf-8') ?? '';
}

const editTranslationRows = computed(() => {
    // Early return if modal is not showing - avoids expensive computation during close
    if (!editTranslationModal.value.show) return [];
    
    const csv = editTranslationModal.value.csvData || [];
    if (!Array.isArray(csv) || csv.length <= 1) return [];

    const systemLabelMap = {
        loading: 'Loading message',
        saving: 'Saving message',
        next_button: 'Next button',
        prev_button: 'Previous button',
        final_button: 'Final button',
        warning1: 'Warning (min)',
        warning2: 'Warning (max)',
        warning3: 'Warning (min/max)',
        warning4: 'Warning (min)',
        warning5: 'Warning (max)',
        warning6: 'Warning (alphanumeric)',
        warning7: 'Warning (letters)',
        warning8: 'Warning (invalid email)',
        required: 'Required warning',
        complete: 'Complete redirect',
        'quality control': 'Quality control redirect',
        screenout: 'Screenout redirect',
        'connection lost title': 'Connection lost (title)',
        'connection lost content': 'Connection lost (content)',
    };

    const rows = [];
    let currentGroup = null;
    let currentGroupTitle = null;
    let inSystemMessages = false;
    let counters = {
        option: 0,
        item: 0,
        value: 0,
        column: 0,
        row: 0,
        statementa: 0,
        statementb: 0,
    };

    for (let i = 1; i < csv.length; i++) {
        const row = csv[i] || [];
        const order = (row[0] ?? '').toString().trim();
        const labelRaw = (row[1] ?? '').toString().trim();
        const labelKey = labelRaw.toLowerCase();
        const original = (row[2] ?? '').toString();
        const translated = (row[3] ?? '').toString();

        if (order && /^Q\d+/i.test(order)) {
            currentGroup = order;
            const questionTitle = getQuestionTitleByQNumber(order);
            currentGroupTitle = questionTitle ? `${order} ${questionTitle}` : order;
            inSystemMessages = false;
            counters = {
                option: 0,
                item: 0,
                value: 0,
                column: 0,
                row: 0,
                statementa: 0,
                statementb: 0,
            };
            rows.push({
                key: `g:${currentGroup}:${i}`,
                isGroupHeader: true,
                groupTitle: currentGroupTitle,
                questionNumber: order,
            });
        } else if (order && /system messages/i.test(order)) {
            currentGroup = 'System Messages';
            currentGroupTitle = 'System Messages';
            inSystemMessages = true;
            rows.push({
                key: `g:${currentGroup}:${i}`,
                isGroupHeader: true,
                groupTitle: currentGroupTitle,
            });
        }

        let label = 'Text';

        if (inSystemMessages) {
            // In system messages section, "order" is empty on subsequent rows and "labelRaw" contains things like "saving", "next_button", etc.
            const mapped = systemLabelMap[labelKey] || (labelRaw ? labelRaw : 'System');
            label = `${mapped}`;
        } else {
            // Question rows
            if (labelKey === 'question') {
                label = 'Question';
            } else if (labelKey === 'option') {
                counters.option += 1;
                label = `Choice ${counters.option}`;
            } else if (labelKey === 'placeholder') {
                // placeholder always follows an option
                label = `Choice ${Math.max(1, counters.option)} — Placeholder`;
            } else if (labelKey === 'item') {
                counters.item += 1;
                label = `Item ${counters.item}`;
            } else if (labelKey === 'value') {
                counters.value += 1;
                label = `Choice ${counters.value}`;
            } else if (labelKey === 'column') {
                counters.column += 1;
                label = `Column ${counters.column}`;
            } else if (labelKey === 'row') {
                counters.row += 1;
                label = `Row ${counters.row}`;
            } else if (labelKey === 'before_textbox') {
                label = 'Before textbox';
            } else if (labelKey === 'after_textbox') {
                label = 'After textbox';
            } else if (labelKey === 'header') {
                label = 'Header';
            } else if (labelKey === 'footer') {
                label = 'Footer';
            } else if (labelKey === 'labela') {
                label = 'Label A';
            } else if (labelKey === 'labelb') {
                label = 'Label B';
            } else if (labelKey === 'statementa') {
                counters.statementa += 1;
                label = `Statement A ${counters.statementa}`;
            } else if (labelKey === 'statementb') {
                counters.statementb += 1;
                label = `Statement B ${counters.statementb}`;
            } else if (labelRaw) {
                // Fallback: preserve original label but make it nicer
                label = labelRaw.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            }
        }

        // Check if this translation has changed (compare with previous snapshot)
        let hasChanged = false;
        if (editTranslationModal.value.previousTranslations && editTranslationModal.value.previousTranslations[i]) {
            const previousTranslated = (editTranslationModal.value.previousTranslations[i][3] ?? '').toString().trim();
            const currentTranslated = translated.trim();
            hasChanged = previousTranslated !== currentTranslated && currentTranslated !== '';
        }

        rows.push({
            key: `r:${currentGroup || 'root'}:${i}`,
            isGroupHeader: false,
            groupTitle: currentGroupTitle,
            csvIndex: i,
            label,
            original,
            translated,
            indent: !(/^Q\d+/i.test(order) || /system messages/i.test(order)) && !!currentGroup,
            hasChanged, // Flag to indicate if this translation changed after re-translation
        });
    }

    return rows;
});

const updateEditTranslation = (csvIndex, value) => {
    const csv = editTranslationModal.value.csvData;
    if (!Array.isArray(csv) || !csv[csvIndex]) return;
    // Ensure 4 columns exist
    while (csv[csvIndex].length < 4) csv[csvIndex].push('');
    csv[csvIndex][3] = value;
};

const handleTranslationBlur = (event) => {
    // Only clear if focus is moving outside the translation editor area
    // Use a small delay to check if focus moved to another editor
    setTimeout(() => {
        const activeElement = document.activeElement;
        const isStillInEditor = activeElement?.closest('.don-editor') || 
                                activeElement?.closest('.ql-editor') ||
                                activeElement?.closest('[contenteditable="true"]');
        if (!isStillInEditor) {
            editTranslationModal.value.activeRowKey = null;
        }
    }, 100);
};

const loadLanguageCsvForEdit = async (languageKey) => {
    const response = await axios.get(
        route('download.questionnaire', {
            questionnaire_code: page.props.questionnaire.code,
            language: languageKey,
            name: `${page.props.questionnaire.name}_${languageKey}.csv`,
        }),
        { responseType: 'arraybuffer' }
    );
    const text = decodeCSVText(response.data);
    const csvArray = parseCSV(text);
    editTranslationModal.value.csvData = csvArray;
};


const closeTranslationPreview = async () => {
    translationPreviewModal.value.closing = true;
    // Small delay to show spinner
    await new Promise(resolve => setTimeout(resolve, 300));
    translationPreviewModal.value.show = false;
    translationPreviewModal.value.language = null;
    translationPreviewModal.value.closing = false;
};

const proceedToEditTranslationFromPreview = async () => {
    const lang = translationPreviewModal.value.language;
    if (!lang) return;

    closeTranslationPreview();
    await openEditTranslation(lang);
};

const openEditTranslation = async (language) => {
    // Start in loading state with only the standalone loader visible
    editTranslationModal.value.loading = true;
    editTranslationModal.value.show = false;
    editTranslationModal.value.language = language;
    editTranslationModal.value.rtl = !!language?.languageInfo?.RTL;
    editTranslationModal.value.csvData = [];
    editTranslationModal.value.previousTranslations = null; // Clear previous snapshot when opening new translation
    editTranslationModal.value.activeRowKey = null; // Clear active row highlight when opening new translation
    editTranslationModal.value.closing = false; // Reset closing state when opening new translation
    originalRowRefs.value = {}; // Clear row refs when opening new translation

    try {
        await loadLanguageCsvForEdit(language.key);
        // Only show the SSR modal after the CSV has finished loading
        editTranslationModal.value.show = true;
    } catch (e) {
        console.error(e);
        toast.error('Failed to load translation. Please try again.');
        closeEditTranslationModal();
    } finally {
        editTranslationModal.value.loading = false;
    }
};

const closeEditTranslationModal = () => {
    // Clear the heavy data FIRST - this makes editTranslationRows return [] immediately
    // When Vue destroys the modal, there are no TE2 components to clean up, just empty divs
    editTranslationModal.value.csvData = [];
    editTranslationModal.value.previousTranslations = null; // Clear change tracking snapshot
    editTranslationModal.value.activeRowKey = null; // Clear active row highlight
    originalRowRefs.value = {}; // Clear row refs
    
    // Hide modal immediately - since csvData is empty, destruction is much faster
    editTranslationModal.value.show = false;
    editTranslationModal.value.loading = false;
    
    // Clean up other lightweight properties (doesn't block)
    editTranslationModal.value.language = null;
    editTranslationModal.value.rtl = false;
    editTranslationForm.reset();
};

const cancelEditTranslationModal = async () => {
    editTranslationModal.value.closing = true;
    // Small delay to show spinner
    await new Promise(resolve => setTimeout(resolve, 300));
    closeEditTranslationModal();
    editTranslationModal.value.closing = false;
};

const saveEditedTranslation = () => {
    const lang = editTranslationModal.value.language;
    if (!lang?.key) return;

    editTranslationForm.language = lang.key;
    editTranslationForm.languageInfo = lang.languageInfo || {};
    editTranslationForm.csvData = editTranslationModal.value.csvData || [];

    editTranslationForm.put(route('upload.translation.csv', { code: page.props.questionnaire.code }), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Translation saved.', { timeout: 2500 });
            router.reload({
                only: ['questionnaire', 'questions'],
                preserveState: true,
                preserveScroll: true,
            });
            closeEditTranslationModal();
        },
        onError: () => {
            toast.error('Failed to save translation. Please try again.');
        },
    });
};

const retranslateFromModal = async () => {
    const lang = editTranslationModal.value.language;
    if (!lang?.key) return;

    const ok = window.confirm(
        'Re-translate will run AI translation again and may overwrite existing translations. Continue?'
    );
    if (!ok) return;

    try {
        // Show loading state during re-translation
        editTranslationModal.value.loading = true;
        
        // Save snapshot of current translations before re-translation for change detection
        if (editTranslationModal.value.csvData && Array.isArray(editTranslationModal.value.csvData)) {
            editTranslationModal.value.previousTranslations = JSON.parse(JSON.stringify(editTranslationModal.value.csvData));
        }
        
        // Run AI translation
        await aiTranslate(lang.key, lang.languageInfo?.EnglishName, lang.languageInfo?.RTL);
        
        // Reload CSV after re-translate so modal shows latest values
        await loadLanguageCsvForEdit(lang.key);
        
        // Ensure reactivity - the computed editTranslationRows will automatically update
        // since it depends on editTranslationModal.value.csvData
        await nextTick();
        
        // Count how many translations changed
        const changedCount = editTranslationRows.value.filter(row => !row.isGroupHeader && row.hasChanged).length;
        if (changedCount > 0) {
            toast.success(`${changedCount} translation${changedCount === 1 ? ' has' : 's have'} been updated`);
        } else {
            toast.success('Translation completed (no changes detected)');
        }
    } catch (error) {
        console.error('Re-translation error:', error);
        toast.error('Failed to re-translate. Please try again.');
        // Clear previous translations on error
        editTranslationModal.value.previousTranslations = null;
    } finally {
        editTranslationModal.value.loading = false;
    }
};

const downloadOriginalQuestionnaire = (lang = null) => {
    if (!lang) {
        lang = formData.language;
    }

    // Set step 1 as active when download button is clicked
    manualTranslationStep.value = 1;

    // Trigger download
    window.location.href = route('download.questionnaire', {
        questionnaire_code: page.props.questionnaire.code,
        language: lang,
        name: page.props.questionnaire.name + '_' + lang + '.csv'
    });

    // After a short delay, assume download started and move to step 2
    // Step 2 represents that the user should now open and translate the file
    setTimeout(() => {
        manualTranslationStep.value = 2;
    }, 1000);
}

const uploadTranslationCSV = (lang = null) => {
    if (lang) {
        formData.language = lang;
    }
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = '.csv';
    fileInput.onchange = (event) => {
        const file = event.target.files[0];
        const reader = new FileReader();
        reader.onload = (event) => {
            const text = decodeCSVText(event.target.result);
            const csvArray = parseCSV(text);

            // Validate language selection
            if (!formData.language) {
                console.error("Please select a language first");
                return;
            }

            const languageInfo = LanguageList.find(item => item.Code === formData.language);
            if (!languageInfo) {
                console.error("Invalid language selected");
                return;
            }

            // Validate CSV data
            if (!csvArray || csvArray.length === 0) {
                console.error("CSV file is empty or invalid");
                toast.error("CSV file is empty or invalid. Please check the file and try again.");
                return;
            }

            formData.languageInfo = languageInfo;
            formData.csvData = csvArray;

            // Capture uploaded language now so we use it after PUT/reload (formData may be reset)
            const uploadedLanguageCode = formData.language;
            const uploadedLangName = languageInfo?.EnglishName || formData.language;

            // Set step 3 as active when upload starts
            manualTranslationStep.value = 3;

            formData.put(route('upload.translation.csv', { code: page.props.questionnaire.code }), {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    // Show a clear success indicator for the user
                    toast.success(`Translated CSV successfully uploaded for ${uploadedLangName}.`);

                    // Use the captured language code so Index.vue shows the uploaded language (same as AI Translate)
                    const newLanguageCode = uploadedLanguageCode || (formData.languageInfo?.Code || ' ');

                    // Reload questionnaire and questions so new manual translations appear immediately
                    router.reload({
                        only: ['questionnaire', 'questions'],
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            // Update URL to reflect the newly added language
                            updateLanguageUrl(newLanguageCode);

                            // Dispatch language-changed event to notify Setting.vue and other components
                            // This ensures translations are reloaded after manual translation upload completes
                            // Use nextTick to ensure page props are updated first
                            nextTick(() => {
                                window.dispatchEvent(new CustomEvent('language-changed', {
                                    detail: { languageCode: newLanguageCode }
                                }));
                            });

                            // Close the modal after successful upload
                            setTimeout(() => {
                                addLanguage.value.show = false;
                                // Reset the step sequence for next time
                                manualTranslationStep.value = 0;
                            }, 500);
                        }
                    });
                },
                onError: (errors) => {
                    console.error("Error uploading translation:", errors);
                    toast.error("Error uploading translated CSV. Please review the file and try again.");
                    // Reset to step 2 if upload fails
                    manualTranslationStep.value = 2;
                }
            });

        }
        reader.readAsArrayBuffer(file);
    }
    fileInput.click();
}

const aiTranslate = async (languageKey = null, languageName = null, RTL = null) => {
    let finalLanguageKey = languageKey;
    let finalLanguageName = languageName;
    let finalRTL = RTL;

    if (!finalLanguageKey) {
        if (!formData.language) {
            toast.error("Please select a language first");
            return;
        }
        finalLanguageKey = formData.language;
        const languageInfo = LanguageList.find(item => item.Code === finalLanguageKey);
        if (!languageInfo) {
            toast.error("Invalid language selected");
            return;
        }
        if (!finalLanguageName) {
            finalLanguageName = languageInfo.EnglishName;
        }
        if (finalRTL === null) {
            finalRTL = languageInfo.RTL;
        }
    }

    // Clear any previous cancellation flag for this language
    cancelledLanguages.value[finalLanguageKey] = false;
    translatingLanguages.value[finalLanguageKey] = true;

    abortController.value = new AbortController();
    const signal = abortController.value.signal;

    const startTime = Date.now();

    try {
        const variableStart = page.props.questionnaire.setup?.variable_start || 1;
        const questions = page.props.questionnaire.questions || [];
        const questionKeys = questions
            .sort((a, b) => a.order - b.order)
            .map(q => `Q${q.order + variableStart - 1}`);

        let allTranslatedKeys = [];
        let translationError = null;

        try {
            const response = await axios.post(
                route('ai.translate.question', { code: page.props.questionnaire.code }),
                {
                    language: finalLanguageKey,
                    languageName: finalLanguageName,
                    questionKeys: questionKeys,
                    includeSystemMessages: true,
                    RTL: finalRTL
                },
                {
                    signal: signal,
                    timeout: 300000,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            // Check if cancelled before processing response
            if (cancelledLanguages.value[finalLanguageKey] || signal.aborted) {
                translatingLanguages.value[finalLanguageKey] = false;
                cancelledLanguages.value[finalLanguageKey] = false;
                abortController.value = null;
                // Clean up system messages that might have been saved by the async job
                await cleanupSystemMessages(finalLanguageKey);
                return;
            }

            allTranslatedKeys = response.data.translatedKeys || [];
            
            // Check if this is an async response (jobs dispatched, will complete in background)
            const isAsyncMode = response.data.success && 
                                allTranslatedKeys.length === 0 && 
                                response.data.message?.includes('dispatched');
            
            if (isAsyncMode) {
                // Show in-progress toast
                toast.info(
                    `Translating ${questionKeys.length} questions. Please wait, this may take a few minutes depending on the number and length of questions...`,
                    { timeout: 10000 }
                );
                
                // Reload immediately to show the newly added language in the dropdown
                // The backend now adds the language to setup immediately, so it should appear right away
                router.reload({
                    only: ['questionnaire'],
                    preserveState: true,
                    preserveScroll: true
                });
                
                // Estimate completion time: ~15 seconds per batch of 3 questions, plus overhead
                const estimatedSeconds = Math.max(30, Math.ceil(questionKeys.length / 3) * 15 + 20);
                
                // Poll for completion by reloading data periodically
                let pollCount = 0;
                const maxPolls = Math.ceil(estimatedSeconds / 10);
                
                const pollForCompletion = async () => {
                    pollCount++;
                    
                    // Check if cancelled
                    if (cancelledLanguages.value[finalLanguageKey]) {
                        translatingLanguages.value[finalLanguageKey] = false;
                        cancelledLanguages.value[finalLanguageKey] = false;
                        return;
                    }
                    
                    // Reload to check for updates
                    router.reload({
                        only: ['questionnaire', 'questions'],
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            // Check if translations are now present
                            const updatedQuestions = page.props.questionnaire?.questions || [];
                            const translatedQuestions = updatedQuestions.filter(q => {
                                const langSetup = q.setup?.language?.[finalLanguageKey];
                                return langSetup && langSetup.question;
                            });
                            
                            // Polling for translation completion
                            
                            if (translatedQuestions.length >= questionKeys.length) {
                                // All translations complete!
                                translatingLanguages.value[finalLanguageKey] = false;
                                toast.success(
                                    `Successfully translated ${translatedQuestions.length} questions to ${finalLanguageName}`,
                                    { timeout: 5000 }
                                );
                                
                                // Update URL to reflect the newly added language
                                updateLanguageUrl(finalLanguageKey);
                                
                                // Close modal and dispatch event
                                addLanguage.value.show = false;
                                nextTick(() => {
                                    window.dispatchEvent(new CustomEvent('language-changed', {
                                        detail: { languageCode: finalLanguageKey }
                                    }));
                                });
                            } else if (pollCount < maxPolls) {
                                // Continue polling
                                setTimeout(pollForCompletion, 10000);
                            } else {
                                // Max polls reached, but some translations may be done
                                translatingLanguages.value[finalLanguageKey] = false;
                                if (translatedQuestions.length > 0) {
                                    toast.warning(
                                        `Translated ${translatedQuestions.length} of ${questionKeys.length} questions. Some may still be processing.`,
                                        { timeout: 6000 }
                                    );
                                } else {
                                    toast.info(
                                        'Translation is still processing. Please refresh the page in a moment.',
                                        { timeout: 6000 }
                                    );
                                }
                                
                                // Close modal
                                addLanguage.value.show = false;
                            }
                        }
                    });
                };
                
                // Start polling after a short delay
                setTimeout(pollForCompletion, 10000);
                return; // Exit early - polling will handle completion
            }
            
            if (response.data.success && allTranslatedKeys.length < questionKeys.length) {
                console.warn('Backend returned fewer keys than sent, but request was successful. Assuming all questions were translated.', {
                    sentCount: questionKeys.length,
                    returnedCount: allTranslatedKeys.length,
                    returnedKeys: allTranslatedKeys,
                    sentKeys: questionKeys
                });
                allTranslatedKeys = questionKeys;
            }
        } catch (error) {
            if (axios.isCancel(error) || error.name === 'AbortError' || signal.aborted || cancelledLanguages.value[finalLanguageKey]) {
                translatingLanguages.value[finalLanguageKey] = false;
                cancelledLanguages.value[finalLanguageKey] = false;
                abortController.value = null;
                // Clean up system messages that might have been saved by the async job
                await cleanupSystemMessages(finalLanguageKey);
                return;
            }

            translationError = error.response?.data?.error || error.message || 'Translation request failed';
            console.error('Translation request failed', {
                error: translationError,
                errorResponse: error.response?.data,
                status: error.response?.status,
                questionKeys: questionKeys
            });
            throw new Error(`Translation failed: ${translationError}`);
        }

        // Check cancellation flag again before proceeding with save
        if (signal.aborted || cancelledLanguages.value[finalLanguageKey]) {
            translatingLanguages.value[finalLanguageKey] = false;
            cancelledLanguages.value[finalLanguageKey] = false;
            abortController.value = null;
            // Clean up system messages that might have been saved by the async job
            await cleanupSystemMessages(finalLanguageKey);
            return;
        }

        const totalDuration = ((Date.now() - startTime) / 1000).toFixed(2);
        const translatedCount = allTranslatedKeys.length;

        if (allTranslatedKeys.length === questionKeys.length) {
            toast.success(
                `Translated ${allTranslatedKeys.length} question${allTranslatedKeys.length !== 1 ? 's' : ''} and system messages to ${finalLanguageName} in ${totalDuration}s`,
                { timeout: 5000 }
            );
        } else {
            const warningMessage = `Translated ${allTranslatedKeys.length} out of ${questionKeys.length} question${questionKeys.length !== 1 ? 's' : ''} and system messages to ${finalLanguageName} in ${totalDuration}s`;
            toast.warning(warningMessage, { timeout: 6000 });
        }

        // Reload questionnaire and questions data to get latest translations
        router.reload({
            only: ['questionnaire', 'questions'],
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // Close the language modal after successful reload (same as manual upload)
                addLanguage.value.show = false;

                // Use the newly added language code (not the current one)
                // This ensures the page switches to the newly translated language
                const newLanguageCode = finalLanguageKey;

                // Update URL to reflect the newly added language
                updateLanguageUrl(newLanguageCode);

                // Dispatch language-changed event to notify Setting.vue and Index.vue
                // This ensures translations are reloaded after AI translation completes
                // Use nextTick to ensure page props are fully updated
                nextTick(() => {
                    window.dispatchEvent(new CustomEvent('language-changed', {
                        detail: { languageCode: newLanguageCode }
                    }));
                });

                // Only clear loading state  after frontend reflects backend changes
                translatingLanguages.value[finalLanguageKey] = false;
                cancelledLanguages.value[finalLanguageKey] = false;
                abortController.value = null;
            }
        });
    } catch (error) {
        if (axios.isCancel(error) || error.name === 'AbortError' || (abortController.value && abortController.value.signal.aborted) || cancelledLanguages.value[finalLanguageKey]) {
            // console.log('Translation cancelled by user');
            translatingLanguages.value[finalLanguageKey] = false;
            cancelledLanguages.value[finalLanguageKey] = false;
            abortController.value = null;
            // Clean up system messages that might have been saved by the async job
            await cleanupSystemMessages(finalLanguageKey);
            return;
        }

        const totalDuration = ((Date.now() - startTime) / 1000).toFixed(2);
        console.error('Translation process failed', {
            error: error.message,
            totalDuration: `${totalDuration}s`,
            timestamp: new Date().toISOString()
        });

        translatingLanguages.value[finalLanguageKey] = false;
        cancelledLanguages.value[finalLanguageKey] = false;
        abortController.value = null;
        const errorMsg = error.message || error.error || 'Unknown error occurred';
        toast.error(`Translation failed: ${errorMsg}`, { timeout: 5000 });
    }
}

const confirmDeleteLanguage = (language) => {
    deleteLanguageModal.value = {
        delete: true,
        data: language,
    };
};

const handleLanguageDeleted = (key) => {
    // Close modal and optionally handle any local state updates here
    deleteLanguageModal.value = {
        delete: false,
        data: null,
    };
};

defineExpose({
    _fn
});
</script>

<style scoped>
::v-deep .flag {
    font-family: 'Segoe UI Emoji', 'Apple Color Emoji', 'Noto Color Emoji', sans-serif;
}

::v-deep .ccode {
    font-size: 6px;
    background-color: var(--mcolor);
    color: white;
    padding: 2px 10px;
    border-radius: 50px;
    font-weight: 600;
    margin-right: 5px;
}

.MChoice :deep() img {
    width: 10vmin !important;
    border: 1px solid lightgray;
    display: inline-block;
    vertical-align: middle;
    margin-left: 4px;
    margin-right: 4px;
}

.MChoice :deep() p {
    padding: 0px !important;
    padding-right: 10px !important;
    margin: 0px !important;
    margin-block-start: 0em !important;
    margin-block-end: 0em !important;
    display: inline-block !important;
    width: 100% !important;
}
</style>