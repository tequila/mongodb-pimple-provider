<?php

namespace Tequila\Silex\Provider\Tests;

use PHPUnit\Framework\TestCase;
use Pimple\Container;
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

    public function testMongoDBCofigDefaultConnectionNameReturnsFirstConnectionNameIfNotSpecified()
    {
        $app = $this->createApp();

        $this->assertEquals('default', $app['mongodb.config.default_connection_name']);
    }

    public function testMongoDBCofigDefaultConnectionNameReturnsFirstConnectionNameIfNotSpecified2()
    {
        $app = $this->createApp();
        $app['mongodb.options.connections'] = ['first' => []];

        $this->assertEquals('first', $app['mongodb.config.default_connection_name']);
    }

    public function testMongoDBCofigDefaultConnectionNameReturnsOptionIfSpecified()
    {
        $app = $this->createApp();
        $app['mongodb.options.default_connection'] = 'defaultConnection';

        $this->assertEquals('defaultConnection', $app['mongodb.config.default_connection_name']);
    }

    public function testMongoDBCofigDefaultConnectionNameReturnsOptionIfSpecified2()
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

    private function createApp()
    {
        $app = new Container();
        $app->register(new MongoDBServiceProvider());

        return $app;
    }
}