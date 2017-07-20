<?php

$app = 'Campaign';
$whitelist = [$app, 'Composer'];

use function Deployer\{has, host, task, run, set, get, add, before, after, localhost, input};

require dirname(__FILE__) . '/vendor/autoload.php';
require dirname(__FILE__) . '/vendor/deployer/deployer/recipe/common.php';
require dirname(__FILE__) . '/vendor/deployphp/recipes/recipe/slack.php';
require dirname(__FILE__) . '/vendor/deployphp/recipes/recipe/rabbit.php';

set('repository', 'git@gitlab.com:remp/remp.git');
set('keep_releases', 4);
set('shared_dirs', ['storage']);
set('shared_files', ['.env']);

localhost('remp2020')
    ->set('deploy_path', '/data/web/remp2020.com/app/Campaign')
    ->set('branch', 'master')
    ->stage('campaign');

task('deploy:vendors', function() {
    if (has('previous_release')) {
        run('cp -R {{previous_release}}/vendor {{release_path}}/vendor');
    }
    run('cd {{release_path}} && {{env_vars}} {{bin/composer}} {{composer_options}}');
});

task('deploy:extract_project', function() use ($app, $whitelist) {
    $grep = '';
    foreach ($whitelist as $name) {
        $grep .= " | grep -v \"{$name}$\"";
    }
    run("find {{release_path}} -mindepth 1 -maxdepth 1 {$grep} | xargs rm -fr");
    run("find {{release_path}}/{$app} -mindepth 1 -maxdepth 1 -exec mv -t {{release_path}} -- {} +");
    run("rmdir {{release_path}}/{$app}");
    run("sed -i -e 's/\.\.\/Composer/.\/Composer/g' {{release_path}}/composer.lock");
})->desc('Monorepo necessary hacks to deploy single project');

task('deploy:migration', function() {
    run("cd {{release_path}}; php artisan migrate");
})->desc('Migrate database');

task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:copy_dirs',
    'deploy:extract_project',
    'deploy:vendors',
    'deploy:shared',
    'deploy:migration',
    'deploy:symlink',
    'cleanup',
])->desc('Deploy your project');
