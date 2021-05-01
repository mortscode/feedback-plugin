<?php

namespace mortscode\feedback\fields;

use craft\base\ElementInterface;
use craft\base\Field;

/**
 *
 * @property-read string $contentColumnType
 */
class TotalRatings extends Field {
    // Human readable name
    public static function displayName(): string
    {
        return 'Total Ratings';
    }

    public function getContentColumnType(): string
    {
        return 'int';
    }

    protected function inputHtml($value, ElementInterface $element = null): string
    {
        return $value ?? 'No Ratings';
    }
}
