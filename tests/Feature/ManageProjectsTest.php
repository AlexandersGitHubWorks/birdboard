<?php

namespace Tests\Feature;

use App\Project;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ManageProjectsTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /** @test */
    public function guests_cannot_manage_projects()
    {
        $project = factory(Project::class)->create();

        $this->get(route('projects.index'))->assertRedirect('login');
        $this->get($project->path())->assertRedirect('login');
        $this->get(route('projects.create'))->assertRedirect('login');
        $this->post('/projects', $project->toArray())->assertRedirect('login');
    }

    /** @test */
    public function a_user_can_create_a_project()
    {
        $this->actingAs(factory(User::class)->create());

        $this->get(route('projects.create'))->assertStatus(200);

        $attributes = [
            'title'       => $this->faker->sentence,
            'description' => $this->faker->paragraph,
        ];

        $this->post(route('projects.store'), $attributes)->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', $attributes);
        $this->get(route('projects.index'))->assertSee($attributes['title']);
    }

    /** @test */
    public function a_user_can_view_their_project()
    {
        $this->actingAs(factory(User::class)->create());

        $this->withoutExceptionHandling();

        $project = factory(Project::class)->create(['owner_id' => auth()->id()]);

        $this->get($project->path())
            ->assertSee($project->title)
            ->assertSee($project->description);
    }

    /** @test */
    public function an_authenticated_user_cannot_view_the_projects_of_others()
    {
        $this->actingAs(factory(User::class)->create());

        $project = factory(Project::class)->create();

        $this->get($project->path())->assertStatus(403);
    }

    /** @test */
    public function a_project_requires_a_title()
    {
        $this->actingAs(factory(User::class)->create());

        $attributes = factory(Project::class)->raw(['title' => '']);

        $this->post(route('projects.store'), $attributes)->assertSessionHasErrors('title');
    }
}