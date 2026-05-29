import { usePage } from "@inertiajs/vue3";


export function convertVariable(obj) {
    if (!obj || !obj.variable || typeof obj.variable !== "string") {
        console.warn("convertVariable: invalid object passed", obj);
        return null;
    }

    let Qlist = getQsInfo();

    let vars = obj.variable.split("_");
    let QTAG = vars[0];

    let QuestionDetails = Qlist.find((q) => {
        return QTAG == q.TAG;
    });

    return QuestionDetails || null;
}




export function getQsInfo(filterQT = []) {
    let list = []
    let qs = usePage().props.questions

    qs = qs.filter((obj)=>{
       // alert(!filterQT.includes(obj.question_type_id)+"="+obj.question_type_id)
        return (!filterQT.includes(obj.question_type_id))
    })
    qs.forEach((data)=>{
        let num = parseInt(data.order) + parseInt(usePage().props.questionnaire.setup.variable_start) - 1
        list.push({
            TAG : "Q"+num,
            TAG_NUM : num,
            data : data
        })
    })
    return list
}


export function getQInfo(order) {
        let data = usePage().props.questions.find((obj)=>{
            return obj.order == order
        })
        let num = parseInt(data.order) + parseInt(usePage().props.questionnaire.setup.variable_start) - 1
        return {
            TAG : "Q"+num,
            TAG_NUM : num,
            data : data
        }
}