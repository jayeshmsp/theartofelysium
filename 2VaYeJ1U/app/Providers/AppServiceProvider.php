<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //Add this custom validation rule.
        Validator::extend('alpha_space', function ($attribute, $value) {
            return preg_match('/^[\pL\s]+$/u', $value); 
        });

        \Schema::defaultStringLength(191);
        
        //v validation for duplicate entry
        Validator::extend('is_user_exist', function($attribute, $value, $parameters, $validator) {
               $return  = false;
               $numOfField = count($parameters);
               $whereArr = array();
               if($numOfField %2 == 0 ) {
                   $numOfField--;
                   $id = end($parameters);
               }
               for($i = 1 ; $i < $numOfField; $i+=2) {
                    $whereArr[$parameters[$i]] = $parameters[$i+1];
               }
               if(empty($parameters[0] ) ) {
                   return $return;
               }
               $whereArr[$attribute] = $value;
               if(isset($id)) {
                   $records = DB::table($parameters[0])->where($whereArr)->where("id","<>",$id)->count();
               } else {
                    $records = DB::table($parameters[0])->where($whereArr)->count();
               }
               if($records > 0) {
                   return $return;
               }
               return true;
               //print "<pre>"; print_r($whereArr); print "</pre>";
               
               //echo  $value; exit;
        });
        
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
