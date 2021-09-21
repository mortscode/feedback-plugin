<?php

namespace mortscode\feedback\gql\queries;

use GraphQL\Type\Definition\Type;
use mortscode\feedback\helpers\Gql as GqlHelper;
use craft\gql\base\Query as BaseQuery;
use mortscode\feedback\gql\interfaces\elements\FeedbackInterface;
use mortscode\feedback\gql\arguments\elements\FeedbackArguments;
use mortscode\feedback\gql\resolvers\elements\FeedbackResolver;

class FeedbackQuery extends BaseQuery
{
    public static function getQueries($checkToken = true): array
    {
        // Make sure the current token’s schema allows querying widgets
        if ($checkToken && !GqlHelper::canQueryFeedback()) {
            return [];
        }

        // Provide one or more query definitions
        return [
            'feedback' => [
                'type' => Type::listOf(FeedbackInterface::getType()),
                'args' => FeedbackArguments::getArguments(),
                'resolve' => FeedbackResolver::class . '::resolve',
                'description' => 'This query is used to query for feedback elements.'
            ],
        ];
    }
}
