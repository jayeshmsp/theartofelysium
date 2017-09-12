<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use View;
use Auth;
use App\Repositories\SettingRepo;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $setting_details;
    public function __construct(Guard $auth)
    {
        parent::__construct($auth);
        $SettingRepo = new SettingRepo;
        $this->setting_details = $SettingRepo->getBy(array('single'=>true));
        $this->middleware('auth');
        View::share('module_name','Dashboard');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        if ($user->status=='completed') {
            $salesforce_dashboard_url = str_replace('[CONTACT_ID]', $user->contact_id, $this->setting_details->salesforce_dashboard_url);
            $salesforce_dashboard_url = str_replace('[UID]', $user->id, $salesforce_dashboard_url);
            $salesforce_dashboard_url = str_replace('[FNAME]', $user->first_name, $salesforce_dashboard_url);
            $salesforce_dashboard_url = str_replace('[LNAME]', $user->last_name, $salesforce_dashboard_url);
            $salesforce_dashboard_url = str_replace('[EMAIL]', $user->email, $salesforce_dashboard_url);
            return redirect($salesforce_dashboard_url);   
            //return redirect('http://sandbox1-theartofelysium.cs14.force.com/CalendarDashboard?profile=false&id='.$user->contact_id);   
        }
        $salesforce_application_page_url = str_replace('[CONTACT_ID]', $user->contact_id, $this->setting_details->salesforce_application_page_url);
        $salesforce_application_page_url = str_replace('[UID]', $user->id, $salesforce_application_page_url);
        $salesforce_application_page_url = str_replace('[FNAME]', $user->first_name, $salesforce_application_page_url);
        $salesforce_application_page_url = str_replace('[LNAME]', $user->last_name, $salesforce_application_page_url);
        $salesforce_application_page_url = str_replace('[EMAIL]', $user->email, $salesforce_application_page_url);
        return redirect($salesforce_application_page_url);
        //return redirect('http://sandbox1-theartofelysium.cs14.force.com/VolunteerApplicationVFpage3?profile=false&id='.$user->contact_id);
    }
}
