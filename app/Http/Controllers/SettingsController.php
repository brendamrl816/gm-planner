<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Requests\UserEditRequest;
use App\Style;
use App\User;
use App\Calendar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests;
use App\Http\Controllers\Controller;


use Response;
use Input;
use DB;
use Redirect;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    
  
    
    public function index()
    {
       $user_id = Auth::id();
       $styles = Style::where('user_id', '=', $user_id)->first();
        
        return Response::json($styles);
    }
    

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        $id = Auth::id();
        $user = User::whereId($id)->firstOrFail();
        
        return view('settings.userSettings', compact('user'));
    }
    
    public function update_user(UserEditRequest $request){
        $id = Auth::id();
        $password = $request->get('old_password');
        
        if (Auth::attempt(array('id' => $id, 'password' => $password)))
        {
            $name = DB::table('users')->where('id', '=', $id)->value('first_name');
            
            $newName = $request->get('first_name');
            
            $user = User::whereId($id)->firstOrFail();
            $user->first_name = $request->get('first_name');
            $user->last_name = $request->get('last_name');
            $user->dob = $request->get('dob');
            $user->email = $request->get('email');
            $new_password = $request->get('password');
                if($new_password != "") {
                    $user->password = Hash::make($new_password);
                }
            $user->save();
                                      
            $calendar = Calendar::where('user_id', '=', $id)->firstOrFail();
            $calendar->name = $request->get('first_name');
            $calendar->save();
            
            
            return redirect(action('SettingsController@edit'))->with('status', 'Your account settings have been updated!');  
        }
        else{
            return redirect(action('SettingsController@edit'))->with('status', 'your current password is invalid!');
        }
        
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $theme_name = Input::get('theme_name');
        $body_backgroundColor = Input::get('body_backgroundColor');
        $body_color = Input::get('body_color');
        $body_fontFamily = Input::get('body_fontFamily');
        $buttons_backgroundColor=Input::get('buttons_backgroundColor');
        $buttons_borderColor=Input::get('buttons_borderColor');
        $navBar_backgroundColor=Input::get('navBar_backgroundColor');
        $navBar_color=Input::get('navBar_color');
        $navBar_borderColor=Input::get('navBar_borderColor');
        $menuModal_backgroundColor=Input::get('menuModal_backgroundColor');
        
        DB::table('styles')->where('id', '=', $id)->update([
            'theme_name'=> $theme_name,
            'body_backgroundColor'=> $body_backgroundColor,
            'body_color'=>  $body_color,
            'body_fontFamily'=> $body_fontFamily,
            'buttons_backgroundColor'=>$buttons_backgroundColor,
            'buttons_borderColor'=> $buttons_borderColor,
            'navBar_backgroundColor'=> $navBar_backgroundColor,
            'navBar_color'=> $navBar_color,
            'navBar_borderColor'=> $navBar_borderColor,
            'menuModal_backgroundColor'=> $menuModal_backgroundColor
        ]);
        
        
        return Response::json(array('success'=>true));
    }
    
    
    
    public function deleteAccount(){
        $id = Auth::id();
        $user = User::whereId($id)->firstOrFail();
        return view('settings.deleteAccount', compact('user'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyAccount(Request $request)
    {   $id = Auth::id();
        $password = $request->get('password');
        
        if (Auth::attempt(array('id' => $id, 'password' => $password)))
        {
            $email = DB::table('users')->where('id', '=', $id)->value('email');
            $name = DB::table('users')->where('id', '=', $id)->value('first_name');
            $event_id = DB::table('events')->where('user_id', '=', $id)->value('id');
            $list_id = DB::table('todolists')->where('user_id', '=', $id)->value('id');
            
            DB::table('repetitions')->where('event_id', '=', $event_id)->delete();
            DB::table('eventchanges')->where('event_id', '=', $event_id)->delete();        
            DB::table('events')->where('user_id', '=', $id)->delete();
            DB::table('tasks')->where('todolist_id', '=', $list_id)->delete();
            DB::table('todolists')->where('user_id', '=', $id)->delete();
            DB::table('role_user')->where('user_id', '=', $id)->delete();
            
            DB::table('users')->where('id', '=', $id)->delete();
            $emaildata = array(
                    'name'=>$name);
            
            Mail::send('emails.deleteAccount', $emaildata, function($message) use($email){
            $message->from('gmplanner.team@gmail.com', 'gmPlanner');
            $message->to($email)->subject('Account');
            });
           
            return redirect(action('PagesController@home'));  
        }
        else{
            return redirect(action('SettingsController@deleteAccount'))->with('status', 'your password is invalid!');
        }
        
    }
}
