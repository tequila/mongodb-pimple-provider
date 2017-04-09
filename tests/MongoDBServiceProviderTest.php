<?php

namespace Tequila\Silex\Provider\Tests;

use MongoDB\Driver\ReadConcern;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Tequila\Bridge\ConnectionConfiguration;
use Tequila\MongoDB\Client;
use Tequila\Silex\Provider\MongoDBServiceProvider;

class MongoDBServiceProviderTest extends TestCase
{
    public function testMongoDBConnectionsOptionsInitializerDoesNothingWhenOptionsProvided()
    {
        $app = $this->createApp();
        $app['mongodb.options.connections'] = ['something'];
        $app['mongodb.connections.options_initializer']();

        $this->assertEquals(['something'], $app['mongodb.options.connections']);
    }

    public function testMongoDBConnectionsOptionsInitializerCreatesDefaultOptions()
    {
        $app = $this->createApp();
        $app['mongodb.connections.options_initializer']();
        $expected = [
            'default' => ['uri' => 'mongodb://127.0.0.1/'],
        ];

        $this->assertEquals($expected, $app['mongodb.options.connections']);
    }

    public function testMongoDBConnectionsOptionsInitializerUsesConnectionOptions()
    {
        $app = $this->createApp();
        $app['mongodb.options.connection'] = ['uri' => 'mongodb://localhost/'];
        $app['mongodb.connections.options_initializer']();
        $expected = [
            'default' => ['uri' => 'mongodb://localhost/'],
        ];

        $this->assertEquals($expected, $app['mongodb.options.connections']);
    }

    public function testMongoDBConnectionsOptionsInitializerRunsOnlyOneTime()
    {
        $app = $this->createApp();
        $app['mongodb.connections.options_initializer']();
        unset($app['mongodb.options.connections']);
        $app['mongodb.connections.options_initializer']();

        $this->assertFalse(isset($app['mongodb.options.connections']));
    }

    public function testMongoDBConfigDefaultConnectionNameReturnsFirstConnectionNameIfNotSpecified()
    {
        $app = $this->createApp();

        $this->assertEquals('default', $app['mongodb.config.default_connection_name']);
    }

    public function testMongoDBConfigDefaultConnectionNameReturnsFirstConnectionNameIfNotSpecified2()
    {
        $app = $this->createApp();
        $app['mongodb.options.connections'] = ['first' => []];

        $this->assertEquals('first', $app['mongodb.config.default_connection_name']);
    }

    public function testMongoDBConfigDefaultConnectionNameReturnsOptionIfSpecified()
    {
        $app = $this->createApp();
        $app['mongodb.options.default_connection'] = 'defaultConnection';

        $this->assertEquals('defaultConnection', $app['mongodb.config.default_connection_name']);
    }

    public function testMongoDBConfigDefaultConnectionNameReturnsOptionIfSpecified2()
    {
        $app = $this->createApp();
        $app['mongodb.options.default_connection'] = 'defaultConnection';
        $app['mongodb.options.connections'] = ['first' => []];

        $this->assertEquals('defaultConnection', $app['mongodb.config.default_connection_name']);
    }

    public function testMongoDBDbsOptionsInitializerDoesNothingWhenOptionsProvided()
    {
        $app = $this->createApp();
        $app['mongodb.options.dbs'] = ['something'];
        $app['mongodb.dbs.options_initializer']();

        $this->assertEquals(['something'], $app['mongodb.options.dbs']);
    }

    public function testMongoDBDbsOptionsInitializerCreatesDefaultOptions()
    {
        $app = $this->createApp();
        $app['mongodb.dbs.options_initializer']();
        $expected = [
            'default' => ['name' => 'default'],
        ];

        $this->assertEquals($expected, $app['mongodb.options.dbs']);
    }

    public function testMongoDBConnectionsOptionsInitializerUsesDbOptions()
    {
        $app = $this->createApp();
        $app['mongodb.options.db'] = ['name' => 'dbname'];
        $app['mongodb.dbs.options_initializer']();
        $expected = [
            'default' => ['name' => 'dbname'],
        ];

        $this->assertEquals($expected, $app['mongodb.options.dbs']);
    }

    public function testMongoDBDbsOptionsInitializerRunsOnlyOneTime()
    {
        $app = $this->createApp();
        $app['mongodb.dbs.options_initializer']();
        unset($app['mongodb.options.dbs']);
        $app['mongodb.dbs.options_initializer']();

        $this->assertFalse(isset($app['mongodb.options.dbs']));
    }

    public function testMongoDBConfigDefaultDbNameReturnsFirstDbAliasIfNotSpecified()
    {
        $app = $this->createApp();

        $this->assertEquals('default', $app['mongodb.config.default_db_name']);
    }

    public function testMongoDBConfigDefaultDbNameReturnsFirstDbAliasIfNotSpecified2()
    {
        $app = $this->createApp();
        $app['mongodb.options.dbs'] = ['first' => []];

        $this->assertEquals('first', $app['mongodb.config.default_db_name']);
    }

    public function testMongoDBConfigDefaultDbNameReturnsOptionIfSpecified()
    {
        $app = $this->createApp();
        $app['mongodb.options.default_db'] = 'defaultDb';

        $this->assertEquals('defaultDb', $app['mongodb.config.default_db_name']);
    }

    public function testMongoDBConfigDefaultDbNameReturnsOptionIfSpecified2()
    {
        $app = $this->createApp();
        $app['mongodb.options.default_db'] = 'defaultDb';
        $app['mongodb.options.connections'] = ['first' => []];

        $this->assertEquals('defaultDb', $app['mongodb.config.default_db_name']);
    }

    public function testMongoDbConfigConnectionsReturnsConfigurationForEachConnection()
    {
        $app = $this->createApp();
        $app['mongodb.options.connections'] = [
            'default' => [],
            'archive' => [],
            'another_one' => ['foo'],
        ];

        foreach ($app['mongodb.options.connections'] as $name => $options) {
            $config = $app['mongodb.config.connections'][$name];
            $this->assertTrue($config instanceof ConnectionConfiguration);
        }
    }

    public function testMongoDbClientsReturnsClientForEachConnectionOption()
    {
        $app = $this->createApp();
        $app['mongodb.options.connections'] = [
            'default' => [],
            'archive' => [],
            'another_one' => ['foo'],
        ];

        foreach ($app['mongodb.options.connections'] as $name => $options) {
            $client = $app['mongodb.clients'][$name];
            $this->assertTrue($client instanceof Client);
        }
    }

    public function testMongoDBClientReturnsDefaultClient()
    {
        $app = $this->createApp();
        $app['mongodb.options.connections'] = [
            'archive' => [],
            'default' => [],
            'another_one' => ['foo'],
        ];

        $app['mongodb.options.default_connection'] = 'default';
        $this->assertSame($app['mongodb.client'], $app['mongodb.clients']['default']);
    }

    private function createApp()
    {
        $app = new Container();
        $app->register(new MongoDBServiceProvider());

        return $app;
    }
}