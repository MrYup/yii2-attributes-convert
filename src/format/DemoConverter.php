<?php
namespace ModelAttributeConverted\format;


use yii\base\BaseObject;

/**
 * Class DemoConverter
 * @package ModelAttributeConverted\format
 */
class DemoConverter extends BaseObject implements IFormatConverter{


    public function dbValue($modelValue)
    {
        return $modelValue;
    }

    public function modelValue($dbValue)
    {
        return $dbValue;
    }

    public function hasDbValueChanged($oldDbVal,$newDbVal):bool
    {
        return $oldDbVal !== $newDbVal;
    }
}

?>