<?php

namespace Tequila\Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tequila\Bridge\ConnectionConfiguration;
use Tequila\Bridge\Exception\ConfigurationException;
use Tequila\MongoDB\Client;
use Tequila\MongoDB\Manager;

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

            if (empty($app['mongodb.options.connections'])) {
                if (isset($app['mongodb.options.connection'])) {
                    $defaultConnectionOptions = $app['mongodb.options.connection'];
                } else {
                    $defaultConnectionOptions = ['uri' => 'mongodb://127.0.0.1/'];
                }

                $app['mongodb.options.connections'] = [
                    'default' => $defaultConnectionOptions,
                ];
            }

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
                    $defaultDbOptions = ['name' => 'default'];
                }

                $app['mongodb.options.dbs'] = [
                    'default' => $defaultDbOptions,
                ];
            }

            $optionsInitialized = true;
        });

        $app['mongodb.config.connections'] = function (Container $app) {
            $app['mongodb.connections.options_initializer']();

            $config = new Container();
            foreach ($app['mongodb.options.connections'] as $name => $options) {
                $config[$name] = function() use ($name, $options) {
                    return new ConnectionConfiguration($name, $options);
                };
            }

            return $config;
        };

        $app['mongodb.clients'] = function (Container $app) {
            $app['mongodb.connections.options_initializer']();

            $clients = new Container();
            foreach ($app['mongodb.options.connections'] as $name => $options) {
                $clients[$name] = function () use ($app, $name, $options) {
                    /** @var ConnectionConfiguration $config */
                    $config = $app['mongodb.config.connections'][$name];

                    $manager = new Manager(
                        $config->getUri(),
                        $config->getUriOptions(),
                        $config->getDriverOptions()
                    );

                    return new Client($manager, $config->getClientOptions());
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
                    if (!isset($options['name'])) {
                        throw new ConfigurationException(
                            sprintf(
                                'Configuration for database "%s" does not contain required option "name".',
                                $name
                            )
                        );
                    }

                    $dbName = $options['name'];
                    unset($options['name']);

                    $defaultConnectionName = $app['mongodb.config.default_connection_name'];
                    if (!isset($options['connection']) || $options['connection'] === $defaultConnectionName) {
                        $client = $app['mongodb.client'];
                    } elseif (!isset($app['mongodb.clients'][$options['connection']])) {
                        throw new ConfigurationException(
                            sprintf(
                                'There is no configured connection "%s" for database "%s".',
                                $options['connection'],
                                $name
                            )
                        );
                    } else {
                        $client = $app['mongodb.clients'][$options['connection']];
                    }

                    /** @var Client $client */
                    return $client->selectDatabase($dbName, $options);
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