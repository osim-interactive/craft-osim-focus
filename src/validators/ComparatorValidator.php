<?php
namespace osim\craft\focus\validators;

use yii\validators\Validator;

class ComparatorValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if (!in_array($value, [
            'exact',
            'contains',
            'notContains',
            'startsWith',
            'notStartsWith',
            'endsWith',
            'notEndsWith',
        ])) {
            $model->$attribute = 'exact';
        }
    }
}
