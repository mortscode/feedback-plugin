<?php

namespace mortscode\feedback\fields;

use craft\base\ElementInterface;
use craft\base\Field;

/**
 *
 * @property-read string $contentColumnType
 */
class AverageRating extends Field {
    // Human readable name
    public static function displayName(): string
    {
        return 'Average Rating';
    }

    public function getContentColumnType(): string
    {
        return 'float(2, 1)';
    }

    protected function inputHtml($value, ElementInterface $element = null): string
    {
        return $value ?? '';
    }
}
