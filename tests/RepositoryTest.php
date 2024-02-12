<?php

namespace DealNews\Repository\Tests;

use \DealNews\Repository\Repository;

/**
 * Repository Tests
 *
 * @author      Brian Moon <brianm@dealnews.com>
 * @copyright   1997-Present DealNews.com, Inc
 * @package     Repository
 * @group       unit
 */
class RepositoryTest extends \PHPUnit\Framework\TestCase {
    public function testCache() {
        $db = new StorageMock();

        $repo = new Repository();
        $repo->register(
            'test',
            [$db, 'load'],
            [$db, 'save']
        );

        $value = $repo->save('test', ['name' => 'Foo']);

        $this->assertSame(
            [
                'name' => 'Foo',
                'id'   => 1,
            ],
            $value
        );

        // Write data outside of the repository
        $db->save(
            [
                'name' => 'Bar',
                'id'   => 1,
            ],
        );

        $this->assertSame(
            [
                1 => [
                    'name' => 'Bar',
                    'id'   => 1,
                ],
            ],
            $db->data
        );

        $cached = $repo->get('test', 1);

        // Should still be Foo
        $this->assertSame(
            [
                'name' => 'Foo',
                'id'   => 1,
            ],
            $cached
        );

        $not_cached = $repo->get('test', 1, false);

        // Should be Bar
        $this->assertSame(
            [
                'name' => 'Bar',
                'id'   => 1,
            ],
            $not_cached
        );

        $cached = $repo->get('test', 1);

        // Should now be Bar since we loaded without cache
        $this->assertSame(
            [
                'name' => 'Bar',
                'id'   => 1,
            ],
            $cached
        );
    }

    public function testLoading() {
        $repo = new Repository();
        $repo->register('test1', function ($ids) {
            $values = [];
            foreach ($ids as $id) {
                $values[$id] = "Value $id";
            }

            return $values;
        });
        $data = $repo->getMulti('test1', [1, 2, 3]);
        $this->assertEquals(
            [
                1 => 'Value 1',
                2 => 'Value 2',
                3 => 'Value 3',
            ],
            $data
        );

        $data = $repo->get('test1', 1);
        $this->assertEquals(
            'Value 1',
            $data
        );
    }

    public function testNotFound() {
        $repo = new Repository();
        $repo->register('test1', function ($ids) {
            return false;
        });
        $data = $repo->getMulti('test1', [1, 2, 3]);
        $this->assertEquals(
            [],
            $data
        );
    }

    public function testSingleLookup() {
        global $lookups;
        $lookups = 0;
        $handler = function ($ids) {
            global $lookups;
            $lookups++;
            $values = [];
            foreach ($ids as $id) {
                $values[$id] = "Value $id";
            }

            return $values;
        };

        $repo = new Repository();
        $repo->register('test1', $handler);
        $data = $repo->getMulti('test1', [1, 2, 3]);
        $this->assertEquals(
            [
                1 => 'Value 1',
                2 => 'Value 2',
                3 => 'Value 3',
            ],
            $data
        );
        $data = $repo->getMulti('test1', [1, 2, 3]);
        $this->assertEquals(
            [
                1 => 'Value 1',
                2 => 'Value 2',
                3 => 'Value 3',
            ],
            $data
        );
        $this->assertEquals(1, $lookups);
    }

    public function testOrder() {
        $repo = new Repository();
        $repo->register('test1', function ($ids) {
            $values = [];
            foreach ($ids as $id) {
                $values[$id] = "Value $id";
            }

            return $values;
        });
        $data = $repo->getMulti('test1', [1, 2, 3]);
        $this->assertEquals(
            [
                1 => 'Value 1',
                2 => 'Value 2',
                3 => 'Value 3',
            ],
            $data
        );
        $data = $repo->getMulti('test1', [4, 5, 6]);
        $this->assertEquals(
            [
                4 => 'Value 4',
                5 => 'Value 5',
                6 => 'Value 6',
            ],
            $data
        );
        $data = $repo->getMulti('test1', [4, 2, 1]);
        $this->assertEquals(
            [
                4 => 'Value 4',
                2 => 'Value 2',
                1 => 'Value 1',
            ],
            $data
        );
    }

    public function testWriting() {
        $db = new StorageMock();

        $repo = new Repository();
        $repo->register(
            'test1',
            [$db, 'load'],
            [$db, 'save']
        );
        $data = $repo->getMulti('test1', [1, 2, 3]);
        $this->assertEquals(
            [],
            $data
        );
        $repo->saveMulti(
            'test1',
            [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
            ]
        );

        $data = $repo->getMulti('test1', [1, 2, 3]);
        $this->assertEquals(
            [
                1 => ['id' => 1],
                2 => ['id' => 2],
                3 => ['id' => 3],
            ],
            $data
        );

        // ensure the data was sent to the storage system
        $data = $db->load([1, 2, 3]);
        $this->assertEquals(
            [
                1 => ['id' => 1],
                2 => ['id' => 2],
                3 => ['id' => 3],
            ],
            $data
        );
    }

    public function testWritingNoId() {
        $db = new StorageMock();

        $repo = new Repository();
        $repo->register(
            'test1',
            [$db, 'load'],
            [$db, 'save']
        );
        $obj1 = $repo->save('test1', ['id' => 1]);
        $obj2 = $repo->save('test1', ['foo' => 2]);

        // ensure the repository did not save the null id
        $ref  = new \ReflectionObject($repo);
        $prop = $ref->getProperty('storage');
        $prop->setAccessible(true);
        $values = $prop->getValue($repo);

        $this->assertEquals(
            [
                $obj1,
                $obj2,
            ],
            array_values($values['test1'])
        );

        // ensure the data was sent to the storage system
        $this->assertEquals(
            [
                $obj1,
                $obj2,
            ],
            array_values($db->data)
        );
    }

    public function testNoReadHandler() {
        $this->expectException('LogicException');
        $repo = new Repository();
        $data = $repo->getMulti('test1', [1, 2, 3]);
    }

    public function testNoWriteHandler() {
        $this->expectException('LogicException');
        $repo = new Repository();
        $data = $repo->save('test1', 'foo');
    }

    public function testRespondsForType() {
        $repo = new Repository();
        $repo->register('test1', function ($ids) {
            return true;
        });

        $repo->register(
            'test3',
            function ($ids) {
                return true;
            },
            function ($ids) {
                return $ids;
            }
        );

        $this->assertTrue($repo->respondsForType('test1'));
        $this->assertFalse($repo->respondsForType('test2'));

        $this->assertTrue($repo->respondsForType('test1', Repository::HANDLE_READ));
        $this->assertFalse($repo->respondsForType('test1', Repository::HANDLE_WRITE));

        $this->assertTrue($repo->respondsForType('test3', Repository::HANDLE_WRITE));
    }
}
