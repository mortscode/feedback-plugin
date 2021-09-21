<?php
namespace mortscode\feedback\gql\arguments\elements;

use craft\gql\base\StructureElementArguments;

use GraphQL\Type\Definition\Type;

class FeedbackArguments extends StructureElementArguments
{
    // Public Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'entryId' => [
                'name' => 'entryId',
                'type' => Type::int(),
                'description' => 'Narrows the query results based on the related Entry Id.'
            ],
            'hasComment' => [
                'name' => 'hasComment',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results whether the comment field is empty.'
            ],
            'feedbackStatus' => [
                'name' => 'feedbackStatus',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the feedback status.'
            ],
            'feedbackType' => [
                'name' => 'feedbackType',
                'type' => Type::string(),
                'description' => 'Narrows the query results based on the type of feedback.'
            ],
        ]);
    }
}
