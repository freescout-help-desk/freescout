@extends('layouts.app')
@section('content')
<div class="layout-2col layout-2col-settings">
  <div class="sidebar-2col">
    <div class="sidebar-title">
      settings
    </div>
  </div>
  <div class="content-2col">
    <div class="section-heading">
      settings
    </div>
    <div class="container_settings row-container form-container top-margin">
      <div class="inner-container row">
          <div class="col-xs-12">
            <form action="/reports/settings" class="setting_form form-horizontal margin-top margin-bottom" method="POST">
              {{@csrf_field()}}
              <div class="setting_form_labe form-group">
                <label for="" class="col-sm-2 control-label">Send Auto Report</label>
               <div class="col-sm-6">
               <div class="controls">
                <div class="onoffswitch-wrap">
                  <div class="onoffswitch">
                    <input type="checkbox" name="settings[email_branding]" value="1" id="email_branding" class="onoffswitch-checkbox" checked="checked">
                    <label for="email_branding" class="onoffswitch-label"></label>
                  </div>
                </div>
               </div>
               </div>
            </div>
                <div class="setting_form_labe form-group">
                    <label for="" class="col-sm-2 control-label">To Email</label>
                   <div class="col-sm-6">
                    <input class="setting_form_labe_input_email form-control input-sized" placeholder="Enter Email" type="text" name="to_email">
                   </div>
                </div>
                <div class="setting_form_labe form-group">
                    <label for="" class="col-sm-2 control-label">Frequency</label>
                    <div class="col-sm-6">
                      <select class="setting_form_labe_input_frequency form-control input-sized" name="frequency" id="">
                        <option value="Monthly">Monthly</option>
                        <option value="Weakly">Weakly</option>
                        <option value="Daily">Daily</option>
                    </select>
                    </div>
                </div>
                <div class="setting_form_labe form-group">
                    <label for="" class="col-sm-2 control-label">Schedule</label>
                   <div class="col-sm-6">
                    <select class="setting_form_labe_input_schedule form-control input-sized" name="schedule" id="">
                      <option value="StartOfMonth">StartOfMonth</option>
                      <option value="EndOfMonth">EndOfMonth</option>
                  </select>
                   </div>
                </div>
                <div class="setting_form_labe form-group">
                    <label for="" class="col-sm-2 control-label">Time</label>
                    <div class="col-sm-6">
                      <input class="setting_form_labe_input_time form-control input-sized" type="time" name="time">
                    </div>
                </div>
                <div class="col-sm-6 col-sm-offset-2">
                    <button class="setting_form_labe_button btn btn-primary" >Save</button>
                </div>
            </form>
          </div>
      </div>
    </div>
  </div>
</div>
<style>
  .layout-2col-settings{
    margin-top: -19px;
  }
</style>
@endsection
