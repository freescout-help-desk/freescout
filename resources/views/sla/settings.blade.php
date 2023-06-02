@extends('layouts.app')
@section('content')
<div class="top_item">
    <div class="top_title_first">
        <h2>Settings</h2>
        <label class="switch">
            <input type="checkbox">
            <span class="slider round"></span>
        </label>
    </div>
    
</div>
<div class="container">
    <div class="inner-container">
        <form action="" class="setting_form">
            <div class="setting_form_labe">
                <label for="">ToEmail</label>
                <input class="setting_form_labe_input_email" type="email">
            </div>
            <div class="setting_form_labe">
                <label for="">Frequency</label>
                <select class="setting_form_labe_input_frequency" name="" id="">
                    <option value="option1">Monthly</option>
                    <option value="option2">Weakly</option>
                    <option value="option3">Daily</option>
                </select>
            </div>
            <div class="setting_form_labe">
                <label for="">Schedule</label>
                <select class="setting_form_labe_input_schedule" name="" id="">
                    <option value="option1">StartOfMonth</option>
                    <option value="option2">EndOfMonth</option>
                </select>
            </div>
            <div class="setting_form_labe">
                <label for="">Time</label>
                <input class="setting_form_labe_input_time" type="time">
            </div>
            <div>
                <button class="setting_form_labe_button" type="button">Save</button>
            </div>
        </form>
    </div>
</div>
<style>

    .top_item{
        height: 20px;
    }
  .top_title_first{
    background-color: #b9d9d9;;
    padding: 2px 20px;
    margin-top: -8px;
    margin-left: -8px;
    display: flex;
    flex-direction: row;
    margin-right: -99px;
    height: 56px;
  }  
  .container{
    width: auto;
    height: auto;
    background-color: rgb(240, 240, 240);
    padding-bottom: 45px;
    margin-top: 40px;
    padding-top: 50px;
    margin-left: -8px;
    margin-right: -99px;
  

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

  .inner-container{
    text-align: left;
  }
  .setting_form_labe_button{
    background-color: rgb(35 159 158);
    width: 100px;
    border-radius: 4px;
    height: 30px;
    margin-top: 20px;
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
  .switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
  left: 210px;
  top: 14px;
 
}

/* Hide default HTML checkbox */
.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

/* The slider */
.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: .4s;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

input:checked + .slider {
  background-color: #2196F3;
}

input:focus + .slider {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
}

/* Rounded sliders */
.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}

@endsection


