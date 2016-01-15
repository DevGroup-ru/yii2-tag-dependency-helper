<?php
/**
 * This unit tests are based on work of Alexander Kochetov (@creocoder) and original yii2 tests
 */

namespace DevGroup\TagDependencyHelper\tests;

use DevGroup\TagDependencyHelper\NamingHelper;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use DevGroup\TagDependencyHelper\tests\models\Post;
use DevGroup\TagDependencyHelper\tests\models\PostComposite;
use DevGroup\TagDependencyHelper\tests\models\PostCompositeNoOverride;
use DevGroup\TagDependencyHelper\tests\models\PostNoTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\db\Connection;

/**
 * DatabaseTestCase
 */
class DatabaseTest extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * @inheritdoc
     */
    public function getConnection()
    {
        return $this->createDefaultDBConnection(\Yii::$app->getDb()->pdo);
    }

    /**
     * @inheritdoc
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(__DIR__ . '/data/test.xml');
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        (new \yii\web\Application([
            'id' => 'unit',
            'basePath' => __DIR__,
            'bootstrap' => ['log'],
            'components' => [
                'log' => [
                    'traceLevel' => 10,
                    'targets' => [
                        [
                            'class' => 'yii\log\FileTarget',
                            'levels' => ['info'],
                        ],
                    ],
                ],
                'request' => [
                    'cookieValidationKey' => 'wefJDF8sfdsfSDefwqdxj9oq',
                    'scriptFile' => __DIR__ .'/index.php',
                    'scriptUrl' => '/index.php',
                ],
                'cache' => [
                    'class' => '\yii\caching\FileCache',
                    'as lazy' => [
                        'class' => 'DevGroup\TagDependencyHelper\LazyCache',
                    ],
                ],
            ],
        ]));
        try {
            Yii::$app->set('db', [
                'class' => Connection::className(),
                'dsn' => 'mysql:host=localhost;dbname=yii2_tagdependency',
                'username' => 'root',
                'password' => '', // TODO: IF password is empty, error database auth in vagrant
            ]);

            Yii::$app->getDb()->open();
            $lines = explode(';', file_get_contents(__DIR__ . '/migrations/mysql.sql'));

            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    Yii::$app->getDb()->pdo->exec($line);
                }
            }
        } catch (\Exception $e) {
            Yii::$app->clear('db');
        }


        if (Yii::$app->get('db', false) === null) {
            $this->markTestSkipped();
        } else {
            parent::setUp();
        }
        Yii::$app->cache->flush();
    }

    /**
     * Clean up after test.
     * By default the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->destroyApplication();
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        if (\Yii::$app && \Yii::$app->has('session', true)) {
            \Yii::$app->session->close();
        }
        \Yii::$app = null;
    }

    public function testWarnings()
    {
        try {
            new PostNoTrait();
        } catch (InvalidConfigException $e) {
            $this->assertTrue(true);
            return;
        }
        $this->assertTrue(false);
    }

    public function testActiveRecord()
    {
        $posts = Post::find()->all();
        $this->assertEquals(3, count($posts));

        $post = Post::loadModel(4);
        $this->assertNull($post);
        try {
            $post = Post::loadModel(4, false, true, 86400, new \Exception("test"));
            $this->assertNull($post);
        } catch (\Exception $e) {
            $this->assertEquals('test', $e->getMessage());
        }

        $post = new Post();
        $post->author_id = 1;
        $post->text = 'fourth post';
        $this->assertTrue($post->save());

        $post = Post::loadModel(4);
        $this->assertNotNull($post);
        $this->assertEquals(1, $post->author_id);

        Yii::$app->db->createCommand("UPDATE {{post}} SET author_id=2 WHERE id=4")->execute();
        $post = Post::loadModel(4);
        // the same author id should because of not invalidated cache
        $this->assertEquals(1, $post->author_id);
        // invalidate

        $post->invalidateTags();

        //reload model
        $post = Post::loadModel(4);
        $this->assertEquals(2, $post->author_id);
        // change to 2
        $post->author_id = 3;
        $post->save();

        //reload model
        $post = Post::loadModel(4);
        $this->assertEquals(3, $post->author_id);

        Yii::$app->db->createCommand("UPDATE {{post}} SET author_id=8 WHERE id=4")->execute();

        $post = Post::loadModel(4);
        $this->assertEquals(3, $post->author_id);

        TagDependency::invalidate(
            Yii::$app->cache,
            [
                NamingHelper::getObjectTag($post->className(), $post->id)
            ]
        );
        $post = Post::loadModel(4);
        $this->assertEquals(8, $post->author_id);

        $post->delete();

        $post = Post::loadModel(4);
        $this->assertNull($post);

        $post = Post::loadModel('', true);
        $this->assertTrue($post->isNewRecord);

        try {
            Post::loadModel('', false, false, 0, new \Exception("test2"));
        } catch (\Exception $e) {
            $this->assertEquals('test2', $e->getMessage());
        }

        $this->assertNull(Post::loadModel('', false));

        $this->assertEquals(
            $post->className().'[CommonTag]',
            NamingHelper::getCommonTag($post)
        );
        $this->assertEquals(
            $post->className().'[ObjectTag:'.$post->id.']',
            NamingHelper::getObjectTag($post, $post->id)
        );
    }



    public function testLazyCache()
    {
        $changed = false;
        /** @var \yii\caching\Cache|\DevGroup\TagDependencyHelper\LazyCache $cache */
        $cache = Yii::$app->cache;
        $val = $cache->lazy(function() use(&$changed) {
            $changed = true;
            return 182;
        }, 'LazyTest', 3600);
        $this->assertEquals(182, $val);
        $this->assertTrue($changed);

        // don't clear and check again
        $changed = false;
        $val = $cache->lazy(function() use(&$changed) {
            $changed = true;
            return 182;
        }, 'LazyTest', 3600);
        $this->assertEquals(182, $val);
        $this->assertFalse($changed);

        // clear and check again
        $cache->delete('LazyTest');
        $changed = false;
        $val = $cache->lazy(function() use(&$changed) {
            $changed = true;
            return 182;
        }, 'LazyTest', 3600);
        $this->assertEquals(182, $val);
        $this->assertTrue($changed);
    }

    public function testCompositeTag()
    {
        $id_author = 3;
        $text_for_update = 'Composite???';

        /* Tests for not configured settings of composite tags */
        $query = PostCompositeNoOverride::find()->where(['author_id' => $id_author]);
        $tag_name = [NamingHelper::getCompositeTag(PostCompositeNoOverride::className(), ['author_id' => $id_author])];

        /* @var TagDependencyTrait|ActiveRecord $post */
        $post = PostCompositeNoOverride::getDb()->cache(
            function ($db) use ($query) {
                return $query->one($db);
            },
            0,
            new TagDependency(
                [
                    'tags' => $tag_name
                ]
            )
        );

        $this->assertNotNull($post);
        $this->assertNotEquals($text_for_update, $post->text);
        $this->assertNotEquals($post->objectCompositeTag(), $tag_name);

        /* Tests for configured settings of composite tags */
        $id_author = 2;

        $query = PostComposite::find()->where(['author_id' => $id_author]);
        $tag_name = [NamingHelper::getCompositeTag(PostComposite::className(), ['author_id' => $id_author])];

        $post = PostComposite::getDb()->cache(
            function ($db) use ($query) {
                return $query->one($db);
            },
            0,
            new TagDependency(
                [
                    'tags' => $tag_name
                ]
            )
        );

        $this->assertNotEquals($text_for_update, $post->text);
        $this->assertEquals($post->objectCompositeTag(), $tag_name);

        /* Tests for invalidates composite tags */
        $post->text = $text_for_update;
        $post->save();

        $post = PostComposite::getDb()->cache(
            function ($db) use ($query) {
                return $query->one($db);
            },
            0,
            new TagDependency(
                [
                    'tags' => $tag_name
                ]
            )
        );

        $this->assertEquals($text_for_update, $post->text);

        $this->assertEquals(
            'DevGroup\TagDependencyHelper\tests\models\PostComposite[CompositeTag(author_id):(2)]',
            NamingHelper::getCompositeTag($post, ['author_id' => $id_author])
        );

        $exceptionThrown = false;
        try {
            NamingHelper::getCompositeTag([], []);
        } catch (InvalidParamException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);

        $exceptionThrown = false;
        try {
            NamingHelper::getObjectTag([], 1);
        } catch (InvalidParamException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);

        $exceptionThrown = false;
        try {
            NamingHelper::getCommonTag([]);
        } catch (InvalidParamException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);
    }

}
