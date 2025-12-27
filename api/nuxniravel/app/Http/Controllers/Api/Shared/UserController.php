<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        // Create a new user
        $user = User::create($validatedData);

        // Redirect to the user's profile page
        return redirect()->route('users.show', $user->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:6',
        ]);

        // Update the user
        $user->update($validatedData);

        // Redirect back to the user's profile page
        return redirect()->route('users.show', $user->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        // Delete the user
        $user->academies()->delete();
        $user->academyAdmins()->delete();
        $user->academyMembers()->delete();


        $user->courses()->delete();
        // $user->courses()->lessons()->delete();
        // $user->courses()->quizzes()->delete();
        // $user->courses()->assignments()->delete();

        $user->courseAdmins()->delete();
        $user->courseGroups()->delete();
        $user->coursesGroupMembers()->delete();
        $user->courseMembers()->delete();

        $user->assignmentAnswers()->delete();
        $user->answerQuestions()->delete();

        $user->courseQuizzes()->delete();
        $user->courseQuizResults()->delete();

        $user->lessons()->delete();
        $user->lessons()->images()->delete();

        
        // $user->courseProgresses()->delete();
        
        $user->delete();
        
        //response json
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);

        // Redirect to the user listing page
        // return redirect()->route('users.index');
    }

}
