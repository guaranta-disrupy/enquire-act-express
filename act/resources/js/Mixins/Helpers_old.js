export default {
    methods: {
        assign(data) {
            return JSON.parse(JSON.stringify(data));
        },
        hasImage(str) {
            return str ? str.split('<img ').length > 1 : false
        },
        datetime(time) {
            return moment(time).tz(timezone).format(time_format)
        },
        hasString(str, needles) {
            return needles.some(entry => str.includes(entry))
        }
    },
};
