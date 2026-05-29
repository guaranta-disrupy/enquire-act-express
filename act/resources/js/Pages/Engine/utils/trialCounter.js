export function getTrialCounter() {
    const getTrialCount = (data) => {
        let count = 0;
        for (let survey of data.surveys) {
            for (let test of survey.tests) {
                count += test?.trials?.length;
            }
        }
        return count;
    };

    return {
        getTrialCount,
    };
}
