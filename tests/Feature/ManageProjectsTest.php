<?php

namespace Tests\Feature;

use App\Models\Project;
use Facades\Tests\Arrangements\ProjectArrangement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ManageProjectsTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /** @test */
    public function guests_cannot_manage_projects()
    {
        $project = ProjectArrangement::create();

        $this->get(route('projects.index'))->assertRedirect('login');
        $this->get($project->path())->assertRedirect('login');
        $this->get("{$project->path()}/edit")->assertRedirect('login');
        $this->get(route('projects.create'))->assertRedirect('login');
        $this->post('/projects', $project->toArray())->assertRedirect('login');
        $this->patch($project->path(), [])->assertRedirect('login');
        $this->delete($project->path())->assertRedirect('login');
    }

    /** @test */
    public function an_authenticated_user_cannot_manage_the_projects_of_others()
    {
        $this->signIn();

        $project = ProjectArrangement::create();

        $this->get($project->path())->assertStatus(403);
        $this->get("{$project->path()}/edit")->assertStatus(403);
        $this->patch($project->path(), ['title' => 'title'])->assertStatus(403);
        $this->delete($project->path())->assertStatus(403);
    }

    /** @test */
    function members_cannot_delete_projects()
    {
        $user = $this->signIn();

        $project = ProjectArrangement::create();

        $project->invite($user);

        $this->actingAs($user)->delete($project->path())->assertStatus(403);
    }

    /** @test */
    public function a_user_can_create_a_project()
    {
        $this->signIn();

        $this->get(route('projects.create'))->assertOk();

        $this->followingRedirects()
            ->post(route('projects.store'), $attributes = factory(Project::class)->raw())
            ->assertSee($attributes['title'])
            ->assertSee($attributes['notes'])
            ->assertSee($attributes['description']);
    }

    /** @test */
    public function a_user_can_update_a_project()
    {
        $project = ProjectArrangement::create(['notes' => 'General notes.']);

        $attributes = factory(Project::class)->raw(['owner_id' => $project->owner]);

        $this->signIn($project->owner);

        $this->get("{$project->path()}/edit")->assertOk();
        $this->patch($project->path(), $attributes)->assertRedirect($project->path());
        $this->assertDatabaseHas('projects', $attributes);
    }

    /** @test */
    public function a_user_can_delete_a_project()
    {
        $project = ProjectArrangement::create();

        $this->actingAs($project->owner)
            ->delete($project->path())
            ->assertRedirect('projects');

        $this->assertDatabaseMissing('projects', $project->only('id'));
    }

    /** @test */
    public function a_user_can_update_a_projects_general_notes()
    {
        $project = ProjectArrangement::create(['notes' => 'General notes.']);

        $this->actingAs($project->owner)
             ->patch($project->path(), $notes = ['notes' => 'Updated notes']);

        $this->assertDatabaseHas('projects', $notes);
    }

    /** @test */
    public function a_user_can_view_their_project()
    {
        $project = ProjectArrangement::create();

        $this->actingAs($project->owner)
            ->get($project->path())
            ->assertSee($project->title);
    }

    /** @test */
    public function a_user_can_see_all_projects_they_have_been_invited_to_on_their_dashboard()
    {
        $project = tap(ProjectArrangement::create())->invite($this->signIn());

        $this->get(route('projects.index'))->assertSee($project->title);
    }

    /** @test */
    public function a_project_requires_a_title()
    {
        $attributes = factory(Project::class)->raw(['title' => '']);

        $this->signIn();

        $this->post(route('projects.store'), $attributes)
            ->assertSessionHasErrors('title');
    }

    /** @test */
    public function task_can_be_included_as_part_a_new_project_creation()
    {
        $attributes = factory(Project::class)->raw();

        $attributes['tasks'] = [
            ['body' => 'Task 1'],
            ['body' => 'Task 2'],
            ['body' => ''],
        ];

        $this->signIn();

        $this->post(route('projects.store'), $attributes);

        $this->assertCount(2, Project::first()->tasks);
    }
}
