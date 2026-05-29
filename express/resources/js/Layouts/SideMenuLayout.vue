<template>
    <div class="fixed w-[140px] h-[100vh] bg-white top-[0px] z-[100] pt-[70px] shadow-md">
        <div class="h-[100%] overflow-auto bg-[white] shadow-lg">
            <a :set="routerLink = 'questions.index'"
                class="w-full flex flex-col justify-center items-center gap-[6px] p-[24px_12px] border-b border-gray-200 cursor-pointer text-[var(--txtcolor)] hover:bg-gray-100"
                :class="{
                    'bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-white': route().current(routerLink)
                }" :href="route(routerLink, {
                    'questionnaire_code': $page.props.questionnaire.code,
                    'language_id': Object.hasOwn($page.props, 'language') ? $page.props.language.id : 1
                })">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-[40px] h-[40px]">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                    </svg>
                </div>
                <div class="font-bold">Questionnaire</div>
            </a>


            <!-- <div class="w-full flex flex-col justify-center items-center gap-[6px] p-[24px_12px] border-b border-gray-200 cursor-pointer text-[var(--txtcolor)]"
        :class="{'bg-[var(--mcolor)]':($Menu.active == 'Settings')}"
        >
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-[40px]">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>

            </div>
            <div class="font-bold">Settings</div>
        </div> -->
            <!-- <div class="w-full flex flex-col justify-center items-center gap-[6px] p-[24px_12px] border-b border-gray-200 cursor-pointer text-[var(--txtcolor)]"
        :class="{'bg-[var(--mcolor)]':($Menu.active == 'Gallery')}"
        >
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-[40px]">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </div>
            <div class="font-bold">Gallery</div>
        </div> -->
            <a :set="routerLink = 'templates.index'"
                class="w-full flex flex-col justify-center items-center text-center gap-[6px] p-[24px_12px] border-b border-gray-200 cursor-pointer text-[var(--txtcolor)] hover:bg-gray-100"
                :class="{
                    'bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-white': route().current(routerLink)
                }" :href="route(routerLink, {
                    'questionnaire_code': $page.props.questionnaire.code,
                    'language_id': Object.hasOwn($page.props, 'language') ? $page.props.language.id : 1
                })">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-[40px] h-[40px]">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
                <div class="font-bold">Question Templates</div>
            </a>

            <a :set="routerLink = 'themes.index'"
                class="w-full flex flex-col justify-center items-center gap-[6px] p-[24px_12px] border-b border-gray-200 cursor-pointer text-[var(--txtcolor)] hover:bg-gray-100"
                :class="{
                    'bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-white': route().current(routerLink)
                }" :href="route(routerLink, {
                    'questionnaire_code': $page.props.questionnaire.code,
                    'language_id': Object.hasOwn($page.props, 'language') ? $page.props.language.id : 1
                })">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[40px] h-[40px]" viewBox="0 0 24 24"
                        fill="currentColor">
                        <path
                            d="M13.4 2.096a10.08 10.08 0 0 0-8.937 3.331A10.054 10.054 0 0 0 2.096 13.4c.53 3.894 3.458 7.207 7.285 8.246a9.982 9.982 0 0 0 2.618.354l.142-.001a3.001 3.001 0 0 0 2.516-1.426 2.989 2.989 0 0 0 .153-2.879l-.199-.416a1.919 1.919 0 0 1 .094-1.912 2.004 2.004 0 0 1 2.576-.755l.412.197c.412.198.85.299 1.301.299A3.022 3.022 0 0 0 22 12.14a9.935 9.935 0 0 0-.353-2.76c-1.04-3.826-4.353-6.754-8.247-7.284zm5.158 10.909-.412-.197c-1.828-.878-4.07-.198-5.135 1.494-.738 1.176-.813 2.576-.204 3.842l.199.416a.983.983 0 0 1-.051.961.992.992 0 0 1-.844.479h-.112a8.061 8.061 0 0 1-2.095-.283c-3.063-.831-5.403-3.479-5.826-6.586-.321-2.355.352-4.623 1.893-6.389a8.002 8.002 0 0 1 7.16-2.664c3.107.423 5.755 2.764 6.586 5.826.198.73.293 1.474.282 2.207-.012.807-.845 1.183-1.441.894z" />
                        <circle cx="7.5" cy="14.5" r="1.5" />
                        <circle cx="7.5" cy="10.5" r="1.5" />
                        <circle cx="10.5" cy="7.5" r="1.5" />
                        <circle cx="14.5" cy="7.5" r="1.5" />
                    </svg>
                </div>
                <div class="font-bold">Themes</div>
            </a>


            <a @click="toggleSlideover" :class="[
                'w-full flex flex-col justify-center items-center gap-[6px] p-[24px_12px] border-b border-gray-200 cursor-pointer text-[var(--txtcolor)] hover:bg-gray-100',
                isGalleryActive ? 'bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-white' : ''
            ]">
                <svg viewBox="0 0 250 250" fill="none" stroke-width="1.2" stroke="currentColor" class="w-[40px]"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M144.531 52.5547V31.5547H52.5312C34.9311 31.5547 30.8645 45.5547 31.0312 52.5547V198.055C30.2312 216.055 45.0312 219.888 52.5312 219.555H197.031C215.431 219.555 219.365 205.221 219.031 198.055V106.555H197.531L197.031 198.055H52.5312V52.5547H144.531Z"
                        fill="currentColor" />
                    <path d="M104.531 145.555L135.531 104.555L187.531 177.555H62.0312L94.0312 136.055L104.531 145.555Z"
                        fill="currentColor" />
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M154.531 38.5547V88.429C154.365 91.1375 155.631 96.4547 162.031 96.0547H213.531C215.698 95.8042 219.931 93.9283 219.531 88.429V38.5547C219.531 36.2214 218.331 31.4547 213.531 31.0547H162.031C159.531 30.7214 154.531 31.7547 154.531 38.5547ZM163.531 64.5547L169.031 59.0547L183.031 73.0547L183.531 39.5547H191.531V73.0547L205.531 59.0547L211.031 64.5547L187.031 88.5547L163.531 64.5547Z"
                        fill="currentColor" />
                </svg>
                <div class="font-bold">Gallery</div>
            </a>

            <a @click="toggleSlideoverSettings" :class="[
                'w-full flex flex-col justify-center items-center gap-[6px] p-[24px_12px] border-b border-gray-200 cursor-pointer text-[var(--txtcolor)] hover:bg-gray-100',
                isSettingsActive ? 'bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-white' : ''
            ]">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[40px] h-[40px]" viewBox="0 0 24 24"
                        fill="currentColor">
                        <path
                            d="M12 16c2.206 0 4-1.794 4-4s-1.794-4-4-4-4 1.794-4 4 1.794 4 4 4zm0-6c1.084 0 2 .916 2 2s-.916 2-2 2-2-.916-2-2 .916-2 2-2z" />
                        <path
                            d="m2.845 16.136 1 1.73c.531.917 1.809 1.261 2.73.73l.529-.306A8.1 8.1 0 0 0 9 19.402V20c0 1.103.897 2 2 2h2c1.103 0 2-.897 2-2v-.598a8.132 8.132 0 0 0 1.896-1.111l.529.306c.923.53 2.198.188 2.731-.731l.999-1.729a2.001 2.001 0 0 0-.731-2.732l-.505-.292a7.718 7.718 0 0 0 0-2.224l.505-.292a2.002 2.002 0 0 0 .731-2.732l-.999-1.729c-.531-.92-1.808-1.265-2.731-.732l-.529.306A8.1 8.1 0 0 0 15 4.598V4c0-1.103-.897-2-2-2h-2c-1.103 0-2 .897-2 2v.598a8.132 8.132 0 0 0-1.896 1.111l-.529-.306c-.924-.531-2.2-.187-2.731.732l-.999 1.729a2.001 2.001 0 0 0 .731 2.732l.505.292a7.683 7.683 0 0 0 0 2.223l-.505.292a2.003 2.003 0 0 0-.731 2.733zm3.326-2.758A5.703 5.703 0 0 1 6 12c0-.462.058-.926.17-1.378a.999.999 0 0 0-.47-1.108l-1.123-.65.998-1.729 1.145.662a.997.997 0 0 0 1.188-.142 6.071 6.071 0 0 1 2.384-1.399A1 1 0 0 0 11 5.3V4h2v1.3a1 1 0 0 0 .708.956 6.083 6.083 0 0 1 2.384 1.399.999.999 0 0 0 1.188.142l1.144-.661 1 1.729-1.124.649a1 1 0 0 0-.47 1.108c.112.452.17.916.17 1.378 0 .461-.058.925-.171 1.378a1 1 0 0 0 .471 1.108l1.123.649-.998 1.729-1.145-.661a.996.996 0 0 0-1.188.142 6.071 6.071 0 0 1-2.384 1.399A1 1 0 0 0 13 18.7l.002 1.3H11v-1.3a1 1 0 0 0-.708-.956 6.083 6.083 0 0 1-2.384-1.399.992.992 0 0 0-1.188-.141l-1.144.662-1-1.729 1.124-.651a1 1 0 0 0 .471-1.108z" />
                    </svg>
                </div>
                <div class="font-bold">Settings</div>
            </a>

            <a @click="toggleSlideoverAdvanced" :class="[
                'w-full flex flex-col justify-center items-center gap-[6px] p-[24px_12px] border-b border-gray-200 cursor-pointer text-[var(--txtcolor)] hover:bg-gray-100',
                isAdvancedActive ? 'bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)] text-white' : ''
            ]">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-[40px] h-[40px]" viewBox="0 0 30 30" fill="currentColor">
                        <path
                            d="M3.52371 29.8835C2.16402 29.5273 1.06868 28.4529 0.675723 27.0901L0.540039 26.6195V15.0296C0.540039 5.51441 0.554182 3.38178 0.619239 3.11613C0.662824 2.93817 0.818266 2.54105 0.964752 2.23366C1.19241 1.75587 1.3047 1.60206 1.73858 1.17392C2.31917 0.601063 2.83572 0.30632 3.594 0.115285C4.04961 0.000504528 4.26231 -0.00190716 13.7425 0.000124267L23.4256 0.00226922L26.444 2.98591L29.4623 5.96956L29.4443 16.3534L29.4263 26.7372L29.2872 27.149C28.808 28.5681 27.7924 29.5352 26.4102 29.8887C25.9786 29.9991 25.6495 30.0023 14.959 29.9991C4.32257 29.9961 3.93784 29.992 3.52371 29.8835ZM26.1212 27.3927C26.5124 27.2006 26.8224 26.8289 26.9227 26.4316C26.9651 26.2632 26.985 23.2079 26.985 16.7986V7.41218L24.5876 7.39684L22.1902 7.38146L22.1748 4.92522L22.1593 2.46899L13.1734 2.46942L4.18748 2.46985L3.89331 2.60713C3.73153 2.68262 3.50138 2.85287 3.38185 2.98545C2.95212 3.46217 2.98144 2.58269 2.98144 15.0012C2.98144 27.0553 2.96601 26.4458 3.28598 26.9191C3.46529 27.1842 3.80202 27.4148 4.14582 27.5078C4.36615 27.5674 6.6075 27.5805 15.1008 27.5716L25.7788 27.5605L26.1212 27.3927ZM11.6665 22.6012C11.6493 22.5735 12.5475 19.4308 13.6623 15.6175C14.7771 11.8041 15.6892 8.67565 15.6892 8.66525C15.6892 8.65484 16.0731 8.64635 16.5423 8.64635C17.0115 8.64635 17.3948 8.65295 17.3941 8.66105C17.3941 8.66915 16.4717 11.813 15.3459 15.6473L13.2989 22.6189L12.4983 22.6352C12.0579 22.6442 11.6836 22.6289 11.6665 22.6012ZM7.74981 18.3791C6.27908 17.4238 5.04141 16.6137 4.99941 16.5788C4.94511 16.5337 4.923 16.3252 4.923 15.858V15.2005L7.63521 13.3501C9.12694 12.3324 10.3713 11.4909 10.4003 11.4801C10.4303 11.469 10.4532 11.8915 10.4532 12.4555V13.4506L8.48515 14.6498C7.40275 15.3094 6.52247 15.8541 6.52902 15.8603C6.53545 15.8665 7.4013 16.3655 8.45293 16.9692C9.50455 17.5729 10.3855 18.087 10.4106 18.1116C10.4354 18.1363 10.4487 18.5974 10.4401 19.1363L10.4238 20.116L7.74981 18.3791ZM18.7618 18.9925L18.7777 17.9841L20.71 16.8799L22.6424 15.7758L22.3279 15.5766C22.155 15.4671 21.2788 14.9307 20.3809 14.3847L18.7483 13.3919V12.4017C18.7483 11.857 18.762 11.4114 18.7783 11.4114C18.7946 11.4114 20.0259 12.2369 21.5139 13.2458L24.2196 15.0802V15.7638V16.4474L21.6491 18.1212C20.2353 19.0418 19.0036 19.8413 18.912 19.8979L18.7455 20.0009L18.7618 18.9925Z" />
                    </svg>

                </div>
                <div class="font-bold">Advanced</div>
            </a>

            <a :href="$page.props.engine_url + '?code=' + $page.props.questionnaire.code + '&preview=1&gtq=1' + previewLangcParam + '&id=id-test'"
                target="_blank"
                class="w-full flex flex-col justify-center items-center gap-[6px] p-[24px_12px] border-b border-gray-200 cursor-pointer text-[var(--txtcolor)] hover:bg-gray-100"
                :class="{ 'bg-[var(--mcolor)] hover:!bg-[var(--mcolor2)]': ($Menu.active == 'Preview') }">
                <div>
                    <!-- <svg xmlns="http://www.w3.org/2000/svg" class="w-[40px]" viewBox="0 0 24 24"
                        style="fill: rgba(51, 51, 51, 1);">
                        <path
                            d="M5.536 21.886a1.004 1.004 0 0 0 1.033-.064l13-9a1 1 0 0 0 0-1.644l-13-9A.998.998 0 0 0 5 3v18a1 1 0 0 0 .536.886zM7 4.909 17.243 12 7 19.091V4.909z">
                        </path>
                    </svg> -->
                    <svg width="50" height="50" viewBox="0 0 1000 1000" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M900 211.5V788.5C894.833 805 877.7 837.2 850.5 834C823.3 830.8 371.167 832.667 148.5 834C132.833 831.833 101.2 819.3 100 786.5V213C101.667 197.333 117.1 165.8 153.5 165H850.5C867 166.667 900 178.3 900 211.5ZM845 211.5C850.6 211.9 851.667 217 851.5 219.5V296.5H148.5V217.5C148.5 211.5 154.833 211 158 211.5H845ZM851.5 345V779C851.667 781.667 850.7 787 845.5 787H152.584C150.89 786.667 147.5 785.3 147.5 782.5C147.5 779.7 148.167 489.667 148.5 345H851.5ZM413 469.5C416.2 442.3 442.667 440.5 455.5 443L616 541C633.2 555 623.167 577.167 616 586.5L455.5 685.5C428.3 691.1 415.833 670.833 413 660V469.5ZM461.5 625.5V503.5L560.5 563.5L461.5 625.5Z"
                            fill="#1E1E1C" />
                    </svg>

                </div>
                <div class="font-bold">Full Preview</div>
            </a>

        </div>
        <div id="slideover-container" class="w-[480px] h-full fixed inset-0 invisible pt-[70px] z-[-1]">
            <div @click="toggleSlideover" id="slideover-bg"
                class="w-full h-full duration-500 ease-out transition-all inset-0 absolute bg-gray-900 opacity-0 hidden poiner-events-none">
            </div>
            <div id="slideover"
                class="w-[480px] pl-[140px] bg-white h-full absolute left-0 duration-300 ease-out transition-all translate-x-[-100%]">

                <GalleryVue :showGallery="showGallery" />
                <div @click="toggleSlideover"
                    class="absolute cursor-pointer text-gray-600 top-0 w-full h-10 flex place-content-end right-0 p-3">
                    <!-- <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg> -->
                </div>
            </div>
        </div>

        <div id="slideover-container-settings" class="w-[700px] h-full fixed inset-0 invisible pt-[50px] z-[-1]">
            <div @click="toggleSlideoverSettings" id="slideover-bg-settings"
                class="w-full h-full duration-500 ease-out transition-all inset-0 absolute bg-gray-900 opacity-0 hidden poiner-events-none">
            </div>
            <div id="slideover-settings"
                class="w-[700px] pl-[140px] bg-white h-full absolute left-0 duration-300 ease-out transition-all translate-x-[-100%]">

                <Setting :questions="$page.props.questions" :showSetting="showSetting" @closeSettings="toggleSlideoverSettings" />

                <!-- <div @click="toggleSlideoverSettings"
                    class="absolute cursor-pointer text-gray-600 top-0 w-full h-10 flex place-content-end left-6 p-8 text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </div> -->
            </div>
        </div>

        <div id="slideover-container-advanced" class="w-[700px] h-full fixed inset-0 invisible pt-[50px] z-[-1]">
            <div @click="toggleSlideoverAdvanced" id="slideover-bg-advanced"
                class="w-full h-full duration-500 ease-out transition-all inset-0 absolute bg-gray-900 opacity-0 hidden poiner-events-none">
            </div>
            <div id="slideover-advanced"
                class="w-[700px] pl-[140px] bg-white h-full absolute left-0 duration-300 ease-out transition-all translate-x-[-100%]">

                <Advanced :showSetting="showSetting" />
                <div @click="toggleSlideoverAdvanced"
                    class="absolute cursor-pointer text-gray-600 top-0 w-full h-10 flex place-content-end right-0 p-3">

                </div>
            </div>
        </div>

    </div>


</template>

<script setup>
import { ref, onMounted, computed, watch, onUnmounted } from 'vue'
import GalleryVue from '@/Pages/Questions/Gallery/Gallery.vue'
import Setting from "@/Pages/Questions/Setting.vue";
import Advanced from "@/Pages/Questions/Advanced.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { changes } from '../../js/helpers.js';

const page = usePage();

// Use a reactive ref for current language to avoid page reloads
// Initialize from URL params or page props
const getInitialLanguageCode = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const langc = urlParams.get('langc');
    if (langc) return langc;
    return page.props?.language?.code || ' ';
};
const currentLanguageCode = ref(getInitialLanguageCode());

// Listen for URL changes (browser back/forward or language switching from other components)
let handlePopState = null;
let handleLanguageChanged = null;

onMounted(() => {
    handlePopState = () => {
        const urlParams = new URLSearchParams(window.location.search);
        const langc = urlParams.get('langc');
        currentLanguageCode.value = langc || page.props?.language?.code || ' ';
    };
    
    window.addEventListener('popstate', handlePopState);
    
    // Also listen for custom language change events from Index.vue
    handleLanguageChanged = (event) => {
        currentLanguageCode.value = event.detail.languageCode;
    };
    window.addEventListener('language-changed', handleLanguageChanged);
});

onUnmounted(() => {
    if (handlePopState) {
        window.removeEventListener('popstate', handlePopState);
    }
    if (handleLanguageChanged) {
        window.removeEventListener('language-changed', handleLanguageChanged);
    }
});

// Get the langc parameter for preview URLs if a translated language is active
const previewLangcParam = computed(() => {
    const lang = currentLanguageCode.value;
    // If it's the original language (empty string or space), return empty string
    if (!lang || lang === ' ' || lang.trim() === '') {
        return '';
    }
    // Otherwise, return the langc parameter
    return '&langc=' + lang;
});

const $Menu = ref({
    active: 'Questionnaire'
})
const activeClass = (current) => {
    return route().current(current) ? "text-white bg-ssr-yellow1 rounded-md" : "liLink hover:text-slate-400"
}

const isGalleryActive = ref(false);
const isSettingsActive = ref(false);
const isAdvancedActive = ref(false);
const showSetting = ref(false);
const showGallery = ref(false);
const showAdvanced = ref(false);
const closeSettings = ref(false);


watch(isGalleryActive, (m) => {
    if (m) {
        document.querySelectorAll(".donModal").forEach((modal) => {
            modal.dispatchEvent(new CustomEvent("sidebar", { detail: { open: true, target: 'gallery' } }))
        })
    } else {
        document.querySelectorAll(".donModal").forEach((modal) => {
            modal.dispatchEvent(new CustomEvent("sidebar", { detail: { open: false, target: 'gallery' } }))
        })
    }
})


// function toggleSlideover() {
//     if (isSettingsActive.value) {
//         isSettingsActive.value = false
//         showSetting.value = false
//     }
//     isGalleryActive.value = !isGalleryActive.value
// }

// function toggleSlideoverSettings() {
//     if (isGalleryActive.value) {
//         isGalleryActive.value = false
//     }
//     isSettingsActive.value = !isSettingsActive.value
//     showSetting.value = isSettingsActive.value
// }
// For Gallery Slideover
function toggleSlideover() {
    const settingsContainer = document.getElementById('slideover-container-settings');
    const advancedContainer = document.getElementById('slideover-container-advanced');
    if (settingsContainer && !settingsContainer.classList.contains('invisible')) {
        toggleSlideoverSettings(); // Close settings if it's open
    }

    if (advancedContainer && !advancedContainer.classList.contains('invisible')) {
        toggleSlideoverAdvanced();
    }

    isGalleryActive.value = !isGalleryActive.value;
    document.getElementById('slideover-container').classList.toggle('invisible');
    document.getElementById('slideover-bg').classList.toggle('opacity-0');
    document.getElementById('slideover-bg').classList.toggle('opacity-50');
    document.getElementById('slideover').classList.toggle('translate-x-[0%]');
    
    // Emit global gallery event to close other gallery modals
    if (isGalleryActive.value) {
        window.dispatchEvent(new CustomEvent('gallery-state-change', { 
            detail: { 
                action: 'open', 
                source: 'sidebar-gallery',
                type: 'sidebar' 
            } 
        }))
    } else {
        // Emit close event when sidebar gallery is closed
        window.dispatchEvent(new CustomEvent('gallery-state-change', { 
            detail: { 
                action: 'close', 
                source: 'sidebar-gallery',
                type: 'sidebar' 
            } 
        }))
    }
    
    if (isGalleryActive.value) {
        console.log('Sidebar opened:', 'gallery');
    }
    window.dispatchEvent(new CustomEvent('sidebar-state-change', {
        detail: {
            sidebar: 'gallery',
            isOpen: isGalleryActive.value
        }
    }));
}

// For Settings Slideover
function toggleSlideoverSettings() {
    const galleryContainer = document.getElementById('slideover-container');
    const advancedContainer = document.getElementById('slideover-container-advanced');
    if (galleryContainer && !galleryContainer.classList.contains('invisible')) {
        toggleSlideover(); // Close gallery if it's open
    }

    if (advancedContainer && !advancedContainer.classList.contains('invisible')) {
        toggleSlideoverAdvanced();
    }

    isSettingsActive.value = !isSettingsActive.value;
    showSetting.value = isSettingsActive.value;

    document.getElementById('slideover-container-settings').classList.toggle('invisible');
    document.getElementById('slideover-bg-settings').classList.toggle('opacity-0');
    document.getElementById('slideover-bg-settings').classList.toggle('opacity-50');
    document.getElementById('slideover-settings').classList.toggle('translate-x-[0%]');
    
    if (isSettingsActive.value) {
        console.log('Sidebar opened:', 'settings');
    }
    window.dispatchEvent(new CustomEvent('sidebar-state-change', {
        detail: {
            sidebar: 'settings',
            isOpen: isSettingsActive.value
        }
    }));
}

// For Advanced Slideover
function toggleSlideoverAdvanced() {
    const galleryContainer = document.getElementById('slideover-container');
    const settingsContainer = document.getElementById('slideover-container-settings');

    if (galleryContainer && !galleryContainer.classList.contains('invisible')) {
        toggleSlideover();
    }

    // Close settings if open
    if (settingsContainer && !settingsContainer.classList.contains('invisible')) {
        toggleSlideoverSettings();
    }

    isAdvancedActive.value = !isAdvancedActive.value;
    showAdvanced.value = isAdvancedActive.value;

    document.getElementById('slideover-container-advanced').classList.toggle('invisible');
    document.getElementById('slideover-bg-advanced').classList.toggle('opacity-0');
    document.getElementById('slideover-bg-advanced').classList.toggle('opacity-50');
    document.getElementById('slideover-advanced').classList.toggle('translate-x-[0%]');
    
    if (isAdvancedActive.value) {
        console.log('Sidebar opened:', 'advanced');
    }
    window.dispatchEvent(new CustomEvent('sidebar-state-change', {
        detail: {
            sidebar: 'advanced',
            isOpen: isAdvancedActive.value
        }
    }));
}

// Global gallery event listener to close sidebar gallery when others open
const handleGlobalGalleryEvent = (event) => {
    const { action, source, type } = event.detail
    
    // If another gallery is opening and it's not the sidebar gallery, close the sidebar gallery
    if (action === 'open' && source !== 'sidebar-gallery' && isGalleryActive.value) {
        toggleSlideover() // Close the sidebar gallery
    }
}

onMounted(() => {
    // Listen for global gallery events
    window.addEventListener('gallery-state-change', handleGlobalGalleryEvent)
})

onUnmounted(() => {
    // Clean up event listener
    window.removeEventListener('gallery-state-change', handleGlobalGalleryEvent)
})
</script>