import { useToast } from "vue-toastification";
import "vue-toastification/dist/index.css";
import State from './State.js';
import VarState from './Variable.js';
export default function () {
    const assign = (data) => {
        return JSON.parse(JSON.stringify(data));
    };

    const datetime = (time) => {
        return moment(time).tz(timezone).format(time_format)
    }

    const toast = useToast({
        timeout: 1000,
        position: "bottom-right",
        hideProgressBar: true,
        closeOnClick: true,
        transition: "Vue-Toastification__fade",
    });

    const hasString = (str, needles) => {
        return needles.some(entry => str.includes(entry))
    };

    const hasImage = (str) => {
        return str ? str.split('<img ').length > 1 : false
    }

    const $state = new State();

    const $varState = new VarState();

    const $testState = new State();

    const sanitize = (text) => {
        let decoder = document.createElement('div');
        decoder.style.display = "none";
        decoder.innerHTML = text;
        let sanitized_string = decoder.textContent;
        decoder.remove;
        return sanitized_string;
    };

    return {
        assign,
        toast,
        datetime,
        hasImage,
        hasString,
        $state,
        $varState,
        sanitize,
        $testState
    };
}