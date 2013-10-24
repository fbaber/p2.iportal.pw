<?php
class users_controller extends base_controller {

    public function __construct() {
        parent::__construct();
        echo "users_controller construct called<br><br>";
    } 

    public function index() {
        echo "This is the index page";
    }

    public function signup() {
        echo "This is the signup page";
		
		# Setup view
            $this->template->content = View::instance('v_users_signup');
            $this->template->title   = "Sign Up";

        # Render template
            echo $this->template;
    }

	public function p_signup() {

        # Dump out the results of POST to see what the form submitted
        #echo '<pre>';
        #print_r($_POST);
        #echo '</pre>';   

		# More data we want stored with the user
		$_POST['created']  = Time::now();
		$_POST['modified'] = Time::now();

		# Encrypt the password  
		$_POST['password'] = sha1(PASSWORD_SALT.$_POST['password']);            

		# Create an encrypted token via their email address and a random string
		$_POST['token'] = sha1(TOKEN_SALT.$_POST['email'].Utils::generate_random_string()); 

		# Insert this user into the database 
		$user_id = DB::instance(DB_NAME)->insert("users", $_POST);

		# For now, just confirm they've signed up - 
		# You should eventually make a proper View for this
		echo 'You are signed up';
		
    }
	
    public function login($error = NULL) {
        echo "This is the login page:<br>";
		echo "<br>";
				
		# Setup view
        $this->template->content = View::instance('v_users_login');
        $this->template->title   = "Login";

		# Pass data to the view
		$this->template->content->error = $error;
		# Render template
		echo $this->template;
    }
	
	public function p_login() {

    # Sanitize the user entered data to prevent any funny-business (re: SQL Injection Attacks)
    $_POST = DB::instance(DB_NAME)->sanitize($_POST);

    # Hash submitted password so we can compare it against one in the db
    $_POST['password'] = sha1(PASSWORD_SALT.$_POST['password']);

    # Search the db for this email and password
    # Retrieve the token if it's available
    $q = "SELECT token 
        FROM users 
        WHERE email = '".$_POST['email']."' 
        AND password = '".$_POST['password']."'";

    $token = DB::instance(DB_NAME)->select_field($q);

    # If we didn't find a matching token in the database, it means login failed
    if(!$token) {

        # Send them back to the login page
		# 10.22.13 added the parameter error
        Router::redirect("/users/login/error");

    # But if we did, login succeeded! 
    } else {

        /* 
        Store this token in a cookie using setcookie()
        Important Note: *Nothing* else can echo to the page before setcookie is called
        Not even one single white space.
        param 1 = name of the cookie
        param 2 = the value of the cookie
        param 3 = when to expire
        param 4 = the path of the cooke (a single forward slash sets it for the entire domain)
        */
        setcookie("token", $token, strtotime('+2 weeks'), '/');

        # Send them to the main page - or whever you want them to go
        Router::redirect("/");

		}

	}
	
	
	
	
	
    public function logout() {
        echo "This is the logout page";
		
		# Generate and save a new token for next login
		$new_token = sha1(TOKEN_SALT.$this->user->email.Utils::generate_random_string());

		# Create the data array we'll use with the update method
		# In this case, we're only updating one field, so our array only has one entry
		$data = Array("token" => $new_token);

		# Do the update
		DB::instance(DB_NAME)->update("users", $data, "WHERE token = '".$this->user->token."'");

		# Delete their token cookie by setting it to a date in the past - effectively logging them out
		setcookie("token", "", strtotime('-1 year'), '/');

		# Send them back to the main index.
		Router::redirect("/");
    }

    public function profile() {
	
	#####  public function profile($user_name = NULL) {
	#####	Added code 10/23
		
		# If user is blank, they're not logged in; redirect them to the login page
    if(!$this->user) {
        Router::redirect('/users/login');
    }

    # If they weren't redirected away, continue:

    # Setup view
    $this->template->content = View::instance('v_users_profile');
    $this->template->title   = "Profile of".$this->user->first_name;

    # Render template
    echo $this->template;
	
	####
		/*
		If you look at _v_template you'll see it prints a $content variable in the <body>
		Knowing that, let's pass our v_users_profile.php view fragment to $content so 
		it's printed in the <body>
		*/
	//	$this->template->content = View::instance('v_users_profile');

		#$title is another variable used in _v_template to set the <title> of the page
	//	$this->template->title = "Profile";
		
		# ****************************** #
		
		# Create an array of 1 or many client files to be included in the head
		$client_files_head = Array(
			'/css/widgets.css',
			'/css/profile.css'
			);

		# Use load_client_files to generate the links from the above array
		$this->template->client_files_head = Utils::load_client_files($client_files_head);  

		# Create an array of 1 or many client files to be included before the closing </body> tag
		$client_files_body = Array(
			'/js/widgets.min.js',
			'/js/profile.min.js'
			);

		# Use load_client_files to generate the links from the above array
		$this->template->client_files_body = Utils::load_client_files($client_files_body);  

		# ****************************** #
		
		# Pass information to the view fragment
		//$this->template->content->user_name = $user_name;

		# Render View
		//echo $this->template;
		
		
		
		# Create a new View instance
		# Do *not* include .php with the view name
		#$view = View::instance('v_users_profile');

		# Pass information to the view instance
		#$view->user_name = $user_name;

		# Render View
		#echo $view;
	
		
		/*
        if($user_name == NULL) {
            echo "No user specified";
        }
        else {
            echo "This is the profile for ".$user_name;
        } */
    }

} # end of the class