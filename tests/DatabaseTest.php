<?php
/**
 * This unit tests are based on work of Alexander Kochetov (@creocoder) and original yii2 tests
 */

namespace DevGroup\TagDependencyHelper\tests;

use DevGroup\TagDependencyHelper\NamingHelper;
use DevGroup\TagDependencyHelper\tests\models\Post;
use DevGroup\TagDependencyHelper\tests\models\PostNoTrait;
use Yii;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;
use yii\helpers\Url;
use yii\web\Application;
use yii\db\Connection;
use yii\web\ServerErrorHttpException;

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
                ],
            ],
        ]));
        try {
            Yii::$app->set('db', [
                'class' => Connection::className(),
                'dsn' => 'mysql:host=localhost;dbname=yii2_tagdependency',
                'username' => 'root',
                'password' => '',
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

        $this->assertEquals($post->className().'[CommonTag]', NamingHelper::getCommonTag($post));
        $this->assertEquals($post->className().'[ObjectTag:'.$post->id.']', NamingHelper::getObjectTag($post, $post->id));
    }
}
