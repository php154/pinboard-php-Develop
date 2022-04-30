<?php

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Intervention\Pinboard\Commands\SearchCommand;
use Intervention\Pinboard\Commands\SetupCommand;
use Intervention\Pinboard\Commands\StatusCommand;
use Intervention\Pinboard\Commands\SyncCommand;
use Symfony\Component\Console\Application;
use Alfred\Workflows\Workflow as AlfredBuilder;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

function initDotenv(): Dotenv
{
    try {
        // try to load .pinboard in home directory
        $dotenv = Dotenv::createImmutable($_SERVER['HOME'], '.pinboard');
        $dotenv->load();
    } catch (Exception $e) {
        //
    }

    return $dotenv;
}

function initDatabase(): Capsule
{
    $capsule = new Capsule();
    $capsule->addConnection([
            'driver'    => 'sqlite',
            'database'  => DATABASE_FILEPATH,
            'prefix'    => '',
    ]);

    // Make this Capsule instance available
    // globally via static methods... (optional)
    $capsule->setAsGlobal();

    // Setup the Eloquent ORM... (optional;
    // unless you've used setEventDispatcher())
    $capsule->bootEloquent();

    return $capsule;
}

function initPinboardApi(): PinboardAPI
{
    return new PinboardAPI(
        isset($_ENV['PINBOARD_USERNAME']) ? $_ENV['PINBOARD_USERNAME'] : false,
        isset($_ENV['PINBOARD_TOKEN']) ? $_ENV['PINBOARD_TOKEN'] : false,
    );
}

function initApplication(PinboardAPI $api): Application
{
    // app
    $app = new Application('Intervention Pinboard', '2.2');

    // add commands
    $app->add(new SetupCommand());
    $app->add(new StatusCommand());
    $app->add(new SearchCommand(new AlfredBuilder()));
    $app->add(new SyncCommand($api));

    return $app;
}

function initNotExistingDatabase(Application $app): void
{
    if (defined(DATABASE_FILEPATH) && !file_exists(DATABASE_FILEPATH)) {
        // create file
        file_put_contents(DATABASE_FILEPATH, '');
        // setup database
        $app->find('setup')->run(
            new ArrayInput(['command' => 'setup']),
            new NullOutput()
        );
    }
}
