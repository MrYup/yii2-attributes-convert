# Soft delete
Attribute value converted between db format and Yii2 model format

Require yiisoft/yii2


## Installation

```sh
composer require yiisoft-custom/yii2-model-attribute-convert
```

# Notice & Important
Case the attribute  set an converter in cast,it would be converted to model format while trigger `EVENT_AFTER_FIND`,
and the model value strictly is already  changed (e.g: db value format is json  `[1,2,3]`,with 'Array' converter , model format would be`array(1,2,3)`).  the result of these method calls would be effected
:
```
isAttributeChanged(),
getOldAttribute(),
getOldAttributes(),
getDirtyAttributes()
```
But don't worry,we gotta the suitable calls instead while any attribute of model casted with a converter,list of mapper:
```
isAttributeChangedWithConverter()   -> isAttributeChanged()
getOldAttributeWithConverter()      -> getOldAttribute()
getOldAttributesWithConverter()     -> getOldAttributes()
getDirtyAttributesWithConverter()   -> getDirtyAttributes()
```


# Usage 
```php
<?php
class Test extends ActiveRecord
{


    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['attribute_convert'] = [
            'class' => AttributeConverted::class,
            'cast' => [
                'order_no' => 'Array',
            ]
        ];

        return $behaviors;
    }
}

?>


```

Just used like behavoir class. Property `$cast` is key-value(eg: `'order_no' => 'Array'`). `Key` is the attribute needs to be  converted,and `value` is the converter,class name or string maps a class name would be fine
