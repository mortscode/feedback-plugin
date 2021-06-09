<?php

namespace mortscode\feedback\fields;

use craft\base\ElementInterface;
use craft\base\Field;

/**
 *
 * @property-read string $contentColumnType
 */
class TotalPending extends Field {
    // Human readable name
    public static function displayName(): string
    {
        return 'Total Pending';
    }

    public function getContentColumnType(): string
    {
        return 'int';
    }

    protected function inputHtml($value, ElementInterface $element = null): string
    {
        return $value ?? 'Nothing Pending';
    }
}
