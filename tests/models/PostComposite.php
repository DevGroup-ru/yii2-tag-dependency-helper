<?php

namespace DevGroup\TagDependencyHelper\tests\models;

use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;

/**
 * Class Post
 * @property integer $author_id
 * @property string $text
 */
class PostComposite extends \yii\db\ActiveRecord
{
    use TagDependencyTrait;

    public function behaviors()
    {
        return [
            'CacheableActiveRecord' => [
                'class' => CacheableActiveRecord::className(),
            ],
        ];
    }

    protected function cacheCompositeTagFields() {
        return ['author_id'];
    }

    public static function tableName()
    {
        return '{{%post}}';
    }

}