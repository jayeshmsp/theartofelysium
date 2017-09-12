<?php namespace App\Repositories;

use App\User;
use App\Role;
use App\Helpers\EloquentHelper;
use DB;

class UserRepo
{
	public function getBy($params = array())
	{
            $query = DB::table('users')->leftjoin("role_user","users.id","=","role_user.user_id");

            $query->select(array(
                'users.*',"role_user.role_id"
            ))
			->where('users.platform','=','art-of-elysium');
            $EloquentHelper = new EloquentHelper();
            return $EloquentHelper->allInOne($query, $params);
        }

	public function find($id)
	{
		return User::find($id);
	}

	/**
	 * Create a new user instance after a valid registration.
	 *
	 * @param  array  $data
	 * @return User
	 */
	public function create(array $data)
	{
		$user = new User;
		$user->first_name  = $data['first_name'];
		$user->last_name  = $data['last_name'];
		$user->platform  = 'art-of-elysium';
		$user->name  = $data['first_name'].' '.$data['last_name'];
		$user->email  = $data['email'];
		$user->username = isset($data['username']) ? $data['username'] : '';
		if(isset($data['contact_id']) && !empty($data['contact_id'])) {
        	$user->status = 'Completed';
        	$user->contact_id = $data['contact_id'];
        } else {
        	$user->status = '';
        	$user->contact_id = "";
        }
        
                $user->verified  = DB::raw('0');
                $user->email_token = str_random(10);
		$user->save();

		foreach($data['role_id'] as $role_id){
           $user->roles()->attach($role_id);
		}

		return $user;
	}

	/**
	 * Create a new user instance after a valid registration.
	 *
	 * @param  array  $data
	 * @return User
	 */
	public function update(array $data,$id)
	{
		$user = User::find($id);

		$user->first_name  = $data['first_name'];
		$user->last_name  = $data['last_name'];
		$user->name  = $data['first_name'].' '.$data['last_name'];
		$user->email  = $data['email'];
		if(isset($data['contact_id']) && !empty($data['contact_id'])) {
        	$user->status = 'Completed';
        	$user->contact_id = $data['contact_id'];
        } else {
        	$user->status = '';
        	$user->contact_id = "";
        }
        $user->username = isset($data['username']) ? $data['username'] : '';
        $user->status = isset($data['status']) ? $data['status'] : '';
		if(!empty($data['password'])){
			$user->password  = bcrypt($data['password']);
		}
		$user->save();

		//delete all selected role first
		DB::table('role_user')->where('user_id',$id)->delete();

		//then add new roles to user
		foreach($data['role_id'] as $role_id){
           $user->roles()->attach($role_id);
		}

		return $user;
	}


	public function delete(int $id)
	{
		return User::destroy($id);
	}

	public function currentUserRole($user_id='')
	{
            $data = DB::table('role_user')->select('role_id')->where('user_id',$user_id)->get();
                $o = array();
		foreach ($data as $key => $value) {
			$o[$value->role_id]=$value->role_id;
		}
		return $o;
	}

	public function updateProfile(array $data,$id)
	{
		/*DATA MODIFICATION*/
		$data['interest'] = (!empty($data['interest']))?implode(',', $data['interest']):'';
		$data['skill'] = (!empty($data['skill']))?implode(',', $data['skill']):'';
		$data['is_profile_updated'] = DB::raw('"1"');
		$data['name'] = $data['first_name'].' '.$data['last_name'];
		if(!empty($data['password'])){ $data['password']  = bcrypt($data['password']); }else{ unset($data['password']); }
		
		return User::find($id)->update($data);
	}
}