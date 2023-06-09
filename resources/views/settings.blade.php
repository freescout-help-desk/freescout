@extends('layouts.app')
@section('content')
<div class="top_item">
  <div class="top_title_first">
      <h2 class="top_title_first_text">Settings</h2>
  </div>
  <label for="" id="dm-switch" data-toggle="tolltip" data-placement="right" title data-original-title="Dark Mode">
    <input name="darkmode" type="checkbox">
    <span class="dm-switch-inner">
    </span>
  </label>
</div>
<div class="container_settings">
  <div class="inner-container">
      <form action="/reports/settings" class="setting_form" method="POST">
        {{@csrf_field()}}
          <div class="setting_form_labe">
              <label for="">ToEmail</label>
              <input style="background-color: white;" class="setting_form_labe_input_email" type="text" name="to_email">
          </div>
          <div class="setting_form_labe">
              <label for="">Frequency</label>
              <select class="setting_form_labe_input_frequency" name="frequency" id="">
                  <option value="option1">Monthly</option>
                  <option value="option2">Weakly</option>
                  <option value="option3">Daily</option>
              </select>
          </div>
          <div class="setting_form_labe">
              <label for="">Schedule</label>
              <select class="setting_form_labe_input_schedule" name="schedule" id="">
                  <option value="option1">StartOfMonth</option>
                  <option value="option2">EndOfMonth</option>
              </select>
          </div>
          <div class="setting_form_labe">
              <label for="">Time</label>
              <input class="setting_form_labe_input_time" type="time" name="time">
          </div>
          <div>
              <button class="setting_form_labe_button" >Save</button>
          </div>
      </form>
  </div>
</div>
<style>
 /* .top_title_first{
  text-align: center;
 } */
 .top_title_first{
    background-color: #b9d9d9;
    /* display: flex;
    flex-direction: row; */
    /* margin-right: 0px; */
    height: 40px;
    margin-top: -20px;
    width: 100%;
    text-align: center;
  }  
  .container_settings{
    width: auto;
    height: auto;
    /* background-color: rgb(240, 240, 240); */
    padding-bottom: 45px;
    margin-top: 0px;
    padding-top: 50px;
    margin-left: -60px;
    margin-right: -34px;
  

  }
  #setting_title{
    text-align: center;
    height: 20px;
    padding-top: 20px
  }

  .setting_form{
    /* text-align: center */
   
  }

  .setting_form_labe{
    padding:10px 0px;
  }
  .setting_form_labe_input_email ,.setting_form_labe_input_frequency ,.setting_form_labe_input_schedule ,.setting_form_labe_input_time{
    width: 40%;
    padding: 7px 0px;

    
  }
  .setting_form_labe_input_email {
    margin-left: 25px;
  }
  .setting_form_labe_input_frequency {
    margin-left: 12px;
  }
  .setting_form_labe_input_schedule{
    margin-left: 20px;
  }
  .setting_form_labe_input_time{
    margin-left: 44px;
  }
  .inner-container{
    text-align: center;
  }

  /* .inner-container{
    text-align: left;
  } */
  .setting_form_labe_button{
    background-color: rgb(35 159 158);
    width: 100px;
    border-radius: 4px;
    height: 30px;
    margin-top: 20px;
  }
  .top_title_first_text{
    /* text-align: center; */
    /* margin-top: 2px; */
    text-align: left;
  }

  .dm .container_settings{
    color: #394956;
  }
  .dm .top_title_first{
    background-color:#005eb4;
  }

  .dm .setting_form_labe_input_email{
     background-color: white;
  }
  .dm .setting_form_labe_input_email ,.setting_form_labe_input_frequency ,.setting_form_labe_input_schedule ,.setting_form_labe_input_time{
    /* background: #2c396c;
    color: #b3b3b3;
    border: 1px solid #232d53; */
}
  
</style>
@endsection
