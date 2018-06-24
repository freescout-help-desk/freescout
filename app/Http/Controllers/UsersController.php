<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\User;

class UsersController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Users list
     */
    public function users()
    {
        $users = User::all();

        return view('users/users', ['users' => $users]);
    }

    /**
     * User profile
     */
    public function profile($id)
    {
        $user = User::findOrFail($id);

        $this->authorize('view', $user);

        $users = User::all();

        return view('users/profile', ['user' => $user, 'users' => $users]);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function profileSave($id, Request $request)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:191|unique:users,email,'.$id,
            'emails' => 'max:100',
            'job_title' => 'max:255',
            'phone' => 'max:60',
            'timezone' => 'required|string|max:255',
            'time_format' => 'required',
        ]);

        //event(new Registered($user = $this->create($request->all())));

        if ($validator->fails()) {
            return redirect()->route('users.profile', ['id' => $id])
                        ->withErrors($validator)
                        ->withInput();
        }

        $user->fill($request->all());

        if (empty($request->input('enable_kb_shortcuts'))) {
            $user->enable_kb_shortcuts = false;
        }

        $user->save();

        \Session::flash('flash_success', __('Profile saved successfully'));
        return redirect()->route('users.profile', ['id' => $id]);
    }

}
