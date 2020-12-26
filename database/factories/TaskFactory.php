<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Task;
use App\Project;
use Faker\Generator as Faker;

$factory->define(Task::class, function (Faker $faker) {
    return [
        'body'       => $faker->sentence,
        'finished'   => false,
        'due'        => now()->addDays(rand(1, 5)),
        'project_id' => factory(Project::class),
    ];
});
