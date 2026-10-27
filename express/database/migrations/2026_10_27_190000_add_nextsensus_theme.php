<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tema NextSensus como tema 1 (o design_id padrão dos questionários).
 *
 * A tabela `themes` estava vazia no servidor e a tela de temas quebrava. Mesmas
 * chaves do tema Default do ThemeSeeder, com a paleta do Fluent: verde de ação,
 * grafite no texto, vermelho só para erro e sem imagem de fundo externa.
 */
return new class extends Migration
{
    public function up()
    {
        if (DB::table('themes')->where('id', 1)->exists()) {
            return;
        }

        $ink = '#0b0c0e';
        $action = '#257556';
        $actionHover = '#1d5e45';
        $line = '#e4e7e8';
        $soft = '#f4f5f5';

        $design = [
            /* BACKGROUND */
            'background-color' => '#ffffff',
            'background-img' => '',
            'background-img-brightness' => '100%',

            'question-margin-top' => '14vmin',
            'question-margin-bottom' => '14vmin',
            'question-margin-left' => '8vmin',
            'question-margin-right' => '8vmin',
            'question-txt-color' => $ink,

            /* BUTTONS */
            'button-color' => $action,
            'button-hover-color' => $actionHover,
            'button-txt-color' => '#ffffff',

            /* WARNING */
            'warning-msg-color' => '#c24848',
            'warning-msg-txt-color' => '#ffffff',
            'warning-msg-border-color' => '#9e3434',

            /* CHOICE BOX */
            'choice-box-color' => '#ffffff',
            'choice-box-border-color' => $line,

            /* TABLE */
            'tbl-header-color' => $action,
            'tbl-header-lbl-color' => '#ffffff',
            'tbl-block-color1' => $soft,
            'tbl-block-color2' => '#ffffff',
            'tbl-lbl-color' => $ink,
            'tbl-hint-color' => '#e3f0e9',
            'tbl-hint-lbl-color' => $action,
            'tbl-border-color' => $line,

            /* RADIO */
            'radio-color' => $soft,
            'radio-border-color' => '#c9cfd1',
            'radio-selected-color' => $action,
            'radio-lbl-color' => $ink,

            /* CHECKBOX */
            'checkbox-color' => $soft,
            'checkbox-border-color' => '#c9cfd1',
            'checkbox-selected-color' => $action,
            'checkbox-lbl-color' => $ink,

            /* TEXTBOX */
            'tb-color' => '#ffffff',
            'tb-border-color' => '#c9cfd1',
            'tb-txt-color' => $ink,
            'tb-lbl-color' => $ink,
            'tb-error-color' => '#f6dede',
            'tb-error-txt-color' => $ink,

            /* CALENDAR */
            'cal-color' => '#ffffff',
            'cal-border-color' => '#c9cfd1',
            'cal-lbl-color' => $ink,
            'cal-disable-lbl-color' => 'rgba(0,0,0,.5)',
            'cal-today-color' => $soft,
            'cal-hover-color' => $soft,
            'cal-selected-color' => $action,

            /* SELECTBOX */
            'sb-color' => $soft,
            'sb-border-color' => '#c9cfd1',
            'sb-txt-color' => $ink,
            'sb-selected-color' => $action,
            'sb-selected-txt-color' => '#ffffff',
            'sb-hover-color' => $line,
            'sb-hover-txt-color' => $ink,

            /* IMAGE SELECTION */
            'isb-color' => '#ffffff',
            'isb-txt-color' => $ink,
            'isb-selected-color' => $action,
            'isb-selected-txt-color' => '#ffffff',
            'isb-hover-color' => $soft,
            'isb-hover-txt-color' => $ink,

            /* SLIDER */
            'slider-line-color' => '#c9cfd1',
            'slider-default-color' => '#c9cfd1',
            'slider-default-selected-color' => '#ffffff',
            'slider-lbl-color' => $ink,
            'slider-option-color' => '#c9cfd1',
        ];

        DB::table('themes')->insert([
            'id' => 1,
            'text' => 'NextSensus',
            'setup' => json_encode(['design_id' => 1, 'design' => $design]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        DB::table('themes')->where('id', 1)->where('text', 'NextSensus')->delete();
    }
};
