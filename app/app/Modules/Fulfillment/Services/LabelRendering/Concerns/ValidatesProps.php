<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering\Concerns;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;

/**
 * Reusable validator factory for FieldType implementations. Field unit tests
 * extend PHPUnit\Framework\TestCase (no Laravel container), so the static
 * Validator facade is unavailable — this trait constructs the factory directly.
 */
trait ValidatesProps
{
    private function validatorFactory(): ValidatorFactory
    {
        static $factory = null;
        if ($factory === null) {
            $factory = new ValidatorFactory(new Translator(new ArrayLoader, 'en'));
        }

        return $factory;
    }
}
