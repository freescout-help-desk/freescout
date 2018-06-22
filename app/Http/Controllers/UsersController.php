<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        // todo: check if current user can edit this profile
        // $this->user()->can('update', $comment)
        $user = User::findOrFail($id);
        $users = User::all();

        return view('users/profile', ['user' => $user, 'users' => $users]);
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:191|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveProfile(Request $request)
    {
        $this->validator($request->all())->validate();

        //event(new Registered($user = $this->create($request->all())));
        // session()
        \Session::flash('flash_success', 'Profile saved successfully');

        return redirect('user.profile');
    }

}
