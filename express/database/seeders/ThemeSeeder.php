<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=ThemeSeeder
     * @return void
     */
    public function run()
    {
        DB::table('themes')->insert([
            [
                'id' => 1,
                'text' => 'Default',
                'setup' => json_encode([
                    'design_id' => 1,
                    'design' => [
    
                        /* BACKGROUND */
                        'background-color' => '#ffffff',
                        'background-img' => 'https://express.splitsecondsurveys.co.uk/ide/gallery/yjNqj0sNfhu5atP3JVI2Sn5pEYsqYRqDQmAAcH9E.png',
                        'background-img-brightness' => '100%',

                        'question-margin-top' => '14vmin',
                        'question-margin-bottom' => '14vmin',
                        'question-margin-left' => '8vmin',
                        'question-margin-right' => '8vmin',                
                        'question-txt-color' => '#2c3e50',
                
                            /* BUTTONS */
                        'button-color' => '#e38733',
                        'button-hover-color' => '#e17126',
                        'button-txt-color' => '#ffffff',
                        
                
                            /* WARNING */
                        'warning-msg-color' => '#f15a2a',
                        'warning-msg-txt-color' => '#ffffff',
                        'warning-msg-border-color' => '#b12b01',
                
                            /* CHOICE BOX */
                        'choice-box-color' =>  '#ffffff',
                        'choice-box-border-color' =>  '#ededed',
                
                
                            /* TABLE */
                        'tbl-header-color' => '#1276be',
                        'tbl-header-lbl-color' => '#ffffff',
                        'tbl-block-color1' => '#f1f1f1',
                        'tbl-block-color2' => '#ffffff',
                        'tbl-lbl-color' => '#2c3e50',
                        'tbl-hint-color' => '#d3e7ff',
                        'tbl-hint-lbl-color' => '#1276be',
                        'tbl-border-color' => '#ededed',
                
                            /* RADIO */
                        'radio-color' => '#ededed',
                        'radio-border-color' => '#dbd7d7',
                        'radio-selected-color' => '#1276be',
                        'radio-lbl-color' => '#2c3e50',
                
                            /* CHECKBOX */
                        'checkbox-color' => '#ededed',
                        'checkbox-border-color' => '#dbd7d7',
                        'checkbox-selected-color' => '#1276be',
                        'checkbox-lbl-color' => '#2c3e50',
                
                            /* TEXTBOX */
                        'tb-color' => '#ffffff',
                        'tb-border-color' => '#dbd7d7',
                        'tb-txt-color' => '#2c3e50',
                        'tb-lbl-color' => '#2c3e50',
                        'tb-error-color' => '#faddd5',
                        'tb-error-txt-color' => '#2c3e50',
                
                        /* CALENDAR */
                        'cal-color' =>  '#ffffff',
                        'cal-border-color' =>  '#dbd7d7',
                        'cal-lbl-color' => '#2c3e50',
                        'cal-disable-lbl-color' => 'rgba(0,0,0,.5)',
                        'cal-today-color' => '#ededed',
                        'cal-hover-color' => '#ededed',
                        'cal-selected-color' => '#1276be',
                        
                            /* SELECTBOX */
                        'sb-color' => '#ededed',
                        'sb-border-color' => '#dbd7d7',
                        'sb-txt-color' => '#2c3e50',
                        'sb-selected-color' => '#0c5ba8',
                        'sb-selected-txt-color' => '#ffffff',
                        'sb-hover-color' => '#ededed',
                        'sb-hover-txt-color' => '#2c3e50',
                
                
                            /* IMAGE SELECTION */
                        'isb-color' => '#ffffff',
                        'isb-txt-color' => '#2c3e50',
                        'isb-selected-color' => '#0c5ba8',
                        'isb-selected-txt-color' => '#ffffff',
                        'isb-hover-color' => '#ededed',
                        'isb-hover-txt-color' => '#2c3e50',
                
                            /* SLIDER */
                        'slider-line-color' => '#dbd7d7',
                        'slider-default-color' => '#dbd7d7',
                        'slider-default-selected-color' => '#ffffff',
                        'slider-lbl-color' => '#2c3e50',
                        'slider-option-color' => '#dbd7d7'
                    
                        
                    ]
                ])
            ],
            [
                'id' => 2,
                'text' => 'Aquarium',
                'setup' => json_encode([
                    'design_id' => 2,
                    'design' => [
                        
                        /* BACKGROUND */
                        'background-color' => '#dee3e5',
                        'background-img' => 'https://express.splitsecondsurveys.co.uk/ide/gallery/eWYXPiBHo9fiIfh92wtRDvpepym3hLaYZtxPeNDt.jpg',
                        'background-img-brightness' => '100%',
                
                        'question-margin-top' => '14vmin',
                        'question-margin-bottom' => '14vmin',
                        'question-margin-left' => '8vmin',
                        'question-margin-right' => '8vmin',
                        'question-txt-color' => '#2c3e50',
                
                         /* BUTTONS */
                        'button-color' => '#78b6ff',
                        'button-hover-color' => '#4595f3',
                        'button-txt-color' => '#ffffff',
                        
                
                         /* WARNING */
                        'warning-msg-color' => '#e91e63',
                        'warning-msg-txt-color' => '#ffffff',
                        'warning-msg-border-color' => '#b12b01',
                
                         /* CHOICE BOX */
                        'choice-box-color' => '#ffffff',
                        'choice-box-border-color' => '#ededed',
                
                
                         /* TABLE */
                        'tbl-header-color' => '#78b6ff',
                        'tbl-header-lbl-color' => '#ffffff',
                        'tbl-block-color1' => '#f7fcff',
                        'tbl-block-color2' => '#ffffff',
                        'tbl-lbl-color' => '#2c3e50',
                        'tbl-hint-color' => '#d3e7ff',
                        'tbl-hint-lbl-color' => '#78b6ff',
                        'tbl-border-color' => '#ededed',
                
                         /* RADIO */
                        'radio-color' => '#ededed',
                        'radio-border-color' => '#dbd7d7',
                        'radio-selected-color' => '#78b6ff',
                        'radio-lbl-color' => '#2c3e50',
                
                         /* CHECKBOX */
                        'checkbox-color' => '#ededed',
                        'checkbox-border-color' => '#dbd7d7',
                        'checkbox-selected-color' => '#78b6ff',
                        'checkbox-lbl-color' => '#2c3e50',
                
                         /* TEXTBOX */
                        'tb-color' => '#ffffff',
                        'tb-border-color' => '#dbd7d7',
                        'tb-txt-color' => '#2c3e50',
                        'tb-lbl-color' => '#2c3e50',
                        'tb-error-color' => '#f1b6ca',
                        'tb-error-txt-color' => '#2c3e50',
                
                        /* CALENDAR */
                        'cal-color' => '#ffffff',
                        'cal-border-color' => '#dbd7d7',
                        'cal-lbl-color' => '#2c3e50',
                        'cal-disable-lbl-color' => 'rgba(0,0,0,.5)',
                        'cal-today-color' => '#ededed',
                        'cal-hover-color' => '#ededed',
                        'cal-selected-color' => '#78b6ff',
                        
                         /* SELECTBOX */
                        'sb-color' => '#ededed',
                        'sb-border-color' => '#dbd7d7',
                        'sb-txt-color' => '#2c3e50',
                        'sb-selected-color' => '#78b6ff',
                        'sb-selected-txt-color' => '#ffffff',
                        'sb-hover-color' => '#ededed',
                        'sb-hover-txt-color' => '#2c3e50',
                
                
                         /* IMAGE SELECTION */
                        'isb-color' => '#ffffff',
                        'isb-txt-color' => '#2c3e50',
                        'isb-selected-color' => '#78b6ff',
                        'isb-selected-txt-color' => '#ffffff',
                        'isb-hover-color' => '#ffffff',
                        'isb-hover-txt-color' => '#2c3e50',
                
                         /* SLIDER */
                        'slider-line-color' => '#6ca5e5',
                        'slider-default-color' => '#6ca5e5',
                        'slider-default-selected-color' => '#ffffff',
                        'slider-lbl-color' => '#2c3e50',
                        'slider-option-color' => '#6ca5e5'
                        
                    ]
                ])
            ],
            [
                'id' => 3,
                'text' => 'D4D',
                'setup' => json_encode([
                    'design_id' => 3,
                    'design' => [
                        
                        /* BACKGROUND */
                        'background-color' => '#dee3e5',
                        'background-img' => 'https://express.splitsecondsurveys.co.uk/ide/gallery/YTOatlsAG87DlLDUDtGDNJ9ayeGTYCZ1DPZl70dz.jpg',
                        'background-img-brightness' => '100%',
                
                        'question-margin-top' => '14vmin',
                        'question-margin-bottom' => '14vmin',
                        'question-margin-left' => '8vmin',
                        'question-margin-right' => '8vmin',
                        'question-txt-color' => '#065e66',
                
                         /* BUTTONS */
                        'button-color' => '#065e66',
                        'button-hover-color' => '#04464c',
                        'button-txt-color' => '#ffffff',
                        
                
                         /* WARNING */
                        'warning-msg-color' => '#e91e63',
                        'warning-msg-txt-color' => '#ffffff',
                        'warning-msg-border-color' => '#b12b01',
                
                         /* CHOICE BOX */
                        'choice-box-color' => '#FFFFFF',
                        'choice-box-border-color' => '#E7E7E7',
                
                
                         /* TABLE */
                        'tbl-header-color' => '#78b6ff',
                        'tbl-header-lbl-color' => '#ffffff',
                        'tbl-block-color1' => '#f7fcff',
                        'tbl-block-color2' => '#ffffff',
                        'tbl-lbl-color' => '#2c3e50',
                        'tbl-hint-color' => '#d3e7ff',
                        'tbl-hint-lbl-color' => '#78b6ff',
                        'tbl-border-color' => '#ededed',
                
                         /* RADIO */
                        'radio-color' => '#ededed',
                        'radio-border-color' => '#dbd7d7',
                        'radio-selected-color' => '#065e66',
                        'radio-lbl-color' => '#2c3e50',
                
                         /* CHECKBOX */
                        'checkbox-color' => '#ededed',
                        'checkbox-border-color' => '#dbd7d7',
                        'checkbox-selected-color' => '#78b6ff',
                        'checkbox-lbl-color' => '#2c3e50',
                
                         /* TEXTBOX */
                        'tb-color' => '#ffffff',
                        'tb-border-color' => '#dbd7d7',
                        'tb-txt-color' => '#2c3e50',
                        'tb-lbl-color' => '#2c3e50',
                        'tb-error-color' => '#f1b6ca',
                        'tb-error-txt-color' => '#2c3e50',
                
                        /* CALENDAR */
                        'cal-color' => '#ffffff',
                        'cal-border-color' => '#dbd7d7',
                        'cal-lbl-color' => '#2c3e50',
                        'cal-disable-lbl-color' => 'rgba(0,0,0,.5)',
                        'cal-today-color' => '#ededed',
                        'cal-hover-color' => '#ededed',
                        'cal-selected-color' => '#78b6ff',
                        
                         /* SELECTBOX */
                        'sb-color' => '#ededed',
                        'sb-border-color' => '#dbd7d7',
                        'sb-txt-color' => '#2c3e50',
                        'sb-selected-color' => '#78b6ff',
                        'sb-selected-txt-color' => '#ffffff',
                        'sb-hover-color' => '#ededed',
                        'sb-hover-txt-color' => '#2c3e50',
                
                
                         /* IMAGE SELECTION */
                        'isb-color' => '#ffffff',
                        'isb-txt-color' => '#2c3e50',
                        'isb-selected-color' => '#065e66',
                        'isb-selected-txt-color' => '#ffffff',
                        'isb-hover-color' => '#ffffff',
                        'isb-hover-txt-color' => '#2c3e50',
                
                         /* SLIDER */
                        'slider-line-color' => '#6ca5e5',
                        'slider-default-color' => '#6ca5e5',
                        'slider-default-selected-color' => '#ffffff',
                        'slider-lbl-color' => '#2c3e50',
                        'slider-option-color' => '#6ca5e5'
                        
                    ]
                ])
            ]  
        ]);
    }
}
