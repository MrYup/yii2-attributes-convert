<?php
namespace ModelAttributeConverted\format;

/**
 * Interface IFormatConverter
 * @package ModelAttributeConverted\format
 */
interface IFormatConverter{

    /**
     * value in db row
     * @param $modelValue
     * @return mixed
     */
    public function dbValue($modelValue);

    /**
     * value needs by model
     * @param $dbValue
     * @return mixed
     */
    public function modelValue($dbValue);

    public function hasDbValueChanged($oldDbVal,$newDbVal):bool;

}



?>