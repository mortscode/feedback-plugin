<?php

namespace mortscode\feedback\gql\interfaces\elements;

use mortscode\feedback\gql\types\generators\FeedbackGenerator;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\TypeManager;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InterfaceType;
use craft\gql\GqlEntityRegistry;

class FeedbackInterface extends ElementInterface
{
    public static function getTypeGenerator(): string
    {
        return FeedbackGenerator::class;
    }

    public static function getName(): string
    {
        return 'FeedbackInterface';
    }

    public static function getType($fields = null): Type
    {
        // Return the type if it’s already been created
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        // Otherwise, create the type via the entity registry, which handles prefixing
        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all widgets.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        FeedbackGenerator::generateTypes();

        return $type;
    }

    public static function getFieldDefinitions(): array
    {
        // Add our custom widget’s field to common ones for all elements
        return TypeManager::prepareFieldDefinitions(array_merge(
            parent::getFieldDefinitions(),
            [
                'entryId' => [
                    'name' => 'entryId',
                    'type' => Type::id(),
                    'description' => 'The id for the feedback\'s related entry.'
                ],
                'name' => [
                    'name' => 'name',
                    'type' => Type::string(),
                    'description' => 'The full name for the feedback\'s author.'
                ],
                'rating' => [
                    'name' => 'rating',
                    'type' => Type::int(),
                    'description' => 'The rating of the feedback.'
                ],
                'comment' => [
                    'name' => 'comment',
                    'type' => Type::string(),
                    'description' => 'The actual comment text.'
                ],
                'response' => [
                    'name' => 'response',
                    'type' => Type::string(),
                    'description' => 'The CP response to the comment.'
                ],
                'feedbackType' => [
                    'name' => 'feedbackType',
                    'type' => Type::string(),
                    'description' => 'The type of feedback, question or review'
                ],
                'feedbackStatus' => [
                    'name' => 'feedbackStatus',
                    'type' => Type::string(),
                    'description' => 'The status of the feedback [approved, pending, spam]'
                ],
            ]
        ), self::getName());
    }
}