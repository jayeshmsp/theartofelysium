@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="white-box">
                    {{-- <div class="pull-left">
                    @include('users.user.partials.search')
                </div> --}}
                @permission('user-add')
                    <div>
                        <h3 class="box-title">
                            <a class="btn btn-sm btn-primary" href="user/create">
                                Add User
                            </a>
                        </h3>
                    </div>
                @endpermission
                <div class="table-responsive">
                    <table id="example1" class="table">
                        @if($items->count())
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fisrt Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Contact ID</th>
                                <th>Application Status</th>
                                <th>User Type</th>
                                <th width="150">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $value)
                            @if($logged_id == $value->id) 
                                @continue;
                            @endif
                            <tr>
                                <td>{{$value->id }}</td>
                                <td>{{$value->first_name}}</td>
                                <td>{{$value->last_name}}</td>
                                <td>{{$value->email}}</td>
                                <td>{{$value->username}}</td>
                                <td>{{$value->contact_id}}</td>
                                 <td>{{$value->status or ''}}</td>
                                <td>{{isset($roles[$value->role_id])?$roles[$value->role_id]:''}}</td>
                                <td>
                                @permission('user-edit')
                                    {!! Form::open(array('url' => 'user/'.$value->id,'method'=>'delete','class'=>'form-inline')) !!}
                                         <a href="{{url('user/'.$value->id.'/edit')}}" class="btn btn-small btn-primary"><span class="glyphicon glyphicon-pencil"></span></a>
                                         <a data-userId="{{$value->id}}" href="javascript:void(0)" class="btn btn-small btn-primary reset-pass-modal"><span class="glyphicon glyphicon-lock"></span></a>
                                         <a href="{{url('user/destroy/'.$value->id)}}" class="btn btn-small btn-danger"><span class="glyphicon glyphicon-trash"></span></a>  
                                    {!! Form::close() !!}
                                @endpermission
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        @else
                        <tbody>
                            <tr>
                                <th>There are no records</th>
                            </tr>
                        </tbody>
                        @endif
                    </table>
                </div>
                {!! str_replace('/?', '?', $items->appends(Request::except(array('page')))->render()) !!}
            </div>
        </div>
    </div>
</div>
<div id="resetPassModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Change password</h4>
      </div>
         
      <div class="modal-body">
        <form name="reset_pass_form" id="reset_pass_form">
            <div class="form-group">
              <input type="hidden" id="resetPassID" name="user_id" value="">  
              <label for="pwd">Enter new password:</label>
              <input type="password" name="password" class="form-control" id="pwd">
            </div>
            <div class="form-group">
              <label for="pwd">Confirm password:</label>
              <input type="password" name="password_confirmation" class="form-control" id="confirm_pwd">
            </div>
            <div class="form-group">
                <span class="pass-error error text-danger" style="display: none" ></span>
                <span class="pass-success success text-success" style="display: none" ></span>
            </div>
        </form>
        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="savePass" >Save</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>
<script src="{{asset('public/plugins/bower_components/jquery/dist/jquery.min.js')}}"></script>
<script type="text/javascript">
    $(document).ready(function(){
        var url = "{{url('/change-password')}}";
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $(".reset-pass-modal").click(function(){
           $("#resetPassID").val($(this).attr('data-userId'));
           $("#resetPassModal").modal("show");
        });
        
        $("#savePass").click(function(){
            var formData = $("#reset_pass_form").serialize();
            $.post(url, formData, function(result){
                $(".pass-success").show();
                $(".pass-success").text(result.msg);
                setTimeout(function(){
                    $(".pass-success").hide();
                    $("#resetPassModal").modal('hide');
                    $('#reset_pass_form')[0].reset();
                },2000);
            }).fail(function(result) {
                $(".pass-error").show();
                var result = $.parseJSON(result.responseText);
                console.log(result);
                $(".pass-error").text(result.msg);
                setTimeout(function(){
                    $(".pass-error").hide();
                },3000);
            });
        })
    });
</script>

@endsection