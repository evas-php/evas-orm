<?php
namespace Evas\Orm\tests;
use Codeception\Util\Autoload;

Autoload::addNamespace('Evas\\Base', 'vendor/evas-php/evas-base/src');
Autoload::addNamespace('Evas\\Db', 'vendor/evas-php/evas-db/src');
Autoload::addNamespace('Evas\\Db\\tests\\help', 'vendor/evas-php/evas-db/tests/help');
Autoload::addNamespace('Evas\\Orm', 'vendor/evas-php/evas-orm/src');
Autoload::addNamespace('Evas\\Orm\\tests\\help', 'vendor/evas-php/evas-orm/tests/help');

namespace Evas\Orm\tests\ActiveRecordTest;
use Evas\Orm\ActiveRecord;

class User extends ActiveRecord {
    public $id;
    public $name;
    public $email;
}
class Auth extends ActiveRecord {
    public $id;
    public $user_id;
}

namespace Evas\Orm\tests;
use Evas\Base\App;
use Evas\Db\Interfaces\DatabaseInterface;
use Evas\Db\Interfaces\QueryBuilderInterface;
use Evas\Orm\tests\ActiveRecordTest\User;
use Evas\Orm\tests\ActiveRecordTest\Auth;
use Evas\Db\tests\help\DatabaseTestUnit;

// Устанавливаем конфиг DI.

/**
 * Тест ActiveRecord.
 */
class ActiveRecordTest extends DatabaseTestUnit
{
    // Вспомогательные свойства и методы

    const TEST_USER_DATA = [
        'name' => 'Egor',
        'email' => 'egor@evas-php.com',
    ];

    const UPDATED_USER_DATA = [
        'name' => 'Updated name',
        'email' => 'Updated email',
    ];

    protected function _before()
    {
        if (!App::di()->has('db')) {
            App::di()->loadDefinitions(
                __DIR__ . '/_config/di_tests_config.php'
            );
        }
        parent::_before();
    }

    protected function _after()
    {
        parent::_after();
        // очищаем состояния IdentityMap
        User::getDb()->identityMapClear();
    }

    protected function db(): DatabaseInterface
    {
        return App::db();
    }

    protected function insertUser(array $userData = null): User
    {
        return User::insert($userData ?? static::TEST_USER_DATA);
    }

    // Тесты

    /**
     * Тест получения соеденния с БД.
     */
    public function testGetDb()
    {
        $this->assertTrue(User::getDb() instanceof DatabaseInterface);
    }

    /**
     * Тест получения первичного ключа таблицы.
     */
    public function testPrimaryKey()
    {
        $this->assertEquals('id', User::primaryKey());
    }

    /**
     * Тест получения колонок таблицы.
     */
    public function testColumns()
    {
        $this->assertEquals(['id', 'name', 'email'], User::columns());
    }

    /**
     * Тест создания записи.
     */
    public function testCreate()
    {
        $user0 = new User(static::TEST_USER_DATA);
        $user1 = User::create(static::TEST_USER_DATA);
        $this->assertEquals($user0, $user1);
    }

    /**
     * Тест заполнения записи.
     */
    public function testFill()
    {
        $user = new User;
        $user->fill(static::TEST_USER_DATA);
        $this->assertEquals(static::TEST_USER_DATA['name'], $user->name);
        $this->assertEquals(static::TEST_USER_DATA['email'], $user->email);
        $this->assertEquals(static::TEST_USER_DATA, $user->getUpdatedProperties());
    }

    /**
     * Тест получения свойств записи.
     */
    public function testGetRowProperties()
    {
        $user = new User(static::TEST_USER_DATA);
        $this->assertEquals(static::TEST_USER_DATA, $user->getRowProperties());
    }

    /**
     * Тест получения измененных свойств записи.
     */
    public function testGetUpdatedProperties()
    {
        $user = new User(static::TEST_USER_DATA);
        $this->assertEquals(static::TEST_USER_DATA, $user->getUpdatedProperties());
    }

    /**
     * Тест создания записи со вставкой.
     */
    public function testInsert()
    {
        $user = $this->insertUser();
        $this->assertEquals(1, $user->id);
    }

    /**
     * Тест сохранения записи (insert || update в зависимости от наличия id у записи)
     */
    public function testSave()
    {
        codecept_debug(User::getDb()->identityMap()->getStates());
        $this->assertEmpty(User::findById(1));

        $createdUser = User::create(static::TEST_USER_DATA);
        $this->assertEquals(static::TEST_USER_DATA, $createdUser->getUpdatedProperties());
        $this->assertEquals(static::TEST_USER_DATA['name'], $createdUser->name);
        $this->assertEquals(static::TEST_USER_DATA['email'], $createdUser->email);

        // test insert
        $insertedUser = $createdUser->save();
        $this->assertEquals($createdUser, $insertedUser);
        $this->assertEquals(spl_object_hash($createdUser), spl_object_hash($insertedUser));

        $this->assertEquals(static::TEST_USER_DATA['name'], $insertedUser->name);
        $this->assertEquals(static::TEST_USER_DATA['email'], $insertedUser->email);

        $findedUser = User::findById($insertedUser->id);
        $this->assertEquals($insertedUser, $findedUser);
        $this->assertEquals(spl_object_hash($insertedUser), spl_object_hash($findedUser));
        
        codecept_debug(User::getDb()->identityMap()->getStates());

        // test update
        $insertedUser->fill(static::UPDATED_USER_DATA);
        $this->assertEquals(static::UPDATED_USER_DATA, $insertedUser->getUpdatedProperties());
        $this->assertEquals(static::UPDATED_USER_DATA['name'], $insertedUser->name);
        $this->assertEquals(static::UPDATED_USER_DATA['email'], $insertedUser->email);
        $updatedUser = $findedUser->save();

        codecept_debug(User::getDb()->identityMap()->getStates());

        $findedUser = User::findById($updatedUser->id);
        $this->assertEquals(static::UPDATED_USER_DATA['name'], $findedUser->name);
        $this->assertEquals(static::UPDATED_USER_DATA['email'], $findedUser->email);
        $this->assertEquals($updatedUser, $findedUser);
        $this->assertEquals(spl_object_hash($updatedUser), spl_object_hash($findedUser));
        $this->assertEquals(spl_object_hash($createdUser), spl_object_hash($findedUser));
    }

    /**
     * Тест удаления записи.
     */
    public function testDelete()
    {
        $this->assertEmpty(User::findById(1));
        // insert
        $insertedUser = $this->insertUser();

        // check insert
        $findedUser = User::findById($insertedUser->id);
        $this->assertNotEmpty($findedUser);
        $this->assertEquals($insertedUser, $findedUser);
        $this->assertEquals(spl_object_hash($insertedUser), spl_object_hash($findedUser));

        codecept_debug(User::getDb()->identityMap()->getStates());

        // delete & check
        $deletedUser = $insertedUser->delete();
        $this->assertEmpty(User::findById(1));

        codecept_debug(User::getDb()->identityMap()->getStates());

        // check IdentityMap
        $this->assertEquals($deletedUser, $findedUser);
        $this->assertEquals(spl_object_hash($deletedUser), spl_object_hash($findedUser));
        $this->assertEquals($deletedUser, $insertedUser);
        $this->assertEquals(spl_object_hash($deletedUser), spl_object_hash($insertedUser));

        $this->assertEmpty($findedUser->id);
        $this->assertEmpty($deletedUser->id);
        $this->assertEmpty($insertedUser->id);
    }

    /**
     * Тест метода find.
     */
    public function testFind()
    {
        $this->assertTrue(User::find() instanceof QueryBuilderInterface);

        $users = User::find()->where('id > ?', [3])->limit(10)->query()->classObject(User::class);
        $users = User::find()->where('id > ?', [3])->limit(10)->query();
        $users = User::find()->where('id > ?', [3])->query(10)->classObject(User::class);
        $users = User::find()->where('id > ?', [3])->query(10);
        $users = User::find(function () {
            return $this->where('id > ?', [3])->limit(10);
        });
    }

    /**
     * Тест поиска записи по id.
     */
    public function testFindId()
    {
        $this->assertEmpty(User::findById(1));
        $insertedUser = $this->insertUser();
        $this->assertEquals(1, $insertedUser->id);
        $findedUser = User::findById(1);
        $this->assertTrue($findedUser instanceof User);
        $this->assertEquals(1, $findedUser->id);
        // проверяем что IdentityMap разрулил копию записи
        $this->assertEquals(spl_object_hash($insertedUser), spl_object_hash($findedUser));
    }

    /**
     * Тест поиска записей по id.
     */
    public function testFindIds()
    {
        $this->assertEmpty(User::findById(1,2));
        $insertedUser1 = $this->insertUser();
        $insertedUser2 = $this->insertUser(static::UPDATED_USER_DATA);
        $this->assertEquals(1, $insertedUser1->id);
        $this->assertEquals(2, $insertedUser2->id);
        $findedUsers = User::findById(1,2);
        $this->assertTrue(is_array($findedUsers));
        $this->assertEquals(2, count($findedUsers));

        list($findedUser1, $findedUser2) = $findedUsers;
        $this->assertTrue($findedUser1 instanceof User);
        $this->assertTrue($findedUser2 instanceof User);
        $this->assertEquals(1, $findedUser1->id);
        $this->assertEquals(2, $findedUser2->id);
        // проверяем что IdentityMap разрулил копию записи
        $this->assertEquals(spl_object_hash($insertedUser1), spl_object_hash($findedUser1));
        $this->assertEquals(spl_object_hash($insertedUser2), spl_object_hash($findedUser2));
    }

    /**
     * Тест поиска по sql.
     */
    public function testFindSql()
    {
        $this->assertEmpty(User::find()->query()->rowCount());
        $insertedUser1 = $this->insertUser();
        $insertedUser2 = $this->insertUser(static::UPDATED_USER_DATA);
        $this->assertEquals(2, User::table()->lastInsertId());
        $this->assertEquals(2, User::table()->maxId());
        $this->assertEquals(2, User::find()->query()->rowCount());

        $findedUser = User::findBySql('SELECT * FROM `users` WHERE id = ? LIMIT 1', [1]);
        $this->assertNotEmpty($findedUser);
        $this->assertEquals($findedUser, $insertedUser1);
        $this->assertEquals(spl_object_hash($findedUser), spl_object_hash($insertedUser1));

        $findedUsers = User::findBySql('SELECT * FROM `users`');
        $this->assertNotEmpty($findedUsers);
        $this->assertEquals($findedUsers[0], $insertedUser1);
        $this->assertEquals($findedUsers[0], $findedUser);
        $this->assertEquals($findedUsers[1], $insertedUser2);
        $this->assertEquals(spl_object_hash($findedUsers[0]), spl_object_hash($insertedUser1));
        $this->assertEquals(spl_object_hash($findedUsers[1]), spl_object_hash($insertedUser2));
    }
}
