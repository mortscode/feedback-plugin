<?php

namespace mortscode\feedback\helpers;

use craft\helpers\Gql as GqlHelper;

class Gql extends GqlHelper
{
    // Public Methods
    // =========================================================================

    public static function canQueryFeedback(): bool
    {
        $allowedEntities = GqlHelper::extractAllowedEntitiesFromSchema();

        return isset($allowedEntities['feedback']);
    }
}