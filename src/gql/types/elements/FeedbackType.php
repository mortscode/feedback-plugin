<?php

namespace mortscode\feedback\gql\types\elements;

use mortscode\feedback\gql\interfaces\elements\FeedbackInterface;

use craft\gql\base\ObjectType;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\types\elements\Element;

use GraphQL\Type\Definition\ResolveInfo;

class FeedbackType extends Element
{
    // Public Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            FeedbackInterface::getType(),
        ];

        parent::__construct($config);
    }
}
