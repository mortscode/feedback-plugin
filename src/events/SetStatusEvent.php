<?php

namespace mortscode\feedback\events;

use yii\base\Event;

class SetStatusEvent extends Event
{
    // Properties
    // =========================================================================

    public $response;
    public $status;
}