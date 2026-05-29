import { usePage } from "@inertiajs/vue3";

export function useQuestionData(orig = true) {
        let list = []

        let pqs = usePage().props.questions

        pqs.forEach((question, index)=> {
            let val = null
            if(!orig){
                val = parseInt(question.order) + parseInt(parseInt(usePage().props.questionnaire.setup.variable_start)) - 1
            }else{
                val = question.order
            }
            list.push({
                value: val,
                text: "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs'>Q"+(parseInt(question.order) + parseInt(parseInt(usePage().props.questionnaire.setup.variable_start)) - 1) + "</span> " +question.question
            })
        });
        return list
}

export function useQuestionChoices(order) {
        
        let pqs = usePage().props.questions
        let ques = pqs.find((obj)=>{
            return obj.order == order
        })
        let temp = {}
        switch (ques.question_type_id) {
            case 2: // MULTIPLE-CHOICE - multiple choice
            case 3: // MULTIPLE-CHOICE - image selection    
                temp = {
                    "type" : "choice",
                    "isMultiple" : false,
                    "TAG" : "Q" +(parseInt(ques.order ) + parseInt(usePage().props.questionnaire.setup.variable_start) - 1), 
                    "choices" : []
                }
                if (ques.setup.selectMultiple) { // multiple answer
                    temp.isMultiple = true
                }
                ques.choices.forEach((choice, index) => {
                    let c = {
                        value:(choice?.index) ? choice.index : index + 1,
                        text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(choice?.index || index + 1)+"</span> "+(choice?.name || 'Choice')
                    }
                    if(ques.setup.selectMultiple){
                        c['selection2'] = [
                        {
                            value: choice.checkboxScore.active,
                            text:  "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+choice.checkboxScore.active+"</span> Selected"
                        },
                        {
                            value: choice.checkboxScore.inactive,
                            text:  "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+choice.checkboxScore.inactive+"</span> Selected"
                        }
                    ]
                    }

                    temp.choices.push(c);

                    

                });

                break;
            case 4: // TEXTBOX - single textbox
                temp = {
                    "type" : "input",
                    "isMultiple" : false,
                    "TAG" : "Q" +(parseInt(ques.order ) + parseInt(usePage().props.questionnaire.setup.variable_start) - 1), 
                    "choices" : []
                }

                break;
            case 5: // TEXTBOX - multiple textbox
            case 7: // TEXTBOX - constant sum
                temp = {
                    "type" : "input",
                    "isMultiple" : true,
                    "TAG" : "Q" +(parseInt(ques.order ) + parseInt(usePage().props.questionnaire.setup.variable_start) - 1), 
                    "choices" : []
                }
                ques.items.forEach((item, index) => {
                    temp.choices.push({
                        value:(item?.index ? item.index : index + 1),
                        text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(item?.index || index + 1)+"</span> "+(item?.name || 'Item')  
                    });
                });
                break;
            case 8: // RATING - slider
            case 15: // RATING - button rating
            case 16: // RATING - segmentation  
                temp = {
                    "type" : "rating",
                    "isMultiple" : true,
                    "TAG" : "Q" +(parseInt(ques.order ) + parseInt(usePage().props.questionnaire.setup.variable_start) - 1), 
                    "choices" : []
                }
                ques.items.forEach((item, index) => {
                    temp.choices.push({
                        value:(item?.index ? item.index : index + 1),
                        text : "<span class='bg-[var(--txtcolor)] rounded p-[2px] text-white text-xs min-w-[20px] inline-block text-center'>"+(item?.index || index + 1)+"</span> "+(item?.name || 'Item')
                    });
                });               
                break;
        }
        return temp;
}