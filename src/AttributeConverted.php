<?php
namespace ModelAttributeConverted;

use yii\base\InvalidConfigException;
use ModelAttributeConverted\format\ArrayConverter;
use ModelAttributeConverted\format\IFormatConverter;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;

/**
 * @property BaseActiveRecord $owner
 * Class AttributeConverted
 * @package ModelAttributeConverted
 */
class AttributeConverted extends Behavior {


    const ARRAY_CONVERTER = 'Array';

    const BASE64_CONVERTER = 'Base64';

    private  $default_converters = [
        'Array' => ArrayConverter::class,
    ];

    /**
     * which converter attribute needs,key-value
     * if string but not an exists class,the converter would map one of @see $default_converters,
     * if class name or an array has key 'class',the converter would be create by @see \Yii::createObject()
     * we often set attribute converter config like this:
     *
     * [
     *      'post_comment_id' => 'Array',
     *      'password' => 'Base64',
     *      'token'    => YourTokenConverter::class,
     *      'goods_id' => [
     *          'class' => YourGoodsIdConverterClass::class
     *      ],
     *
     * ]
     * @var array
     */
    public $cast = [];

    public function events()
    {
        return [
            $this->owner::EVENT_AFTER_FIND => [$this,'convertModelFormat'],
            $this->owner::EVENT_BEFORE_VALIDATE => [$this,'convertDbFormat'],
            $this->owner::EVENT_AFTER_UPDATE => [$this,'convertModelFormat'],
            $this->owner::EVENT_AFTER_INSERT => [$this,'convertModelFormat'],

        ];
    }


    /**
     * convert attribute value as model format
     * @throws ConvertedException
     * @throws InvalidConfigException
     */
    public final function convertModelFormat(){
        if (!is_array($this->cast)){
            return;
        }

        foreach ($this->cast as $name => $converterConf){
            $converter = $this->converterInstance($converterConf);

            $old = $this->owner->getOldAttribute($name);

            $this->owner->$name = $converter->modelValue($old);
        }
    }

    /**
     * convert attribute value as db format
     * @throws ConvertedException
     * @throws InvalidConfigException
     */
    public final function convertDbFormat(){

        if (!is_array($this->cast)){
            return;
        }
        foreach ($this->cast as $name => $converterConf){
            $converter = $this->converterInstance($converterConf);

            $newValue = $converter->dbValue($this->owner->$name);
            $this->owner->$name = $newValue;

        }

    }


    /**
     * Return converter singleton instance according attribute converted config @see $cast
     * @param $converterConf
     * @return IFormatConverter|Object
     * @throws ConvertedException
     * @throws InvalidConfigException
     */
    private function converterInstance($converterConf){

        if (is_string($converterConf)){

            if (!empty($this->default_converters[$converterConf])){
                $class = $this->default_converters[$converterConf];
            }else{
                $class = $converterConf;
            }
            $params = [];

        }elseif (is_array($converterConf)){
            if (!isset($converterConf['class'])){
                throw new ConvertedException("column config must specific a class name");
            }
            $class = $converterConf['class'];
            $params = array_diff($converterConf,['class']);


        }else{
            throw new ConvertedException('converter config invalid, only string or array accepted');
        }

        //singleton
        if (\Yii::$container->hasSingleton($class)){
            $converter =  \Yii::$container->get($class,$params);
        }else{
            $converter = \Yii::createObject($class,$params);
            \Yii::$container->setSingleton($class,$converter);
        }


        if (!$converter instanceof IFormatConverter){
            \Yii::$container->clear($class);
            throw new ConvertedException("converter class must instance of ".IFormatConverter::class);
        }

        return $converter;
    }

    /**
     * Remove the converter of attributes
     * @param $name
     * @return BaseActiveRecord
     * @throws ConvertedException
     * @throws InvalidConfigException
     */
    public function removeColumnConverter($name){
        if ($this->isColumnCasted($name)){
            $converter = $this->converterInstance($this->cast[$name]);

            $this->owner->$name = $converter->dbValue($this->owner->$name);
        }

        unset($this->cast[$name]);
        return $this->owner;
    }

    /**
     * Set a converter for attribute
     * @param $name
     * @param $converterConf
     * @return BaseActiveRecord
     * @throws ConvertedException
     * @throws InvalidConfigException
     */
    public function addColumnConverter($name,$converterConf){
        $converter = $this->converterInstance($converterConf);
        $this->owner->$name = $converter->modelValue($this->owner->$name);

        $this->cast[$name] = $converterConf;
        return $this->owner;
    }


    /**
     * Return whether the attribute is converted
     * @param $name
     * @return bool
     */
    public function isColumnCasted($name){
        return array_key_exists($name,$this->cast);
    }

    /**
     * Returns whether the attribute is changed compared to AFTER_FIND.
     * if the attribute not casted,just call parent::isAttributeChanged(),
     * otherwise,compare the value after converted in db format with the value AFTER_FIND
     * @see BaseActiveRecord::isAttributeChanged();
     * @param $name
     * @param bool $identical
     * @return bool
     * @throws ConvertedException
     * @throws InvalidConfigException
     */
    public function isAttributeChangedWithConverter($name,$identical = true){
        if (!$this->isColumnCasted($name)){
            return $this->owner->isAttributeChanged($name,$identical);
        }

        $converter = $this->converterInstance($this->cast[$name]);
        $oldByConverted = $converter->dbValue($this->owner->$name);
        $oldAfterFind = $this->owner->getOldAttribute($name);

        if ($identical){
            return $oldAfterFind !== $oldByConverted;
        }
        return $oldAfterFind != $oldByConverted;

    }

    /**
     * Returns old values of each attributes AFTER_FIND,
     * if the attribute not casted,just call parent::getOldAttribute(),
     * otherwise,attribute value would be converted to model format.
     * @see BaseActiveRecord::getOldAttribute()
     * @param $name
     * @return mixed|null
     * @throws ConvertedException
     * @throws InvalidConfigException
     */
    public function getOldAttributeWithConverter($name)
    {
        if (!$this->isColumnCasted($name)){
            return $this->owner->getOldAttribute($name);
        }

        $converter = $this->converterInstance($this->cast[$name]);
        $old = $this->owner->getOldAttribute($name);
        return $converter->modelValue($old);
    }

    /**
     * @see getOldAttributeWithConverter()
     * @return array
     * @throws ConvertedException
     * @throws InvalidConfigException
     */
    public function getOldAttributesWithConverter()
    {
        $oldAttrs = [];
        foreach (array_keys($this->owner->attributes) as $name){
            $oldAttrs[$name] = $this->getOldAttributeWithConverter($name);
        }

        return $oldAttrs;
    }


    /**
     * If the attribute is casted, indication of modified would bases on isAttributeChangedWithConverter,
     * otherwise indicated by parent::getDirtyAttributes
     * @see isAttributeChangedWithConverter()
     * @see parent::getDirtyAttributes()
     * @param null $names
     * @return array
     * @throws ConvertedException
     * @throws InvalidConfigException
     */
    public function getDirtyAttributesWithConverter($names = null)
    {
        $dirty =  $this->owner->getDirtyAttributes($names);
        $reallyDirty = [];

        foreach ($dirty as $name =>$value){

            if (!$this->isColumnCasted($name)){
                $reallyDirty[$name] = $value;
                continue;
            }

            if($this->isAttributeChangedWithConverter($name)){
                $converter = $this->converterInstance($this->cast[$name]);
                $reallyDirty[$name] = $converter->dbValue($value);
            }else{
                continue;
            }

            $reallyDirty[$name] = $value;
        }

        return $reallyDirty;
    }
}
