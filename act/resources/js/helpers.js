
const helper = {
    shuffle : (array)=> {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array;
    },
    setDefaults : (target, defaults)=>{
        for (let key in defaults) {
            if (typeof defaults[key] === 'object' && defaults[key] !== null && !Array.isArray(defaults[key])) {
            if (!target[key]) target[key] = {};
                helper.setDefaults(target[key], defaults[key]);
            } else {
            if (!(key in target)) {
                target[key] = defaults[key];
            }
            }
        }
        return target;
    }
}

export default helper
