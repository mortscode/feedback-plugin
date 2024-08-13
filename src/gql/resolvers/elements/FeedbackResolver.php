<?php

namespace mortscode\feedback\gql\resolvers\elements;

use craft\gql\base\ElementResolver;

use mortscode\feedback\elements\FeedbackElement;
use mortscode\feedback\helpers\Gql as GqlHelper;

class FeedbackResolver extends ElementResolver
{
    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        if ($source === null) {
            // If this is the beginning of a resolver chain, start fresh
            $query = FeedbackElement::find();
        } else {
            // If not, get the prepared element query
            $query = $source->$fieldName;
        }

        // Return the query if it’s preloaded
        if (is_array($query)) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            if (method_exists($query, $key)) {
                $query->$key($value);
            } elseif (property_exists($query, $key)) {
                $query->$key = $value;
            } else {
                // Catch custom field queries
                $query->$key($value);
            }
        }

        // Don’t return anything that’s not allowed
        if (!GqlHelper::canQueryFeedback()) {
            return [];
        }

        return $query;
    }
}


