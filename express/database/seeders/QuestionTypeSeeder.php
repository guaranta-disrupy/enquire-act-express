<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=QuestionTypeSeeder
     * @return void
     */
    public function run()
    {

        DB::table('question_types')->truncate();
        DB::table("question_types")->insert([
            [
                "id" => 1,
                "name" => "Descriptive",
                "category" => "Description",
                "setup" => json_encode([
                    "layout" => 1,
                    "layout_img" => [
                        "src" => "",
                        "width" => "40vmin",
                        "height" => "40vmin",
                        "image_dimension" => "50vmin",
                        "image_dimension_tab" => "50vmin",
                        "image_dimension_mobile" => "50vmin",
                        "image_dimension_enabler" => false,
                    ],
                    "layout_border_enabler" => false,
                    "btn_delay" => 0,
                    "btn_autoNext" => 0,
                    "btn_delay_enabled" => false,
                    "btn_autoNext_enabled" => false,
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "advanceSkiplogic2" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                        "max-width_enabler" => false,
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 2,
                "name" => "Text Selection",
                "category" => "Multiple choice",
                "setup" => json_encode([
                    "textSelection" => [
                        "layout_switch" => 0,
                        "grid" => 1,
                        "grid_tab" => 1,
                        "grid_mobile" => 1,
                        "multiSelect_type" => 1,
                        "multiSelect_max" => "",
                        "multiSelect_min" => "",
                        "multiSelect_errorMsg_type2" => "Please select between @min_select to @max_select items",
                        "multiSelect_errorMsg_type3" => "You must select @max_select items",
                    ],
                    "minorUnitSymbol" => 0,
                    "detectGibberish" => 0,
                    "displayChoices" => 0,
                    "itemsPerPage" => 1,
                    "searchPlaceholder" => "Search for",
                    "selectedPlaceholder" => "option(s) selected",
                    "selectMultiple" => 0,
                    "enablePoint" => 0,
                    "randomChoices" => [],
                    "unSelectAll" => [],
                    "selectAll" => [],
                    "isDropdown" => 0,
                    "placeholder" => "Select Choice",
                    "appendString" => "",
                    "prependString" => "",
                    "grid" => 1,
                    "grid_tab" => 1,
                    "grid_mobile" => 1,
                    "validate" => [
                        "required" => 1,
                        "min" => 0,
                        "max" => 0,
                        "errorMsg" => "This question requires an answer",
                    ],
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "advanceSkiplogic2" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 3,
                "name" => "Image selection",
                "category" => "Multiple choice",
                "setup" => json_encode([
                    "imageSelection" => [
                        "layout_switch" => 0,
                        "layout_desktop" => 1,
                        "layout_mobile" => 1,
                        "layout_tab" => 1,
                        "grid" => 5,
                        "grid_tab" => 3,
                        "grid_mobile" => 2,
                        "image_dimension_switch" => 0,
                        "image_dimension" => "20vmin",
                        "image_dimension_tab" => "20vmin",
                        "image_dimension_mobile" => "20vmin",
                        "multiSelect_type" => 1,
                        "multiSelect_max" => "",
                        "multiSelect_min" => "",
                        "multiSelect_errorMsg_type2" => "Please select at least @min_select to @max_select item(s)",
                        "multiSelect_errorMsg_type3" => "You have to select @max_select items",
                    ],
                    "detectGibberish" => 0,
                    "selectMultiple" => 1,
                    "randomChoices" => [],
                    "grid" => 5,
                    "grid_tab" => 3,
                    "grid_mobile" => 2,
                    "itemMaxWidth" => 300,
                    "image_dimension" => "20vmin",
                    "image_dimension_tab" => "20vmin",
                    "image_dimension_mobile" => "20vmin",
                    "validate" => [
                        "required" => 1,
                        "min" => 0,
                        "max" => 0,
                        "errorMsg" => "This question requires an answer",
                    ],
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "max-width_enabler" => 0,
                        "size" => "md",
                        "max-width" => "70%",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "layout" => 1,
                    "mobile_layout" => 1,
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 4,
                "name" => "Single textbox",
                "category" => "Textbox",
                "setup" => json_encode([
                    "textBox" => [
                        "layout_switch" => 0,
                        "layout_desktop" => 1,
                        "layout_mobile" => 1,
                        "layout_tab" => 1,
                        "textbox_dimension_switch" => 0,
                        "textbox_dimension" => "20vmin",
                        "textbox_dimension_tab" => "20vmin",
                        "textbox_dimension_mobile" => "20vmin",
                    ],
                    "minorUnitSymbol" => 0,
                    "detectGibberish" => 0,
                    "validate" => [
                        "required" => 1,
                        "type" => 1,
                        "min" => "",
                        "max" => "",
                        "errorMsg" => "This question requires an answer",
                    ],
                    "appendString" => "",
                    "prependString" => "",
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "style" => [
                        "max-width" => "100%",
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                    "textBox_dimension_switch" => 0,
                    "textBox_dimension" => "20vmin",
                    "textBox_dimension_tab" => "20vmin",
                    "textBox_dimension_mobile" => "20vmin",
                ]),
            ],
            [
                "id" => 5,
                "name" => "Multiple textbox",
                "category" => "Textbox",
                "setup" => json_encode([
                    "multipleTextSelection" => [
                        "layout_switch" => 0,
                        "grid" => 1,
                        "grid_tab" => 3,
                        "grid_mobile" => 2,
                    ],
                    "minorUnitSymbol" => 0,
                    "detectGibberish" => 0,
                    "randomChoices" => [],
                    "grid" => 1,
                    "validate" => [
                        "required" => 1,
                    ],
                    "appendString" => "",
                    "prependString" => "",
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 6,
                "name" => "Open-ended",
                "category" => "Textbox",
                "setup" => json_encode([
                    "validate" => [
                        "filter_domain" => null,
                        "required" => 1,
                        "min" => 1,
                        "max" => 200,
                        "errorMsg" => "Please input a value in 1 - 200",
                    ],
                    "detectGibberish" => 0,
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "style" => [
                        "width" => "100%",
                        "height" => "200px",
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 7,
                "name" => "Pie Chart",
                "category" => "Charts",
                "setup" => json_encode([
                    "selectMultiple" => 1,
                    "randomChoices" => [],
                    "enableDecimal" => 0,
                    "prefix" => "",
                    "suffix" => "%",
                    "enableChart" => 1,
                    "enableSlider" => 1,
                    "chart" => [
                        "enable" => 1,
                        "noDataLabel" => "",
                    ],
                    "range" => [
                        "min" => 0,
                        "max" => 100,
                        "origin" => 0,
                    ],
                    "totalLabel" => [
                        "enable" => 1,
                        "text" => "Total",
                    ],
                    "fixedValue" => 1,
                    "validate" => [
                        "required" => 1,
                        "errorMsg" => "This question requires an answer",
                    ],
                    "validate2" => [
                        "minValue" => 0,
                        "errorMsg" => "Each value must be greater than @min",
                    ],
                    "validate3" => [
                        "total" => 100,
                        "errorMsg" => "Total must be @total%",
                    ],
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 8,
                "name" => "Slider",
                "category" => "Rating",
                "setup" => json_encode([
                    "default" => 3,
                    "randomItems" => [],
                    "validate" => [
                        "required" => 1,
                        "errorMsg" => "This question requires an answer",
                    ],
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 9,
                "name" => "Ranking",
                "category" => "Rating",
                "setup" => json_encode([
                    "randomChoices" => [],
                    /**
                     * Number of columns
                     */
                    "numberOfColumns" => 2,
                    "numberOfColumns_tab" => 1,
                    "numberOfColumns_mobile" => 1,
                    /**
                     * mode=> ranking / sorting
                     * 0 = ranking,
                     * 1 = sorting
                     */
                    "mode" => 1,
                    /**
                     * position=> chosen box positioning
                     * 1 = chosen box is on the right
                     * 2 = chosen box is on the left
                     * 3 = chosen box is on the bottom
                     * 4 = chosen box is on the top
                     * ON MOBILE VIEW
                     * 1 == 3 = chosen box is on the bottom
                     * 2 == 4 = chosen box is on the top
                     * */
                    "position" => 1,
                    /**
                     * limit=> drag limiter
                     * limit == 0 = no limit
                     * limit > 0 = with limit
                     */
                    "limit" => 0,
                    /**
                     * validate=> for empty data checker
                     */
                    "mostPreferred" => "Most Preferred",
                    "leastPreferred" => "Least Preferred",
                    "dropzone" => "Drop Zone",
                    "validate" => [
                        "required" => 1,
                        "errorMsg" => "This question requires an answer",
                    ],
                    /**
                     * validate2=> item count checker
                     * minValue == 0 = means skip validation
                     * minValue > 0 = validate chosen item count
                     */
                    "validate2" => [
                        //For not fixed value
                        "minValue" => 3,
                        "errorMsg" => "Select at least {{count}} item(s)",
                    ],
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "fixedHeight" => "50vmin",
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "itemDimension" => [
                        "item_dimension_switch" => 0,
                        "item_dimension" => "20vmin",
                        "item_dimension_tab" => "20vmin",
                        "item_dimension_mobile" => "20vmin",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 10,
                "name" => "Star rating",
                "category" => "Rating",
                "setup" => json_encode([
                    "size" => 5,
                    "randomItems" => [],
                    "design" => [
                        "selected" => "",
                        "notSelected" => "",
                    ],
                    "validate" => [
                        "required" => 1,
                        "errorMsg" => "This question requires an answer",
                    ],
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 11,
                "name" => "Matrix",
                "category" => "Matrix",
                "setup" => json_encode([
                    "displayByRow" => 0,
                    "selectMultiple" => 0,
                    "random" => 0,
                    "optional" => 0,
                    "randomRows" => [],
                    "unSelectAll" => [],
                    "selectAll" => [],
                    "validate" => [
                        "required" => 1,
                        "min" => 1,
                        "max" => 3,
                        "errorMsg" => "This question requires an answer",
                    ],
                    "writeinType" => 1,
                    "writeinMin" => 0,
                    "writeinMax" => 1,
                    "writeinRow" => 0,
                    "writeinRowChoice" => "Others (Please specify)",
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "size" => "lg",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            // [
            //     "id" => 12,
            //     "name" => "Matrix multi",
            //     "category" => "Matrix",
            //     "setup" => json_encode([
            //         "selectMultiple" => 1,
            //         "randomRows" => [],
            //         "unSelectAll" => [],
            //         "selectAll" => [],
            //         "validate" => [
            //             "required" => 1,
            //             "min" => 1,
            //             "max" => 3,
            //             "errorMsg" => "This question requires an answer",
            //         ],
            //         "skiplogic" => [],
            //         "customSkiplogic" => [
            //             "enter" => "",
            //             "leave" => "",
            //         ],
            //         "advanceSkiplogic" => [
            //             "enter" => [],
            //             "leave" => [],
            //         ],
            //         "boxStyle" => [
            //             "size" => "lg",
            //             "max-width" => "60vmin",
            //         ],
            //         "dataType" => [],
            //         "questionName" => "",
            //     ]),
            // ],
            // [
            //     "id" => 13,
            //     "name" => "Memory test descriptive",
            //     "category" => "Memory test",
            //     "setup" => json_encode([
            //         "interval" => 2000,
            //         "random" => 1,
            //         "validate" => [
            //             "required" => 1,
            //             "errorMsg" => "This question requires an answer",
            //         ],
            //         "skiplogic" => [],
            //         "customSkiplogic" => [
            //             "enter" => "",
            //             "leave" => "",
            //         ],
            //         "advanceSkiplogic" => [
            //             "enter" => [],
            //             "leave" => [],
            //         ],
            //         "boxStyle" => [
            //             "size" => "md",
            //             "max-width" => "60%",
            //             "max-width-tab" => "100%",
            //             "max-width-mobile" => "100%",
            //         ],
            //         "dataType" => [],
            //         "questionName" => "",
            //     ]),
            // ],
            // [
            //     "id" => 14,
            //     "name" => "Memory test comparison",
            //     "category" => "Memory test",
            //     "setup" => json_encode([
            //         "getReactionTime" => 0,
            //         "random" => 1,
            //         "validate" => [
            //             "required" => 1,
            //             "errorMsg" => "This question requires an answer",
            //         ],
            //         "skiplogic" => [],
            //         "customSkiplogic" => [
            //             "enter" => "",
            //             "leave" => "",
            //         ],
            //         "advanceSkiplogic" => [
            //             "enter" => [],
            //             "leave" => [],
            //         ],
            //         "boxStyle" => [
            //             "size" => "md",
            //             "max-width" => "60%",
            //             "max-width-tab" => "100%",
            //             "max-width-mobile" => "100%",
            //         ],
            //         "dataType" => [],
            //         "questionName" => "",
            //     ]),
            // ],
            [
                "id" => 15,
                "name" => "Button rating",
                "category" => "Rating",
                "setup" => json_encode([
                    "header" => "",
                    "footer" => "",
                    "randomItems" => [],
                    "randomValues" => [],
                    "validate" => [
                        "required" => 1,
                        "errorMsg" => "This question requires an answer",
                    ],
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "fontStyle" => [
                        "items" => "",
                        "values" => "",
                    ],
                    "hideQuestionFrame" => false,
                    // "imageSize" => [
                    //     "height" => "",
                    //     "width" => "",
                    // ],
                    // "borderRadius" => "",
                    "itemsLayout" => 1, //1. Text only 2. Image only (Circular display) 3. Image only (Box display) 4. Text with Image
                    "valuesLayout" => 1,
                    "passRTToURL" => 1,
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 16,
                "name" => "Segmentation",
                "category" => "Rating",
                "setup" => json_encode([
                    "labels" => [
                        "statementA" => "Statement A",
                        "statementB" => "Statement B",
                    ],
                    "default" => 3,
                    "randomItems" => [],
                    "validate" => [
                        "required" => 1,
                        "errorMsg" => "This question requires an answer",
                    ],
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "max-width_enabler" => 0,
                        "size" => "md",
                        "max-width" => "100%",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 18,
                "name" => "Video Player",
                "category" => "Description",
                "setup" => json_encode([
                    "layout_video" => [
                        "src" => "",
                        "loop" => 0,
                        "play_again" => 1,
                        "play_text" => "Play Video",
                        "play_again_text" => "Play Again",
                        "width" => "60vmin",
                        "height" => "60vmin",
                    ],
                    "btn_delay" => 0,
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 19,
                "name" => "Pairwise Comparison",
                "category" => "Pairwise Comparison",
                "setup" => json_encode([
                    "footer" => "<p>Click <strong>Statement A</strong> or <strong>Statement B</strong> to choose your preference</p>",
                    "labels" => [
                        "statementA" => "Statement A",
                        "statementB" => "Statement B",
                    ],
                    "validate" => [
                        "required" => 1,
                        "errorMsg" => "This question requires an answer",
                    ],
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 20,
                "name" => "Without Dimensions",
                "category" => "Best-Worst Scale",
                "setup" => json_encode([
                    "bestWostScale" => [
                        "labelA" => "Best",
                        "labelAColor" =>  "#4caf50",
                        "labelB" => "Worst",
                        "labelBColor" => "#f44336",
                        "sets" => '',
                        "repetitionsPerStatement" => 1,
                        "itemsPerSet" => ''
                    ],
                    "validate" => [
                        "required" => 1,
                        "errorMsg" => "This question requires an answer",
                    ],
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 21,
                "name" => "Bar Chart",
                "category" => "Charts",
                "setup" => json_encode([
                    "selectMultiple" => 1,
                    "randomChoices" => [],
                    "enableDecimal" => 0,
                    "prefix" => "",
                    "suffix" => "%",
                    "enableChart" => 1,
                    "enableSlider" => 1,
                    "barchart"  => [
                        "x" => "x axis",
                        "y" => "y axis"
                    ],
                    "chart" => [
                        "enable" => 1,
                        "noDataLabel" => "",
                    ],
                    "range" => [
                        "min" => 0,
                        "max" => 100,
                        "origin" => 0,
                    ],
                    "totalLabel" => [
                        "enable" => 1,
                        "text" => "Total",
                    ],
                    "fixedValue" => 1,
                    "validate" => [
                        "required" => 1,
                        "errorMsg" => "This question requires an answer",
                    ],
                    "validate2" => [
                        "minValue" => 0,
                        "errorMsg" => "Each value must be greater than @min",
                    ],
                    "validate3" => [
                        "total" => 100,
                        "errorMsg" => "Total must be @total%",
                    ],
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
            [
                "id" => 22,
                "name" => "With Dimensions",
                "category" => "Best-Worst Scale",
                "setup" => json_encode([
                    "bestWostScale" => [
                        "labelA" => "Best",
                        "labelAColor" =>  "#4caf50",
                        "labelB" => "Worst",
                        "labelBColor" => "#f44336",
                        "sets" => '',
                        "repetitionsPerStatement" => 3,
                        "itemsPerSet" => ''
                    ],
                    "validate" => [
                        "required" => 1,
                        "errorMsg" => "This question requires an answer",
                    ],
                    "skiplogic" => [],
                    "customSkiplogic" => [
                        "enter" => "",
                        "leave" => "",
                    ],
                    "advanceSkiplogic" => [
                        "enter" => [],
                        "leave" => [],
                    ],
                    "boxStyle" => [
                        "size" => "md",
                        "max-width" => "70vmin",
                        "max-width-tab" => "100%",
                        "max-width-mobile" => "100%",
                    ],
                    "dataType" => [],
                    "questionName" => "",
                ]),
            ],
        ]);
    }
}
