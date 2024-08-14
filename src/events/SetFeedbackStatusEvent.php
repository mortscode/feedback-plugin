<?php

namespace mortscode\feedback\events;

use yii\base\Event;

class SetFeedbackStatusEvent extends Event
{
    // Properties
    // =========================================================================

    public $response;
    public $status;
}