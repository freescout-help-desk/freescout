@extends('layouts.app')
@section('content')
<div class="layout-2col layout-2col-settings">
  <div class="sidebar-2col">
    <div class="sidebar-title">
      Settings
    </div>
  </div>
  <div class="content-2col">
    <div class="section-heading">
      Settings
    </div>
    <div class="container_settings row-container form-container top-margin">
      <div class="inner-container row">
          <div class="col-xs-12">
            <form action="/reports/settings" class="setting_form form-horizontal margin-top margin-bottom" id="settings_forms" method="POST">
              {{@csrf_field()}}
              <div class="setting_form_labe form-group">
                <label for="" class="col-sm-2 control-label">Send Auto Report</label>
               <div class="col-sm-6">
               <div class="controls">
                <div class="onoffswitch-wrap">
                  <div class="onoffswitch">
                    @if ($settings && $settings->auto_data=="1")
                    <input type="checkbox" name="auto_data" value="1" id="email_branding" class="onoffswitch-checkbox" checked>
                    @else
                    <input type="checkbox" name="auto_data" value="1" id="email_branding" class="onoffswitch-checkbox">
                    @endif
                    <label for="email_branding" class="onoffswitch-label"></label>
                  </div>
                </div>
               </div>
               </div>
            </div>
                <div class="setting_form_labe form-group">
                    <label for="" class="col-sm-2 control-label">To Email</label>
                   <div class="col-sm-6">
                    <input class="setting_form_labe_input_email form-control input-sized" placeholder="" type="text" name="to_email" value="{{$settings && $settings->to_email ? $settings->to_email : ''}}">
                   </div>
                </div>
                <div class="setting_form_labe form-group">
                    <label for="" class="col-sm-2 control-label">Frequency</label>
                    <div class="col-sm-6">
                      <select class="setting_form_labe_input_frequency form-control input-sized"  name="frequency" id="myFrequency">
                        <option selected>Choose Frequency</option>
                        <option value="Monthly" {{$settings && $settings->frequency == 'Monthly' ? 'selected': ''}}>Monthly</option>
                        <option value="Weekly" {{$settings && $settings->frequency == 'Weekly' ? 'selected': ''}}>Weekly</option>
                        <option value="Daily" {{$settings && $settings->frequency == 'Daily' ? 'selected': ''}}>Daily</option>
                    </select>
                    </div>
                </div>
               @if($settings->frequency!='null')
               <div class="setting_form_labe form-group" id="mySchedule1_a">
                <label for="" class="col-sm-2 control-label">Schedule</label>
               <div class="col-sm-6">
                <select class="setting_form_labe_input_schedule form-control input-sized" name="schedule" id="mySchedule1" >
                  <option selected>Choose Schedule</option>
              </select>
              </div>
            </div>
            <div class="setting_form_labe form-group" id="mySchedule2_a">
              <label for="" class="col-sm-2 control-label">Schedule</label>
             <div class="col-sm-6">
              <select class="setting_form_labe_input_schedule form-control input-sized" name="schedule" id="mySchedule2" >
                <option selected>Choose Schedule</option>
            </select>
            </div>
          </div>
               @endif
                <div class="setting_form_labe form-group">
                    <label for="" class="col-sm-2 control-label">Time</label>
                    <div class="col-sm-6">
                      <input class="setting_form_labe_input_time form-control input-sized" type="time" name="time"  value="{{$settings && $settings->time ? $settings->time : ''}}">
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
<input type="hidden" id="selectedSchedule" value={{$settings && $settings->schedule ? $settings->schedule : 'demo'}} />
<style>
  .layout-2col-settings{
    margin-top: -19px;
  }  
</style>
<script >
  var stateList=[
    {Frequency:'Weekly',schedule:'Monday'},
    {Frequency:'Weekly',schedule:'Tuesday'},
    {Frequency:'Weekly',schedule:'Wednesday'},
    {Frequency:'Weekly',schedule:'Thursday'},
    {Frequency:'Weekly',schedule:'Friday'},
    {Frequency:'Weekly',schedule:'Saturday'},
    {Frequency:'Weekly',schedule:'Sunday'},  
  ];

   $(document).ready(function (){
      $('#mySchedule1_a').hide();
      $('#mySchedule2_a').hide();
    $("#myFrequency").change(function(){
      toggleSchedule($(this).val());
    });
    if($('#myFrequency').val()=="Monthly"){
      $('#mySchedule2_a').hide();
      toggleSchedule('Monthly');
    }else{
      $('#mySchedule1_a').hide();
      toggleSchedule('Weekly');
    }
  });

  function toggleSchedule(selectedFrequency) {
    console.log(selectedFrequency);
    if(selectedFrequency =="Monthly"){
      $("#mySchedule1").empty();
      $("#mySchedule2").empty();
      for(var i=1;i<=31;i++){
        console.log($('#selectedSchedule').val());
        const option="<option value='"+i+"'>"+i+"</option>"
        $("#mySchedule1").append(option);
        $('#mySchedule1_a').show();
        $('#mySchedule2_a').hide();
        $('#mySchedule1 option[value='+$('#selectedSchedule').val()+']').attr('selected', 'selected');
      }
    }
    if(selectedFrequency =="Weekly"){
      $("#mySchedule1").empty();
      $("#mySchedule2").empty();
        const states=stateList.filter(m=>m.Frequency==$("#myFrequency").val());
      states.forEach(element=>{
        console.log($('#selectedSchedule').val());
      const option="<option value='"+element.schedule+"'>"+element.schedule+"</option>";
      $("#mySchedule2").append(option);
        $('#mySchedule2_a').show();
        $('#mySchedule1_a').hide();
      $('#mySchedule2 option[value='+$('#selectedSchedule').val()+']').attr('selected', 'selected');
    });
    }
    if(selectedFrequency =='Daily'){
      $('#mySchedule1_a').hide();
      $('#mySchedule2_a').hide();

    }
  }

  </script>
@endsection