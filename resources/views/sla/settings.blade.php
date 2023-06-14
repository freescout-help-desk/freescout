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
            @foreach ($settings2 as $item)
            @if($loop->last)
            <form action="/reports/settings" class="setting_form form-horizontal margin-top margin-bottom" id="settings_forms" method="POST">
              {{@csrf_field()}}
              <div class="setting_form_labe form-group">
                <label for="" class="col-sm-2 control-label">Send Auto Report</label>
               <div class="col-sm-6">
               <div class="controls">
                <div class="onoffswitch-wrap">
                  <div class="onoffswitch">
                    @if ($item->auto_data=="1")
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
                    <input class="setting_form_labe_input_email form-control input-sized" placeholder="" type="text" name="to_email" value="{{$item->to_email}}">
                   </div>
                </div>
                <div class="setting_form_labe form-group">
                    <label for="" class="col-sm-2 control-label">Frequency</label>
                    <div class="col-sm-6">
                      
                      <select class="setting_form_labe_input_frequency form-control input-sized"  name="frequency" id="myFrequency">
                        <option selected>Choose Frequency</option>
                        {{-- <option value="Monthly" {{$item->frequency == 'Monthy' ? 'selected': ''}}>Monthly</option>
                        <option value="Weekly" {{$item->frequency == 'Weekly' ? 'selected': ''}}>Weekly</option>
                        <option value="Daily" {{$item->frequency == 'Daily' ? 'selected': ''}}>Daily</option> --}}
                        <option value="Monthly" {{$item->frequency == 'Monthly' ? 'selected': ''}}>Monthly</option>
                        <option value="Weekly" {{$item->frequency == 'Weekly' ? 'selected': ''}}>Weekly</option>
                        <option value="Daily" {{$item->frequency == 'Daily' ? 'selected': ''}}>Daily</option>
                    </select>
                    </div>
                </div>
                <div class="setting_form_labe form-group" id="mySchedule1_a">
                    <label for="" class="col-sm-2 control-label">Schedule</label>
                   <div class="col-sm-6">
                    <select class="setting_form_labe_input_schedule form-control input-sized" name="schedule" id="mySchedule1" >
                      <option selected>Choose Schedule</option>
                      {{-- <option value="StartOfMonth" {{$item->schedule == 'StartOfMonth' ? 'selected': ''}}>StartOfMonth</option>
                      <option value="EndOfMonth" {{$item->schedule == 'EndOfMonth' ? 'selected': ''}}>EndOfMonth</option> --}}
                  </select>
                  </div>
                </div>
                <div class="setting_form_labe form-group" id="mySchedule2_a">
                  <label for="" class="col-sm-2 control-label">Schedule</label>
                 <div class="col-sm-6">
                  <select class="setting_form_labe_input_schedule form-control input-sized" name="schedule" id="mySchedule2" >
                    <option selected>Choose Schedule</option>
                    {{-- <option value="StartOfMonth" {{$item->schedule == 'StartOfMonth' ? 'selected': ''}}>StartOfMonth</option>
                    <option value="EndOfMonth" {{$item->schedule == 'EndOfMonth' ? 'selected': ''}}>EndOfMonth</option> --}}
                </select>
                </div>
              </div>
                <div class="setting_form_labe form-group">
                    <label for="" class="col-sm-2 control-label">Time</label>
                    <div class="col-sm-6">
                      <input class="setting_form_labe_input_time form-control input-sized" type="time" name="time"  value="{{$item->time}}">
                    </div>
                </div>
                <div class="col-sm-6 col-sm-offset-2">
                    <button class="setting_form_labe_button btn btn-primary" >Save</button>
                </div>
            </form>
            @endif
            @endforeach
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
<script >
  // $(document).ready(function(){
  
  //     $('#email_branding').on('click', function(){
  //         if($(this).is(':checked')) {
  //             // if your checkbox is checked, submit the form
  //             $('#settings_forms').submit();
  //         }
  //     });
  
  // });
  var stateList=[
    {Frequency:'Weekly',schedule:'Monday'},
    {Frequency:'Weekly',schedule:'Tuesday'},
    {Frequency:'Weekly',schedule:'Wednesday'},
    {Frequency:'Weekly',schedule:'Thursday'},
    {Frequency:'Weekly',schedule:'Friday'},
    {Frequency:'Weekly',schedule:'Saturday'},
    {Frequency:'Weekly',schedule:'Sunday'},
    {Frequency:'Daily',schedule:'Monday'},
   

    
    
  ];

   $(document).ready(function (){
    $("#myFrequency").change(function(){
    //   $("#mySchedule").html("<option selected>Choose Schedule</option>");
    //   $('#dpdlCity').html("<option selected>Choose City</option>");
    //   const states=stateList.filter(m=>m.Frequency==$("#myFrequency").val());
    //   states.forEach(element=>{
    //   const option="<option val='"+element.schedule+"'>"+element.schedule+"</option>";
    //   $("#mySchedule").append(option);
      
    // });
    if($(this).val()=='Monthly'){
      $("#mySchedule2").empty();
      for(var i=1;i<=31;i++){
        const option="<option val='"+i+"'>"+i+"</option>"
       
        $("#mySchedule2").append(option);
        $('#mySchedule2_a').show();
        $('#mySchedule1_a').hide();


      }
    }
    if($(this).val()=='Weekly'){
      $("#mySchedule1").empty();
        const states=stateList.filter(m=>m.Frequency==$("#myFrequency").val());
      states.forEach(element=>{
      const option="<option val='"+element.schedule+"'>"+element.schedule+"</option>";
      // $("#mySchedule").remove();
      
      $("#mySchedule1").append(option);
      $('#mySchedule1_a').show();
        $('#mySchedule2_a').hide();
    });
    // $("#mySchedule").show();
    }
    if($(this).val()=='Daily'){
      // $("#mySchedule").hide();
      $('#mySchedule1_a').hide();
    }
  });
  $('#mySchedule2_a').hide();
   });
  </script>
@endsection
