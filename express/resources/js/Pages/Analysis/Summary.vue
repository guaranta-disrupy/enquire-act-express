<script setup>
import {
    S4Input,
    S4Button,
    S4Pagination,
    S4Svg,
    S4Loading,
    S4Table,
} from "@/S4";
import { library } from "@fortawesome/fontawesome-svg-core";
import { faUserSecret } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faImage,
    faDownload,
    faArrowLeft,
    faChartBar,
    faChevronDown,
    faChevronRight,
    faCopy,
    faEdit,
    faExternalLinkAlt,
    faSortDown,
    faTrashAlt,
} from "@fortawesome/free-solid-svg-icons";
import {
    AddEditDeleteFilterModal,
    SummaryTable,
    SummaryFilters,
} from "@/Pages/Analysis/index.js";
import { Chart } from "highcharts-vue";
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import JetDropdown from "@/Jetstream/Dropdown.vue";
import Highcharts from "highcharts";
import loadDrillDown from "highcharts/modules/drilldown";
import domtoimage from "dom-to-image";

library.add(faUserSecret);
loadDrillDown(Highcharts);

const ready = ref(true);
const modals = ref({
    questionnaire: {
        add_edit: false,
        method: "add",
    },
});
const faIcon = ref({
    cheveron_right: faChevronRight,
    cheveron_down: faChevronDown,
    trash: faTrashAlt,
    copy: faCopy,
    edit: faEdit,
    chart: faChartBar,
    link: faExternalLinkAlt,
    arrow_left: faArrowLeft,
    sort_down: faSortDown,
    download: faDownload,
    image: faImage,
});
const showFilterModal = ref(false);
const filteredId = ref(0);
const filterName = ref(null);
const chartOptionList = ref([]);
const chartOptions = ref({
    chart: {
        type: "bar",
        // height : '250'
    },
    xAxis: {
        categories: [],
    },
    series: [],
    legend: {},
});
const countSample = ref({
    allsample: 0,
    complete: 0,
    screenout: 0,
});
const csvdata = ref([]);
const form = useForm({});
const status = ref("No data found.");
// const props = defineProps(['data'])
const data = ref({});
const addFilter = computed(() => {
    return "relative inline-flex items-center px-4 py-2 bg-blue-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-500 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:shadow-outline-yellow transition ease-in-out duration-150";
});

onMounted(async () => {
    // console.log(typeof data.value.data)
    // if(typeof data.value.data === 'undefined' ){
    //     await fetchCount()
    // }
    // loadChart()

    window.addEventListener("getSelectedFilter", getSelectedFilter);
});

onUnmounted(() => {
    window.removeEventListener("getSelectedFilter", getSelectedFilter);
});

const loadChart = async () => {
    await processChartContents();
    setTimeout(() => {
        ready.value = true;
        console.log("ready");
    }, 1000);
};

const initAddFilter = () => {
    showFilterModal.value = true;
};
const closeFilterModal = () => {
    showFilterModal.value = false;
};
const showModal = (table, modal, method) => {
    modals.value[table][modal] = true;
    modals.value[table].method = method;
};
const closeModal = (table, modal) => {
    modals.value[table][modal] = false;
};
const fetchCount = (selected_filter = 0) => {
    return new Promise((resolve) => {
        console.log("fetching data");
        ready.value = false;
        status.value = "Fetching data";

        var params = {
            questionnaire_code: usePage().props.questionnaire.code,
            filter_id: selected_filter,
        };
        console.log(params);
        console.log(route("analysis.summary", params));
        axios.get(route("analysis.summary", params)).then((obj) => {
            console.log(obj);
            data.value = obj.data;
            resolve();
        });
    });

    // alert(usePage().props.project.code)
    // alert(usePage().props.questionnaire.code)
};

const getSelectedFilter = async (event) => {
    // let selected_filter = data.value.filters.find((o)=>{
    //     return event.detail.id === o.id
    // })
    console.log(event.detail.id);

    await fetchCount(event.detail?.id || 0);
    loadChart();
};
const textboxCount = () => {
    _.map(data.value.questions, function (question, qindex) {
        if (question.question_type_id == 4 || question.question_type_id == 6) {
            console.log(Math.max(data.value.data.answercount[question.id]));
        }
    });
};
const savePNG = (e, qindex) => {
    let node = document.querySelector(".chart" + qindex);
    let name = "Q" + (qindex + 1) + ".png";
    let perp = this;

    domtoimage.toPng(node).then(function (dataUrl) {
        // window.saveAs(dataUrl,'Q'+(index+1)+'.png');
        var link = document.createElement("a");
        link.download = name;
        link.href = dataUrl;
        link.click();
    });

    console.log(e, qindex, "img");
};
const prependRows = (value, array) => {
    var newArray = array.slice();
    newArray.unshift(value);
    return newArray;
};
const saveTable = (e, qindex, qid, download_all = false, csv = []) => {
    var filename = "Q" + (qindex + 1) + " table";
    let tmp_col, tmp_row;
    let dom = new DOMParser().parseFromString(
        data.value.questions[qindex].question,
        "text/html"
    );

    //insert question
    csv.push(`Q${qindex + 1},\"${dom.body.textContent}\"`);

    switch (data.value.questions[qindex].question_type_id) {
        case 2:
        case 3:
            csv.push(",Choices,Count,Percentage");
            _.forEach(
                data.value.questions[qindex].choices,
                (q_value, q_index) => {
                    csv.push(
                        `,\"${q_value.name}\" ,${data.value.data.answercount[qid][q_index]},${data.value.data.anspercentage[qid][q_index]}`
                    );
                }
            );
            break;
        case 4:
        case 6:
            csv.push(`,Participant ID, Responses`);
            _.forEach(
                data.value.data.answercount[qid]["ids"],
                (ans, ans_key) => {
                    csv.push(
                        `,${ans},${data.value.data.answercount[qid]["answers"][ans_key]}`
                    );
                }
            );
            break;
        case 5:
            tmp_col = [];
            tmp_row = [];
            let tmp_answer = JSON.parse(
                JSON.stringify(data.value.data.answercount[qid])
            );
            console.log(tmp_answer);

            _.forEach(
                data.value.questions[qindex].items,
                (q_value, q_index) => {
                    tmp_col.push(`"${q_value.name}"`);
                }
            );
            csv.push(tmp_col);
            do {
                tmp_row = [];
                _.forEach(tmp_answer, (a) => {
                    tmp_row.push(`"${a.shift()}"`);
                });
                csv.push(tmp_row);
            } while (tmp_answer[0].length > 0);
            break;
        case 9:
            let tmp_item = data.value.questions[qindex].items;
            let tmp_ranks = _.map(tmp_item, (o, k) => {
                return `Rank ${k + 1}`;
            }).join(",");
            csv.push(
                `,Count,${new Array(tmp_item.length - 1).fill("")},,Percentage`
            );
            csv.push(`,Choices,${tmp_ranks},,${tmp_ranks}`);
            _.forEach(tmp_item, (q_value, q_index) => {
                csv.push(
                    `,\"${q_value.name}\" ,${data.value.data.answercount[qid][q_index]},,${data.value.data.anspercentage[qid][q_index]}`
                );
            });
            break;
        case 11:
            let tmp_column = data.value.questions[qindex].columns;
            tmp_col = ["", ""];
            _.forEach(
                data.value.questions[qindex].columns,
                (q_value, q_index) => {
                    tmp_col.push(`"${q_value.name}"`);
                }
            );
            let col_copy = [...tmp_col];

            col_copy.shift();
            col_copy.push("");
            csv.push(
                `,,Count,${new Array(tmp_column.length - 1).fill(
                    ""
                )},,Percentage`
            );
            csv.push(tmp_col.concat(col_copy));
            _.forEach(data.value.questions[qindex].rows, (q_value, q_index) => {
                let row_label =
                    q_value.placeholder !== undefined
                        ? q_value.placeholder
                        : q_value.name;
                csv.push(
                    `,\"${row_label}\",${data.value.data.answercount[qid][q_index]},,${data.value.data.anspercentage[qid][q_index]}`
                );
            });
            break;
        case 12:
            let tmp_q = data.value.questions[qindex];
            let tmp_data = {
                dropdown: { row: [], col: [] },
                radio: { row: [], col: [] },
                textbox: [],
            };
            tmp_answer = data.value.data.answercount[qid];
            _.forEach(tmp_q.rows, (cName, row_index) => {
                _.forEach(tmp_q.items, (item, item_index) => {
                    _.forEach(item.column, (column, column_key) => {
                        switch (column.type) {
                            case 1: // dropdown
                            case 2:
                                let chart_prop =
                                    column.type == 1 ? "dropdown" : "radio";
                                if (row_index == 0) {
                                    tmp_col =
                                        column.type == 1
                                            ? _.map(column.items, (o) => {
                                                  return `\"${o.name}\"`;
                                              })
                                            : _.map(item.column, (o) => {
                                                  return `\"${o.name}\"`;
                                              });
                                    csv.push(
                                        `${
                                            chart_prop.charAt(0).toUpperCase() +
                                            chart_prop.slice(1)
                                        } Data`
                                    );
                                    csv.push(`,${tmp_col.join(",")}`);
                                }
                                csv.push(
                                    `${
                                        cName.name
                                    },${data.value.data.answercount[qid][
                                        row_index
                                    ][item_index].join(
                                        ","
                                    )},${data.value.data.anspercentage[qid][
                                        row_index
                                    ][item_index].join(",")}`
                                );
                                break;
                            case 3:
                                if (row_index == 0) {
                                    csv.push("Textbox Data");
                                    csv.push(
                                        `Participant ID, ${_.map(
                                            tmp_q.rows,
                                            (o) => {
                                                return `${o.name}`;
                                            }
                                        ).join(",")}`
                                    );
                                    _.forEach(
                                        tmp_answer[0][item_index]["ids"],
                                        (id, answer_key) => {
                                            csv.push(
                                                `${id},${_.map(
                                                    tmp_q.rows,
                                                    (o, row_key) => {
                                                        return tmp_answer[
                                                            row_key
                                                        ][item_index][
                                                            "answers"
                                                        ][answer_key];
                                                    }
                                                )}`
                                            );
                                        }
                                    );
                                    csv.push("");
                                    csv.push("");
                                    csv.push("");
                                }
                                break;
                        }
                    });
                });
            });
            csv.push(tmp_data.textbox);
            break;
        default:
            csv.push("");
            break;
    }

    if (download_all == true) {
        csvdata.value.push(csv.join("\n"));
        if (qindex + 1 === data.value.questions.length)
            downloadFile(
                csvdata.value.join("\n"),
                data.value.questionnaire.name
            );
    } else {
        downloadFile(csv.join("\n"), filename);
    }
};
const downloadFile = (table, filename) => {
    var file;
    var fileLink;
    var universalBOM = "\uFEFF";
    table = universalBOM + table;
    file = new Blob([table], { type: "text/csv;charset=utf-8;" });

    fileLink = document.createElement("a");
    fileLink.download = filename;
    fileLink.href = window.URL.createObjectURL(file);
    fileLink.style.display = "none";

    document.body.appendChild(fileLink);

    fileLink.click();
};
const processChartContents = () => {
    return new Promise((resolve) => {
        console.log("processing charts");
        status.value = "Processing charts";
        _.map(data.value.questions, (choice, index) => {
            let tmp = JSON.parse(JSON.stringify(chartOptions.value));
            let item_count = 0;
            tmp.title = choice.question;
            tmp.xAxis.title = null;
            tmp.series = [];
            tmp.plotOptions = {
                bar: {
                    dataLabels: {
                        enabled: true,
                    },
                },
                data,
            };
            tmp.responsive = {
                rules: [
                    {
                        condition: {
                            maxHeight: 1000,
                        },
                    },
                ],
            };
            let a_count = data.value.data.anspercentage[choice.id];
            let ans_count = data.value.data.answercount[choice.id];
            let tmp_height = 0;
            switch (choice.question_type_id) {
                case 2: //Multiple Choice
                case 3: //Image Selection
                    _.map(choice.choices, (cName, index) => {
                        tmp.series.push({
                            name: cName.name,
                            data: [a_count[index]],
                        });
                    });
                    _.map(a_count, (o) => {
                        if (o > 0) item_count++;
                    });
                    tmp_height =
                        a_count.length > 2 ? a_count.length * 100 : 300;
                    if (choice.choices.length > 5) tmp.chart.type = "column";
                    tmp.chart.height = tmp_height > 500 ? 500 : tmp_height;

                    break;
                case 9:
                    tmp = {
                        chart: { type: "column" },
                        title: { text: "" },
                        xAxis: { type: "category" },
                        legend: { enabled: false },
                        plotOptions: {
                            series: {
                                borderWidth: 0,
                                dataLabels: { enabled: false },
                            },
                        },
                        series: [
                            {
                                name: "Rank",
                                colorByPoint: true,
                                data: _.map(choice.items, (cName, cindex) => {
                                    return {
                                        name: cName.name,
                                        y: a_count[cindex][0],
                                        drilldown: `Q${index}_${cindex + 1}`,
                                    };
                                }),
                            },
                        ],
                        drilldown: {
                            activeAxisLabelStyle: {
                                textDecoration: "none",
                                fontStyle: "italic",
                            },
                            activeDataLabelStyle: {
                                textDecoration: "none",
                                fontStyle: "italic",
                            },
                            series: _.map(choice.items, (cName, cindex) => {
                                return {
                                    id: `Q${index}_${cindex + 1}`,
                                    data: _.map(a_count[cindex], (o, key) => {
                                        return [`Rank ${key + 1}`, o];
                                    }),
                                };
                            }),
                        },
                    };
                    break;
                case 5: //Multiple textbox
                case 7: //Constant Sum
                case 8: //Slider
                case 10: //Star rating
                    _.map(choice.items, (cName, index) => {
                        tmp.series.push({
                            name: cName.name,
                            data: [a_count[index].length],
                        });
                    });
                    tmp_height =
                        choice.items.length > 2
                            ? (choice.items.length - 2) * 100
                            : 200;
                    tmp.chart.height = tmp_height > 1000 ? 1000 : tmp_height;
                    break;
                case 11: //Matrix
                    let rows =
                        choice.setup.displaybycolumn == 1
                            ? choice.columns
                            : choice.rows;
                    let columns =
                        choice.setup.displaybycolumn == 1
                            ? choice.rows
                            : choice.columns;
                    tmp.xAxis.categories = _.map(rows, (cName, index) => {
                        return cName.name;
                    });
                    _.map(columns, (cName, index) => {
                        tmp.series.push({
                            name: cName.name,
                            data: a_count[index],
                        });
                    });
                    tmp.chart.type = "column";
                    break;
                case 12: //matrix multi
                    tmp = {
                        dropdown: JSON.parse(
                            JSON.stringify(chartOptions.value)
                        ),
                        radio: JSON.parse(JSON.stringify(chartOptions.value)),
                    };
                    _.forEach(choice.rows, (cName, row_index) => {
                        _.forEach(choice.items, (item, item_index) => {
                            _.forEach(item.column, (column, column_key) => {
                                switch (column.type) {
                                    case 1: // dropdown
                                    case 2:
                                        let chart_prop =
                                            column.type == 1
                                                ? "dropdown"
                                                : "radio";
                                        if (row_index == 0) {
                                            tmp[chart_prop].xAxis.categories =
                                                column.type == 1
                                                    ? _.map(
                                                          column.items,
                                                          (o) => {
                                                              return o.name;
                                                          }
                                                      )
                                                    : _.map(
                                                          item.column,
                                                          (o) => {
                                                              return o.name;
                                                          }
                                                      );
                                            tmp[chart_prop].title =
                                                choice.question;
                                        }
                                        if (column_key == 0)
                                            tmp[chart_prop].series.push({
                                                name: cName.name,
                                                data: a_count[row_index][
                                                    item_index
                                                ],
                                            });

                                        tmp[chart_prop].chart.type = "column";
                                        break;
                                }
                            });
                        });
                    });
                    break;
            }
            chartOptionList.value[index] = tmp;
        });
        resolve();
    });
};
const saveAll = () => {
    let q_size = data.value.questions.length;
    csvdata.value = [];
    data.value.data.questions.forEach((q, q_index) => {
        csvdata.value.push(saveTable(q, q_index, q.id, true));
    });
};
const backToAnalysis = () => {
    window.history.back();
};

const headers = ref([
    {
        label: "DATA SUMMARY",
        class: "pl-6 py-5 font-semibold w-1/3 rounded-tl-xl",
    },
]);
</script>

<template>
    <s4-table
        :headers="headers"
        class="mt-6 overflow-auto"
        style="min-height: 55vmin"
    >
        <template #body>
            <tr
                class="text-md border-b border-l border-r border-gray-100 text-slate-500 hover:bg-slate-50"
            >
                <div class="bg-white main-content flex flex-col flex-grow px-5">
                    <div class="grid grid-cols-7">
                        <jet-label class="mt-4 font-bold text-xs mb-1"
                            >Select Filter:</jet-label
                        >

                        <SummaryFilters />
                        <div class="mt-2 rounded-lg w-full">
                            <s4-button
                                @click="saveAll"
                                :theme="''"
                                class="max-h-10 h-full w-full text-center inline-block"
                            >
                                Download All
                            </s4-button>
                        </div>

                        <div
                            class="col-span-12"
                            v-if="ready && data?.data?.complete > 0"
                        >
                            <div
                                class="border border-gray-300 p-10 mb-5 rounded-lg flex flex-col items center"
                                v-for="(question, qindex) in $page.props
                                    .questions"
                                :key="qindex"
                            >
                                <div
                                    class="header-content flex justify-end items-center float-right"
                                    v-if="question.question_type_id !== 1"
                                >
                                    <s4-button
                                        @click="
                                            saveTable(
                                                $event,
                                                qindex,
                                                question.id
                                            )
                                        "
                                        :theme="''"
                                        title="Download"
                                        class="mr-1"
                                    >
                                        <font-awesome-icon
                                            :icon="faIcon.download"
                                            class="w-5 h-5 fill-current ft"
                                        />
                                    </s4-button>
                                    <s4-button
                                        @click="savePNG($event, qindex)"
                                        :theme="''"
                                        title="Save as PNG"
                                        v-if="
                                            question.question_type_id !== 4 &&
                                            question.question_type_id !== 6
                                        "
                                    >
                                        <font-awesome-icon
                                            :icon="faIcon.image"
                                            class="w-5 h-5 fill-current ft"
                                        />
                                    </s4-button>
                                </div>
                                <!-- <div class="flex flex-wrap items-center"> -->
                                <div :class="'chart' + qindex">
                                    <div class="h-full w-full">
                                        <span
                                            class="block mb-5 text-gray-700 block"
                                            ><strong
                                                >Q{{ question.order }}</strong
                                            ></span
                                        >
                                        <div class="mb-5 block">
                                            <p v-html="question.question"></p>
                                        </div>
                                    </div>

                                    <template
                                        v-if="question.question_type_id !== 1"
                                    >
                                        <div
                                            :class="
                                                'textbox-container table' +
                                                qindex
                                            "
                                            v-if="
                                                question.question_type_id ==
                                                    4 ||
                                                question.question_type_id == 6
                                            "
                                        >
                                            <SummaryTable
                                                :question="question"
                                                :rows="[]"
                                                :ids="
                                                    data.data.answercount[
                                                        question.id
                                                    ]['ids']
                                                "
                                                :answers="
                                                    data.data.answercount[
                                                        question.id
                                                    ]['answers']
                                                "
                                            >
                                            </SummaryTable>
                                        </div>
                                        <div
                                            :class="
                                                'textbox-container table' +
                                                qindex
                                            "
                                            v-if="
                                                question.question_type_id == 5
                                            "
                                        >
                                            <SummaryTable
                                                :question="question"
                                                :rows="question.items"
                                                :columns="data.data.answercount"
                                            >
                                            </SummaryTable>
                                        </div>
                                        <div
                                            :class="'w-full table' + qindex"
                                            v-else-if="
                                                question.question_type_id == 12
                                            "
                                        >
                                            <template
                                                v-for="(
                                                    mm_item, mm_key
                                                ) in question.items"
                                                :key="mm_key"
                                            >
                                                <template
                                                    v-for="(
                                                        item_column, ic_key
                                                    ) in mm_item.column"
                                                    :key="ic_key"
                                                >
                                                    <template
                                                        v-if="
                                                            mm_item.column[
                                                                ic_key
                                                            ].type == 3
                                                        "
                                                    >
                                                        <div
                                                            class="textbox-container"
                                                        >
                                                            <SummaryTable
                                                                :question="
                                                                    question
                                                                "
                                                                :rows="
                                                                    question.rows
                                                                "
                                                                :ids="
                                                                    data
                                                                        .answercount[
                                                                        question
                                                                            .id
                                                                    ][0][
                                                                        mm_key
                                                                    ]['ids']
                                                                "
                                                                :answers="
                                                                    data
                                                                        .answercount[
                                                                        question
                                                                            .id
                                                                    ]
                                                                "
                                                                :item_key="
                                                                    mm_key
                                                                "
                                                            >
                                                            </SummaryTable>
                                                        </div>
                                                    </template>
                                                    <template
                                                        v-if="
                                                            mm_item.column[
                                                                ic_key
                                                            ].type == 1 &&
                                                            chartOptionList[
                                                                qindex
                                                            ] !== undefined
                                                        "
                                                    >
                                                        <Chart
                                                            :options="
                                                                chartOptionList[
                                                                    qindex
                                                                ].dropdown
                                                            "
                                                            class="w-full"
                                                        ></Chart>
                                                    </template>
                                                    <template
                                                        v-if="
                                                            mm_item.column[
                                                                ic_key
                                                            ].type == 2 &&
                                                            chartOptionList[
                                                                qindex
                                                            ] !== undefined &&
                                                            ic_key == 0
                                                        "
                                                    >
                                                        <Chart
                                                            :options="
                                                                chartOptionList[
                                                                    qindex
                                                                ].radio
                                                            "
                                                            class="w-full"
                                                        ></Chart>
                                                    </template>
                                                </template>
                                            </template>
                                        </div>
                                        <div
                                            :class="
                                                'table' +
                                                qindex +
                                                ' h-full w-full bg-white rounded-lg md:rounded-none md:rounded-1-lg'
                                            "
                                            v-else
                                        >
                                            <template
                                                v-if="
                                                    chartOptionList[qindex]
                                                        .series.length > 0
                                                "
                                            >
                                                <Chart
                                                    :options="
                                                        chartOptionList[qindex]
                                                    "
                                                    class="w-full"
                                                ></Chart>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div v-show="!ready">
                            <s4-loading
                                theme="theme1"
                                :label="status"
                                class="h-screen z-50 overflow-hidden bg-gray-700/50"
                                :color="'text-white'"
                            ></s4-loading>
                        </div>
                    </div>
                </div>
            </tr>
        </template>
    </s4-table>
</template>
<style>
#summarytable {
    overflow-y: auto;
}
.textbox-container {
    max-height: 300px;
    overflow-y: scroll;
    width: 100%;
    font-size: small;
}
.inline-block {
    display: inline-block;
}
</style>
