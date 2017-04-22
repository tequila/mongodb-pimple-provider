<?php

namespace Tequila\Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tequila\MongoDB\Client;

class MongoDBServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['mongodb.config.default_connection_name'] = function (Container $app) {
            if (isset($app['mongodb.options.default_connection'])) {
                return $app['mongodb.options.default_connection'];
            }

            $app['mongodb.connections.options_initializer']();
            $options = $app['mongodb.options.connections'];

            // first connection name is the default
            return key($options);
        };

        $app['mongodb.config.default_db_name'] = function (Container $app) {
            if (isset($app['mongodb.options.default_db'])) {
                return $app['mongodb.options.default_db'];
            }

            $app['mongodb.dbs.options_initializer']();
            $options = $app['mongodb.options.dbs'];

            // first database alias is the default
            return key($options);
        };

        $app['mongodb.connections.options_initializer'] = $app->protect(function() use ($app) {
            static $optionsInitialized = false;

            if ($optionsInitialized) {
                return;
            }

            $defaultOptions = [
                'uri' => 'mongodb://127.0.0.1/',
                'uriOptions' => [],
                'driverOptions' => [],
            ];

            if (empty($app['mongodb.options.connections'])) {
                if (isset($app['mongodb.options.connection'])) {
                    $defaultConnectionOptions = $app['mongodb.options.connection'];
                } else {
                    $defaultConnectionOptions = $defaultOptions;
                }

                $app['mongodb.options.connections'] = [
                    'default' => $defaultConnectionOptions,
                ];
            }

            $connectionsOptions = $app['mongodb.options.connections'];
            foreach ($connectionsOptions as $name => &$connectionOptions) {
                $connectionOptions = array_replace($defaultOptions, $connectionOptions);
            }
            $app['mongodb.options.connections'] = $connectionsOptions;

            $optionsInitialized = true;
        });

        $app['mongodb.dbs.options_initializer'] = $app->protect(function() use ($app) {
            static $optionsInitialized = false;

            if ($optionsInitialized) {
                return;
            }

            if (empty($app['mongodb.options.dbs'])) {
                if (isset($app['mongodb.options.db'])) {
                    $defaultDbOptions = $app['mongodb.options.db'];
                } else {
                    $defaultDbOptions = [];
                }

                $app['mongodb.options.dbs'] = [
                    'default' => $defaultDbOptions,
                ];
            }

            $dbsOptions = $app['mongodb.options.dbs'];
            $defaultOptions = [
                'connection' => $app['mongodb.config.default_connection_name'],
                'options' => [],
            ];
            foreach ($dbsOptions as $name => &$dbOptions) {
                $dbOptions = array_replace($defaultOptions, ['name' => $name], $dbOptions);
            }
            $app['mongodb.options.dbs'] = $dbsOptions;

            $optionsInitialized = true;
        });

        $app['mongodb.clients'] = function (Container $app) {
            $app['mongodb.connections.options_initializer']();

            $clients = new Container();
            foreach ($app['mongodb.options.connections'] as $name => $options) {
                $clients[$name] = function () use ($options) {
                    return new Client(
                        $options['uri'],
                        $options['uriOptions'],
                        $options['driverOptions']
                    );
                };
            }

            return $clients;
        };

        $app['mongodb.client'] = function (Container $app) {
            $clients = $app['mongodb.clients'];

            // return Client, whose name is first in mongodb.options.connections
            return $clients[$app['mongodb.config.default_connection_name']];
        };

        $app['mongodb.dbs'] = function(Container $app) {
            $app['mongodb.dbs.options_initializer']();

            $dbs = new Container();
            foreach ($app['mongodb.options.dbs'] as $name => $options) {
                $dbs[$name] = function () use ($app, $name, $options) {
                    if (!isset($app['mongodb.clients'][$options['connection']])) {
                        throw new \LogicException(
                            sprintf(
                                'There is no configured connection "%s" for database "%s".',
                                $options['connection'],
                                $name
                            )
                        );
                    }

                    $client = $app['mongodb.clients'][$options['connection']];

                    /** @var Client $client */
                    return $client->selectDatabase($options['name'], $options['options']);
                };
            }

            return $dbs;
        };

        $app['mongodb.db'] = function (Container $app) {
            $dbAlias = $app['mongodb.config.default_db_name'];

            return $app['mongodb.dbs'][$dbAlias];
        };
    }
}