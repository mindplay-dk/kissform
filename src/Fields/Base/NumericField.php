<?php

namespace mindplay\kissform\Fields\Base;

use mindplay\kissform\Facets\ValidatorInterface;
use mindplay\kissform\Fields\TextField;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckInt;
use mindplay\kissform\Validators\CheckMaxValue;
use mindplay\kissform\Validators\CheckMinValue;
use mindplay\kissform\Validators\CheckRange;

/**
 * Abstract base class for integer, floating-point and fixed-precision numeric Field types.
 */
abstract class NumericField extends TextField
{
    /**
     * @var int|float|null minimum value
     */
    public $min_value;

    /**
     * var int|float|null maximum value
     */
    public $max_value;

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        $defaults = [];

        if ($this->max_length) {
            $defaults['maxlength'] = $this->max_length;
        }

        if ($this->min_value) {
            $defaults['min'] = $this->min_value;
        }

        if ($this->max_value) {
            $defaults['max'] = $this->max_value;
        }

        return $renderer->inputFor($this, 'number', $attr + $defaults);
    }

    /**
     * @return ValidatorInterface
     */
    abstract protected function createTypeValidator();
    
    /**
     * @inheritdoc
     */
    public function createValidators()
    {
        $validators = parent::createValidators();
        
        $validators[] = $this->createTypeValidator();

        if ($this->min_value !== null) {
            if ($this->max_value !== null) {
                $validators[] = new CheckRange($this->min_value, $this->max_value);
            } else {
                $validators[] = new CheckMinValue($this->min_value);
            }
        } else if ($this->max_value !== null) {
            $validators[] = new CheckMaxValue($this->max_value);
        }

        return $validators;
    }
}
