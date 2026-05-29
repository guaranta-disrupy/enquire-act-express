<?php

namespace App\Objects;

class Response
{
    public $rts = [];

    public $index, $info, $prime, $is_taken;

    public $test_id, $record_id;

    function __construct($info)
    {
        $this->rts = $info['rts'];
        $this->index = $info['index'];
        $this->test_id = $info['test_id'];
        $this->info = $info['info'];
        $this->record_id = $info['record_id'];
        $this->prime = $info['prime'];
        $this->is_taken = $info['is_taken'];
    }
}