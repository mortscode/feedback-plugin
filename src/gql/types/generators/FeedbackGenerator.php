<?php

namespace mortscode\feedback\gql\types\generators;

use Craft;
use craft\gql\base\Generator;
use craft\gql\base\SingleGeneratorInterface;
use mortscode\feedback\elements\FeedbackElement;
use mortscode\feedback\gql\types\elements\FeedbackType;
use mortscode\feedback\gql\interfaces\elements\FeedbackInterface;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeManager;

class FeedbackGenerator extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        // Feedback items have no context
        $type = static::generateType($context);
        return [$type->name => $type];
    }

    public static function generateType(mixed $context): mixed
    {
        $context = $context ?: Craft::$app->getFields()->getLayoutByType(FeedbackElement::class);

        $typeName = FeedbackElement::gqlTypeNameByContext(null);
        $contentFieldGqlTypes = self::getContentFields($context);

        $feedbackFields = Craft::$app->getGql()->prepareFieldDefinitions(array_merge(
            FeedbackInterface::getFieldDefinitions(),
            $contentFieldGqlTypes
        ), $typeName);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new FeedbackType([
            'name' => $typeName,
            'fields' => function() use ($feedbackFields) {
                return $feedbackFields;
            },
        ]));
    }
}
