<?php

namespace mortscode\feedback\helpers;


use Craft;

class RequestHelpers
{
    /**
     * Returns whether a request is from the CP
     *
     * @return bool
     */
    public static function isCpRequest(): bool {
        $request = Craft::$app->getRequest()->isCpRequest;
        $userIsAdmin = Craft::$app->getUser()->getIsAdmin();

        return $request && $userIsAdmin;
    }
}