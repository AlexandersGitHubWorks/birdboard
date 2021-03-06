<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;

class ProjectsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $projects = auth()->user()->accessibleProjects();

        return view('projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('projects.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  ProjectRequest $request
     * @return mixed
     */
    public function store(ProjectRequest $request)
    {
        $attributes = $request->validated();

        $project = auth()->user()->projects()->create($attributes);

        if ($task = request('tasks')) {
            $project->addTasks($task);
        }

        if (request()->wantsJson()) {
            return ['path' => $project->path()];
        }

        return redirect($project->path());
    }

    /**
     * Display the specified resource.
     *
     * @param  Project $project
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Project $project)
    {
        $this->authorize('owner-or-member', $project);

        $project->load('tasks');

        return view('projects.show')->with(compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Project $project
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(Project $project)
    {
        $this->authorize('owner-or-member', $project);

        return view('projects.edit')->with(compact('project'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Project $project
     * @param ProjectRequest $form
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(ProjectRequest $form, Project $project)
    {
        return redirect($form->save()->path());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Project $project
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Project $project)
    {
        $this->authorize('manage', $project);

        $project->delete();

        return redirect()->route('projects.index');
    }
}
