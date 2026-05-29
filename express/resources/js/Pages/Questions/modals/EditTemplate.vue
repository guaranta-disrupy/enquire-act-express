<template>
    <jet-dialog-modal :show="modal.add_edit" :closeable="false" maxWidth="7xl" @close="close()">
        <template #title>
            <span v-if="modal.method == 'add'">Add Question</span>
            <span v-else-if="modal.method == 'copy'">Copy Question</span>
            <span v-else-if="modal.method == 'copy_to'">Copy Question To</span>
            <span v-else>Update Question</span>
        </template>
        
        <template #content v-if="modal.method == 'copy'">
            <span>Are you sure you want to continue this action?</span>
        </template>

        <template #content v-else-if="modal.method == 'copy_to'">
            <span>Project Name:</span>
            <select v-model="copyProj_id"  @change="getQnr()" class="w-full truncate form-select cursor-pointer text-gray-500 rounded-md border-1 border-gray-300 focus:border-ssr-blue3  focus:ring-1 focus:ring-ssr-blue3">
                <option v-for="(proj,index) in $page.props.projects" :key="index" :value="index">{{proj.name}}</option>
            </select>

            <span v-if="copyToQuestionnaire.length > 0">Questionnaire:</span>
            <select v-if="copyToQuestionnaire.length > 0" v-model="copyDataToProject.qnrCodeToCopy" class="w-full truncate form-select cursor-pointer text-gray-500 rounded-md border-1 border-gray-300 focus:border-ssr-blue3  focus:ring-1 focus:ring-ssr-blue3">
                <option v-for="(qnr,index) in copyToQuestionnaire" :key="index" :value="qnr.code">{{qnr.name}}</option>
            </select>

        </template>
        <template #content v-else>

            <jet-label class="mb-2">
                Question Instruction 

                <!-- <button class="ckbtn float-right -mt-1 pr-3 pl-3 bg-ssr-blue3 border-transparent font-bold text-xs text-white tracking-widest hover:bg-ssr-bluehover focus:outline-none" :class="enableCK" @click="enableCKEditor()">
                    Editor
                </button> -->

                <div class="float-right mb-2">  
                    <label for="toogleA" class="flex items-center cursor-pointer">
                        <!-- toggle -->
                        <div class="relative">
                        <!-- input -->
                        <input id="toogleA" type="checkbox" v-model="enableEditor" class="sr-only" />
                        <!-- line -->
                        <div class="w-10 h-4 bg-gray-200 rounded-full shadow-inner"></div>
                        <!-- dot -->
                        <div class="dot absolute w-6 h-6 bg-white rounded-full shadow -left-1 -top-1 transition"></div>
                        </div>
                        <!-- label -->
                        <div class="ml-3 text-gray-600">
                            Editor
                        </div>
                    </label>                
                </div>
            </jet-label>
            
            <ckeditor :editor="editor" v-model="form.question" v-if="enableEditor == true"> </ckeditor>
            <s4-text-area v-else v-model="form.question" class="w-full h-40" color="ssrGray"></s4-text-area>
            <jet-input-error :message="form.errors.question" />
            <div class="grid grid-cols-6 gap-1">
                <div class="mt-3 col-span-3">
                    <jet-label>Question Category</jet-label>
                    <s4-input-select class="w-full" color="ssrGray" @change="updateCategoryList()" :lists="question_types.categories" v-model="category"></s4-input-select>
                </div>
                <div class="mt-3 col-span-3">
                    <jet-label>Question Type</jet-label>
                    <div class="flex gap-1">
                        <s4-input-select class="w-full" color="ssrGray" 
                            @change="updateSetup()"
                            :lists="question_types.values.filter(type => type.category == category)" 
                            v-model="form.question_type_id">
                        </s4-input-select>
                        
                        <s4-button :theme="'theme2'">
                            <span class="pl-3 pr-3">Templates</span>
                        </s4-button> 
                    </div>
                             
                </div>        
            </div>    
            
            <div class="mt-5">               
                <multiple-choice :modal="modal" v-if="form.question_type_id == 2" />
                <image-selection :modal="modal" v-else-if="form.question_type_id == 3" />
                <single-textbox :modal="modal" v-else-if="form.question_type_id == 4" />
                <multiple-textbox :modal="modal" v-else-if="form.question_type_id == 5" />
                <open-ended :modal="modal" v-else-if="form.question_type_id == 6" />
                <constant-sum :modal="modal" v-else-if="form.question_type_id == 7" />
                <slider :modal="modal" v-else-if="form.question_type_id == 8" />
                <ranking :modal="modal" v-else-if="form.question_type_id == 9" />
                <star-rating :modal="modal" v-else-if="form.question_type_id == 10" />
                <matrix-choice :modal="modal" v-else-if="form.question_type_id == 11" />
                <matrix-multi :modal="modal" v-else-if="form.question_type_id == 12" />
                <memory-test-desc :modal="modal" v-else-if="form.question_type_id == 13" />
                <memory-test-comparison :modal="modal" v-else-if="form.question_type_id == 14" />
            </div>
        </template>

        <template #footer>
            <s4-button :theme="'theme2'" class="float-left" :disabled="form.processing">
                <span class="pl-3 pr-3">
                    Save as template
                </span>
            </s4-button>
            <s4-button :theme="'ssrCancel'" color="ssrGray" class="mr-3" @click="close()">
                Cancel
            </s4-button>
            <s4-button :theme="'ssrOrange'" :disabled="form.processing" @click="AddEditQuestion()">
                <template v-if="form.processing">
                    <span class="pl-3 pr-3"><font-awesome-icon :icon="faIcon.spinner" spin /> Wait</span>
                </template>
                <template v-else-if="modal.method == 'edit'">
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.edit" />--> 
                        Update
                    </span>
                </template>
                 <template v-else-if="modal.method == 'copy'">
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.copy" />-->
                        Copy
                    </span>
                </template>
                <template v-else>
                    <span class="pl-3 pr-3">
                        <!--<font-awesome-icon :icon="faIcon.save" /> -->
                        Save
                    </span>
                </template>
            </s4-button>
        </template>
    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue';
import JetLabel from '@/Jetstream/Label.vue';
import JetInputError from '@/Jetstream/InputError.vue'
import DropDownFilter from '@/S4/DropDown.vue';
import S4Button from '@/S4/Button.vue';
import S4TextArea from '@/S4/TextArea.vue';
import S4InputSelect from '@/S4/InputSelect.vue';
import MultipleChoice from'./types/MultipleChoice.vue';
import ImageSelection from'./types/ImageSelection.vue';
import SingleTextbox from './types/SingleTextbox.vue';
import MatrixChoice from './types/MatrixChoice.vue';
import MatrixMulti from'./types/MatrixMulti.vue';
import ConstantSum from'./types/ConstantSum.vue';
import MemoryTestComparison from './types/MemoryTestComparison.vue';
import MemoryTestDesc from './types/MemoryTestDesc.vue';
import MultipleTextbox from'./types/MultipleTextbox.vue';
import OpenEnded from'./types/OpenEnded.vue';
import Ranking from './types/Ranking.vue';
import Slider from './types/Slider.vue';
import StarRating from './types/StarRating.vue';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faEdit, faSpinner, faSave, faCode } from '@fortawesome/free-solid-svg-icons'
import ClassicEditor from '@ckeditor/ckeditor5-build-classic'

export default {
    name:'add-edit-question',
    components:{
    JetDialogModal,
    JetLabel,
    DropDownFilter,
    S4Button,
    S4TextArea,
    S4InputSelect,
    MultipleChoice,
    ImageSelection,
    MatrixChoice,
    SingleTextbox,
    MatrixMulti,
    ConstantSum,
    MemoryTestComparison,
    MemoryTestDesc,
    MultipleTextbox,
    OpenEnded,
    Ranking,
    Slider,
    StarRating,
    JetInputError,
    FontAwesomeIcon,
    ClassicEditor,
},
    props:{
        modal:{
            default:{
                add_edit:false,
                method:'add',
                data:null,
            }
        },
    },
    computed:{
        enableCK(){
                return "relative inline-flex items-center px-2 py-1 bg-orange-400 border border-orange-400 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-500 active:bg-yellow-900 focus:outline-none focus:border-yellow-900 focus:shadow-outline-yellow transition ease-in-out duration-150";
            },
    },
    // emits: ["showGallery"],
    data() {
        return{
            faIcon: {
                save: faSave,
                spinner: faSpinner,
                edit: faEdit,
                code:faCode,
            },
            form: this.$inertia.form({
                question_type_id:1,
                question:'',
                setup:{},
                choices:[],
                rows:[],
                columns:[],
                items:[],
                item1:[],
                item2:[],
                values:[]
            }, {
                resetOnSuccess:true,
            }),
            category: 'Description',
            question_types: {
                values: [],
                categories: []
            },
            copyData:this.$inertia.form({
                questionToCopy:null,
                copyQuestionTo:null,
            }),
            copyProj_id:null,
            copyDataToProject:this.$inertia.form({
                copyCodeTo:null, //Where qnr will be copied into what project
                codeToCopy:null, //Code to copy
                qnrCodeTo:null, //where question will be copied to what questionnaire
                qnrCodeToCopy:null, //question code to copy
            }),
            copyToQuestionnaire:[],
            editor:ClassicEditor,
            enableEditor:true,
        }
    },
    created() {
        this.initialized()
    },
    methods:{
        initialized() {
            this.$page.props.question_types.forEach(type => {
                //type lists
                this.question_types.values.push({
                    value: type.id,
                    text: type.name,
                    category: type.category //for filtering lists
                })
                // category list
                if (!this.$helpers.valExists(this.question_types.categories, {key: 'value', value: type.category})) {
                    this.question_types.categories.push({
                        value: type.category,
                        text: type.category
                    })
                }
            })
        }, 
        updateSetup() {
            //for edit old questions
            if (('boxStyle' in this.$questionvar.add_edit.setup) == false) {
                this.$questionvar.add_edit.setup['boxStyle'] = {
                    'size': 'md',
                    'max-width': '100%'
                }
                this.$questionvar.add_edit.setup['style'] = {
                    'max-width': '100%',
                    'width': '100%',
                    'height': '200px'
                }
            }

            this.$nextTick(() => {
                var default_setup = this.$page.props.question_types.find(question_type => question_type.id == this.form.question_type_id).setup
                for (const key in default_setup) {
                    //add setting if not exist
                    if (!Object.hasOwnProperty.call(this.$questionvar.add_edit.setup, key)) {
                        this.$questionvar.add_edit.setup[key] = default_setup[key]
                    }else {
                        if (typeof(this.$questionvar.add_edit.setup[key]) != 'number' || typeof(this.$questionvar.add_edit.setup[key]) != 'string') {
                            //add setting deep
                            for (const skey in default_setup[key]) {
                                if (!Object.hasOwnProperty.call(this.$questionvar.add_edit.setup[key], skey)) {
                                    this.$questionvar.add_edit.setup[key][skey] = default_setup[key][skey]
                                }
                            }
                        }
                    }
                }   
            })  
        },
        updateCategoryList(type_id = -1) {
            if (type_id == -1) {
                this.$nextTick(() => {
                    this.form.question_type_id = this.$page.props.question_types.find(type => type.category == this.category).id
                })
            }else {
                this.category = this.$page.props.question_types.find(type => type.id == type_id).category
            }
            
            //update setup
            this.updateSetup()  
        },
        close(){
            this.form.reset()
            this.$emit('close')
        }, 
        enableCKEditor(){
            this.enableEditor = !this.enableEditor;
        },
        getQnr(){
            //console.log(this.copyProj_id+1,'id')
            let parent = this;
            let copyId = this.copyProj_id+1;
            _.map(this.$page.props.questionnaires,function(questionnaire,qindex){
                if (questionnaire.project_id == copyId){
                    parent.copyDataToProject.copyCodeTo = parent.$page.props.projects[parent.copyProj_id].code;
                    parent.copyToQuestionnaire.push(questionnaire)
                }
            })
        },
        AddEditQuestion(){
            let parent = this;
            /* let writeinSetting = {
                type : 1,
                placeholder : 'Please specify',
                validate : {
                    type : this.$questionvar.add_edit.setup.writeinType,
                    min : this.$questionvar.add_edit.setup.writeinMax,
                    max : this.$questionvar.add_edit.setup.writeinMin,
                    errorMsg : this.$questionvar.add_edit.setup.writeinErrorMsg,
                }
            }; */
            if (this.modal.method == 'add') {
                var web_route = 'questions.store'
                var params = [
                    this.$page.props.project.code, 
                    this.$page.props.questionnaire.code
                ]
            } 
            else if(this.modal.method == 'copy')
            {
                var params = [
                    this.$page.props.project.code, 
                    this.$page.props.questionnaire.code,
                    this.modal.data.id,
                ]
                this.copyData.post(route('question.copy',params),{
                    preserveScroll:true,
                    onSuccess:() => {
                        this.$toast.success('Question Copied',{
                            timeout:2000
                        });
                        this.close()
                    },
                })
            }
            else if(this.modal.method == 'copy_to'){
                var params = [
                    this.$page.props.project.code,
                    this.$page.props.questionnaire.code,
                    this.modal.data.id,
                ]
                this.copyDataToProject.post(route('question.copy_to',params),{
                    preserveScroll:true,
                    onSuccess:()=>{
                        this.$toast.success('Question Copied',{
                            timeout:2000
                        });
                        this.close();
                    }
                })
            }
            else {
                var web_route = 'questions.update'
                var params = [
                    this.$page.props.project.code, 
                    this.$page.props.questionnaire.code,
                    this.modal.data.id
                ]
            }

            /* if(this.$questionvar.add_edit.setup.writeinRow == 1){
                console.log(writeinSetting,"writein")
                this.$questionvar.add_edit.rows.push({name:this.$questionvar.add_edit.setup.writeinRowChoice,value:this.$questionvar.add_edit.rows.length,wc:writeinSetting,optional:true   })
            } */
            this.form.post(route(web_route, params), {
                preserveScroll: true,
                onSuccess: () => {
                    this.$toast.success(`Question ${this.modal.method == 'add' ? 'added': 'updated'}`, {
                        timeout: 2000
                    })
                    this.close()
                },
                onError: () => {
                    //code
                },
                onFinish: () => {
                    //code
                }
            })
        },
        /* showGallery() {
            this.$emit('showGallery')
        } */
    },
    watch:{
        modal:{
            deep: true,
            handler(modal){
                this.$questionvar.add_edit = this.form //global form for child components
                //assign values to edit
                if (modal.method == 'edit' && modal.add_edit == true) {
                    for (const key in this.form) {
                        // prevent assigning erros from ths.form
                        if (Object.hasOwnProperty.call(modal.data, key)) {
                            this.form[key] = this.$helpers.assign(modal.data[key])
                        }
                    }
                    this.updateCategoryList(this.form.question_type_id)
                }else {
                    this.updateCategoryList(1)
                }
                
            }
        }
    }
}
</script>

<style>
.ckbtn {
    border:none;   
    border-radius:0.3rem; 
}

input:checked ~ .dot {
  transform: translateX(100%);
  background-color: #759fd1;
}
</style>