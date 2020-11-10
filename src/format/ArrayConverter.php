<?php
namespace ModelAttributeConverted\format;


use yii\base\BaseObject;
use yii\di\Instance;

/**
 * Class ArrayConverter
 * @package ModelAttributeConverted\format
 */
class ArrayConverter extends BaseObject implements IFormatConverter{

    public function dbValue($modelValue)
    {
        if (is_array($modelValue) || is_object($modelValue)){
            return json_encode($modelValue);
        }
        return $modelValue;

    }

    public function modelValue($dbValue)
    {
        $v = json_decode($dbValue,true);
        return $v?$v:[];
    }

    public function hasDbValueChanged($oldDbVal,$newDbVal):bool
    {
        return $oldDbVal !== $newDbVal;
    }

}

?>