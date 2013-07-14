<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/


Route::get("doctors/populate", "DoctorsController@get_populate");
Route::post("doctors/populate", "DoctorsController@post_populate");
Route::get("doctors/expiring", "DoctorsController@get_expiring");
Route::get("doctors/list", "DoctorsController@get_list");

Route::get("doctors/dash", "DoctorsController@get_dash");

Route::get("doctors/index", "DoctorsController@get_index");
Route::get("doctors", "DoctorsController@get_index");
Route::post("doctors/index", "DoctorsController@post_index");
Route::post("doctors", "DoctorsController@post_index");


Route::get('/ORM/{object_name}/new',function($object_name){

        if(class_exists($object_name)){
                $view_data = standard_view_data();
                $object = new $object_name();

                foreach($_GET as $gkey => $gitem){ //sanatize and add to the form...
                        $gkey = mysql_real_escape_string($gkey);
                        $gitem = mysql_real_escape_string($gitem);
                        $object->$gkey = $gitem;
                }

                $view_data['form_json'] = $object->getAlpacaJSON();
                $view_data['object_name'] = $object_name;
                $view_data['view_contents'] = View::make('ormform',$view_data);
                $view_data['menu_contents'] = View::make('menu',$view_data);
                return View::make('html',$view_data);
        }else{
                return "<h1>Cough... sputter... I can't find an ORM called $object_name</h1>";
        }

});

Route::get('/ORM/{object_name}/{number}',function($object_name, $number){

        if(class_exists($object_name)){
                $view_data = standard_view_data();
                $object = $object_name::find($number);
                $view_data['form_json'] = $object->getAlpacaJSON();
                $view_data['object_name'] = $object_name;
                $view_data['view_contents'] = View::make('ormform',$view_data);
                $view_data['menu_contents'] = View::make('menu',$view_data);
                return View::make('html',$view_data);
        }else{
                return "<h1>Cough... sputter... I can't find an ORM called $object_name</h1>";
        }

});

Route::post('/ORM/{object_name}/new',function($object_name, $number = null){



        if(class_exists($object_name)){

                $myObject = new $object_name();
                $input = Input::all();
                unset($input['submit']);


                $myObject = $object_name::create($input);

                //dead code required by laravel 3
                //http://forums.laravel.io/viewtopic.php?pid=34751#p34751
                //$new_id = DB::connection('mysql')->pdo->lastInsertId();
		$new_id = $myObject->id;


                $view_data = standard_view_data();
                $return_me = "<h1> Created new $object_name ($new_id)  </h1>
                <ul>
                        <li><a href='/ORM/$object_name/new'>Add new $object_name</a> </li>
                        <li><a href='/ORM/$object_name/$new_id'>Edit $object_name $new_id</a> </li>
                        <li><a href='/ORM/$object_name/'>Back to $object_name list</a> </li>
                </ul>
                ";
                return(main_html_wrap($return_me));

        }else{
                return "<h1>Cough... sputter... I can't find an ORM called $object_name</h1>";
        }

});

Route::post('/ORM/{object_name}/{number}',function($object_name, $number = null){


        if(class_exists($object_name)){
                $view_data = standard_view_data();
                if(is_null($number)){
                        $return_me .= "<p>Cough sputter... I did not get a number...";
                }else{
                        $myObject = $object_name::find($number);
                        $input = Input::all();
                        unset($input['submit']);
                        foreach($input as $id => $value){
                                if(strpos($id,'is_') !== false){
                                        $input[$id] = true;
                                }
                        }

                        foreach($myObject->get_fields() as $field){
                                if(!isset($input[$field])){
                                        $input[$field] = false; //this means a checkbox was not checked..
                                }
                        }


                        $myObject->fill($input);
                        $myObject->save();

                        $return_me ="<h1> Saved $object_name with $number </h1>
                <ul>
                        <li><a href='/ORM/$object_name/$number'>continue to edit $object_name $number</a></li>
                        <li><a href='/ORM/$object_name/'>return to $object_name Manager</a></li>
                        <li><a href='/ORM'>return to Data Manager</a></li>
                        <li><a href='/'>return to dashboard</a></li>
                </ul>
 </p> ";
                }
                return(main_html_wrap($return_me));

        }else{
                return main_html_wrap("<h1>Cough... sputter... I can't find an ORM called $object_name</h1>");
        }

});

Route::get('/ORM/{object_name}',function($object_name){

        if(class_exists($object_name)){
                $return_me = "<h1> List of all $object_name </h1>";
                $return_me .= "<a href='/ORM/$object_name/new'>Make a new $object_name</a><br><br>";
                $return_me .= "<ul>\n";
                foreach($object_name::all() as $this_one_object){
                        if(!isset($name_field)){ //only runs on the first pass
                                $name_field = $this_one_object->getMyNameField();
                        }

                        $this_id = $this_one_object->id;
                        $this_name = $this_one_object->$name_field;

                        $return_me .= "<li><a href='/ORM/$object_name/$this_id'>$this_name ($this_id)</a></li>\n";
                }
                $return_me .= "</ul>\n";


        }else{
                $return_me = "<h1>Cough... sputter... I can't find an ORM called $object_name</h1>";
        }

        return(main_html_wrap($return_me));

});

Route::get('/ORM',function(){

        $class_list = BaseORM::listObjectTypes();

        $return_me = "<h1> Data </h1>";
        $return_me .= "<ul>\n";

        asort($class_list);

                foreach($class_list as $this_class){
                        $return_me .= "<li><a href='/ORM/$this_class/'>$this_class</a></li>\n";
                }
                $return_me .= "</ul>\n";

        return(main_html_wrap($return_me));

});



Route::get('/', function()
{
        return main_html_wrap(View::make('dashboard')); 
});

Route::get('/dashboard', function()
{
        return main_html_wrap(View::make('dashboard')); 
});






Route::get('/protected', function()
{
        return("You have to be logged in to see this");
});

Route::get('/unprotected', function()
{
        return("Everyone can see this");
});


Route::filter('before', function()
{
        // Do stuff before every request to your application...
            // Maintenance mode
    if(0) return Response::error( '503' );

    /*
        Secures parts of the application
        from public viewing.
    */
    $location = URI::segment(1) . '/' . URI::segment(2);
    if(Auth::guest() && !in_array( $location, Config::get('application.no_login_needed'))){

	//no authentication for now :(
        //return Redirect::to( '/login' );

    }

});




function main_html_wrap($stuff){
        $view_data = standard_view_data();
        $stuff = "<div class='container'>\n$stuff\n</div>";
        $view_data['view_contents'] = $stuff;

	$menu_contents = View::make('menu',$view_data);
	$view_data['menu_contents'] = $menu_contents;

        return View::make('html',$view_data);
}
//TODO pull intelligently from config...
function standard_view_data(){

        $base_url = URL::to('/');
	$menu = Config::get('app.menu');
	$site_name = Config::get('app.site_name');
	$copyright = Config::get('app.copyright');
        $view_data = array(
                'displayName' => 'UserName Here',
                'copyright' => $copyright,
                'title' => $site_name,
                'base_url' => $base_url,
                'menu' => $menu,
        );

	return($view_data);
}

/// These are tests to make sure Smarty is working
Route::get('/smartybasictest', function()
{
        return View::make('smartybasictest');
});

Route::get('/smartyfulltest', function()
{
	$data = standard_view_data(); //normally this is only available to the parent...
        return main_html_wrap(View::make('smartyfulltest',$data));
});





// Social Sentry oauth controller mappings...
// To use it, in app/routes.php
//Route::controller('oauth', 'Cartalyst\SentrySocial\Controllers\OAuthController');

// To extend it, make a class which extends Cartalyst\SentrySocial\Controllers\OAuthController
//Route::controller('oauth', 'MyOAuthController');

