<?php

namespace DevGroup\TagDependencyHelper\tests\models;

use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;

/**
 * Class PostNoTrait
 * @property integer $author_id
 * @property string $text
 */
class PostNoTrait extends \yii\db\ActiveRecord
{

    public function behaviors()
    {
        return [
            'CacheableActiveRecord' => [
                'class' => CacheableActiveRecord::className(),
            ],
        ];
    }


    public static function tableName()
    {
        return '{{%post}}';
    }

}