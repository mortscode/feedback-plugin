<?php

namespace mortscode\feedback\helpers;


use Craft;

class RequestHelpers
{
    /**
     * Returns the average rating for a given entry ID
     *
     * @return bool
     */
    public static function isCpRequest(): bool {
        $request = Craft::$app->getRequest()->isCpRequest;
        $userIsAdmin = Craft::$app->getUser()->getIsAdmin();

        return $request && $userIsAdmin;
    }
}