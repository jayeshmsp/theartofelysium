<?php

namespace App\Http\Controllers\Auth;

use Mail;
use DB;
use App\User;
use Illuminate\Http\Request;
use App\Mail\EmailVerification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Auth;
use Form;
use View;
use App\Repositories\SettingRepo;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $setting_details;
    public function __construct()
    {
        $SettingRepo = new SettingRepo;
        $this->setting_details = $SettingRepo->getBy(array('single'=>true));
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $rules = [
            'first_name' => 'required|string|alpha_space|max:255',
            'last_name' => 'required|string|alpha_space|max:255',
            'email' => 'required|string|email|max:255|is_user_exist:users,name,'.$data['first_name']." ".$data['last_name'],
            //'email' => 'required|string|email|max:255',
            //'password' => 'required|string|min:6|confirmed',
            'g-recaptcha-response' => 'required|captcha',
        ];
        
        $msg = [
            'email.unique'=> "This contact is already registered."
        ];
        return Validator::make($data, $rules,$msg);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        $user =  User::create([
            'name' => $data['first_name'].' '.$data['last_name'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => isset($data['email'])?$data['email']:'',
            'username' => '',
            'platform' => 'art-of-elysium',
            //'password' => bcrypt($data['password']),
            'verified' => '0',
            'email_token' => str_random(10),
        ]);
        $user->attachRole('2');

        return $user;
    }

    /**
    *  Over-ridden the register method from the "RegistersUsers" trait
    *  Remember to take care while upgrading laravel
    */
    public function register(Request $request)
    {
        // Laravel validation
        $validator = $this->validator($request->all());
        if ($validator->fails()) 
        {
            $this->throwValidationException($request, $validator);
        }
        $user_exists= User::where('first_name','=',$request->get('first_name'))
                            ->where('last_name','=',$request->get('last_name'))
                            ->where('email','=',$request->get('email'))
                            ->first();
        if (!empty($user_exists) && $user_exists->status=='completed' ) {
            $salesforce_dashboard_url = str_replace('[CONTACT_ID]', $user_exists->contact_id, $this->setting_details->salesforce_dashboard_url);
             $salesforce_dashboard_url = str_replace('[UID]', $user_exists->id, $salesforce_dashboard_url);
            $salesforce_dashboard_url = str_replace('[FNAME]', $user_exists->first_name, $salesforce_dashboard_url);
            $salesforce_dashboard_url = str_replace('[LNAME]', $user_exists->last_name, $salesforce_dashboard_url);
            $salesforce_dashboard_url = str_replace('[EMAIL]', $user_exists->email, $salesforce_dashboard_url);
            
            return redirect($salesforce_dashboard_url);   
        }elseif (!empty($user_exists)) {
            $salesforce_application_page_url = str_replace('[CONTACT_ID]', $user_exists->contact_id, $this->setting_details->salesforce_application_page_url);
            $salesforce_application_page_url = str_replace('[FNAME]', $user_exists->first_name, $salesforce_application_page_url);
            $salesforce_application_page_url = str_replace('[LNAME]', $user_exists->last_name, $salesforce_application_page_url);
            $salesforce_application_page_url = str_replace('[EMAIL]', $user_exists->email, $salesforce_application_page_url);
            $salesforce_application_page_url = str_replace('[UID]', $user_exists->id, $salesforce_application_page_url);

            return redirect($salesforce_application_page_url);
            //return redirect('http://sandbox1-theartofelysium.cs14.force.com/VolunteerApplicationVFpage3?profile=false&id='.$user_exists->contact_id);
        }else{
            $user = $this->create($request->all());
            $email = new EmailVerification(new User(['email_token' => $user->email_token, 'name' => $user->name]));
            Mail::to($user->email)->send($email);
            $msg = 'Registration Successful.   Please check your email for verification instructions.';
            
            return back()->with('success',$msg);
        }
        
    }

    // Get the user who has the same token and change his/her status to verified i.e. 1
    public function verify($token)
    {
        $user = User::where('email_token',$token)->first();
        //print "<pre>"; print_r($user); print "</pre>";exit;
        if(!empty($user)){
            if(!empty($user['name'])) {
                $userName = explode(" ", $user['name']);
                $user['f_name'] = $userName[0];
                if(isset($userName[1])) {
                    $user['l_name'] = $userName[1];
                }
            }
            return view('auth.verify')
                    ->with('user',$user);
        }
        return redirect('login')->with('error','You Are Already verified or token not found in our records.');
    }

    public function verifyStore(Request $request,$id='')
    {
        $data = $request->all();
        $rules = [
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) 
        {
            $this->throwValidationException($request, $validator);
        }
        
        User::where('id','=',$id)->update(['username' => $data['username'],'password' => bcrypt($data['password'])]);
        User::where('id',$id)->firstOrFail()->verified();
        $user = User::where('id',$id)->first();
        Auth::loginUsingId($id);
        $salesforce_application_page_url = str_replace('[CONTACT_ID]', '', $this->setting_details->salesforce_application_page_url);

        $salesforce_application_page_url = str_replace('[FNAME]', $user->first_name, $salesforce_application_page_url);
        $salesforce_application_page_url = str_replace('[LNAME]', $user->last_name, $salesforce_application_page_url);
        $salesforce_application_page_url = str_replace('[EMAIL]', $user->email, $salesforce_application_page_url);
        $salesforce_application_page_url = str_replace('[UID]',$id, $salesforce_application_page_url);

        return redirect($salesforce_application_page_url);
        //return redirect('http://sandbox1-theartofelysium.cs14.force.com/VolunteerApplicationVFpage3?profile=false&id=');
    }
    
    /**
     * this function send email verification mail while user change there email on verification
     * page 
     * @return true on success 
     */
    /*public function verificationMail($requestData,$token)
    {
        $response = array('status'=>0);
        $userUpdateData = array();
        if(empty($token)) {
            $response['msg'] = "You Are Already verified or token not found in our records.";
            return $response;
        }
        $user = User::where('email_token',$token)->first();
        if(empty($user)) {
            $response['msg'] = "You Are Already verified or token not found in our records.";
            return $response;
        }
        if(strcasecmp($requestData['email'],$user['email'])  == 0 ) {
            $response['status'] = 1;
            return $response;
        }
        
        $rules = [
            'email' => 'required|string|email|max:255|unique:users',
        ];
        $validator = Validator::make($requestData, $rules,$msg);
        if($validator->fails()) {
            $this->throwValidationException($requestData, $validator);
            return $response;
        }
        $userUpdateData['email_token'] = str_random(10);
        $userUpdateData['email'] = $requestData['email'];
        $userObj = new User();
        User::where('id','=', $user['id'])->update(['username' => $data['username'],'password' => bcrypt($data['password'])]);
        
    }*/
    
}
