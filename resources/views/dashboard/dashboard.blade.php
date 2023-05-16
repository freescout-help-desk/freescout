@extends('layouts.app')
@section('content')
<div class="container">
  <div class="row vh-100">
    <div class="col-md-9"> <!-- 80% width for larger screens -->
        <div class="row">
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Total Tickets</p>
                        <h2 class="card-title text-danger" style="font-size: 80px;">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Unassigned Tickets</p>
                        <h2 class="card-title" style="font-size: 80px;">15</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Overdue Tickets</p>
                        <h2 class="card-title text-danger" style="font-size: 80px;">0</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Open Tickets</p>
                        <h2 class="card-title" style="font-size: 80px;">40</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Close Tickets</p>
                        <h2 class="card-title" style="font-size: 80px;">33</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-150 w-300" style="height: 150px; width: 300px;">
                    <div class="card-body text-center">
                        <p class="card-text">Hold Tickets</p>
                        <h2 class="card-title" style="font-size: 80px;">40</h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;700&display=swap"
      rel="stylesheet"
    />
  </head>
  <body>
    <h2 class="chart-heading">Total on of Tickets</h2>
    <div class="programming-stats">
      <div class="chart-container">
        <canvas class="my-chart"></canvas>
      </div>

      <div class="details">
        <ul></ul>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   
  </body>
</html>
<script>

const chartData = {
  labels: ["Open Tickets", "Close Tickets", "Hold Tickets", "Overdue Tickets"],
  data: [30, 17, 10, 7],
};

const myChart = document.querySelector(".my-chart");
const ul = document.querySelector(".programming-stats .details ul");

new Chart(myChart, {
  type: "doughnut",
  data: {
    labels: chartData.labels,
    datasets: [
      {
        label: "Language Popularity",
        data: chartData.data,
      },
    ],
  },
  options: {
    borderWidth: 10,
    borderRadius: 2,
    hoverBorderWidth: 0,
    plugins: {
      legend: {
        display: false,
      },
    },
  },
});

const populateUl = () => {
  chartData.labels.forEach((l, i) => {
    let li = document.createElement("li");
    li.innerHTML = `${l}: <span class='percentage'>${chartData.data[i]}%</span>`;
    ul.appendChild(li);
  });
};

populateUl();
</script>

<style>
.chart-heading {
  font-family: "Rubik", sans-serif;
  color: #023047;
  text-transform: uppercase;
  font-size: 24px;
  text-align: left;
  
}

.chart-container {
  width: 50px;
  display: block;
    box-sizing: border-box;
    height: 231px;
    width: 231px;   
}

.programming-stats {
  font-family: "Rubik", sans-serif;
  display: -webkit-inline-box;
  align-items: center;
  gap: 24px;
  margin: 0 auto;
  width: fit-content;
  box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.3);
  border-radius: 20px;
  padding: 8px 32px;
  color: #023047;
  transition: all 400ms ease;
}

.programming-stats:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 16px -7px rgba(0, 0, 0, 0.3);
}

.programming-stats .details ul {
  list-style: none;
  padding: 0;
}

.programming-stats .details ul li {
  font-size: 16px;
  margin: 12px 0;
  text-transform: uppercase;
}

.programming-stats .details .percentage {
  font-weight: 700;
  color: #e63946;
}

</style>



<!---charts start-->
<html>
<body>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" charset="utf-8"></script>
<div class="chart">
    <ul class="numbers">
        <li><span>8%</span></li>
        <li><span>6%</span></li>
        <li><span>4%</span></li>
        <li><span>2%</span></li>
        <li><span>0%</span</li>
</ul>
<ul class="bars">
    <h2 class="header">Weekly Tickets</h2>
<li><div class="bar" data-percentage="30"></div><span>S</span></li>
<li><div class="bar" data-percentage="50"></div><span>M</span></li>
<li><div class="bar" data-percentage="40"></div><span>T</span></li>
<li><div class="bar" data-percentage="50"></div><span>W</span></li>
<li><div class="bar" data-percentage="80"></div><span>T</span></li>
<li><div class="bar" data-percentage="20"></div><span>F</span></li>
<li><div class="bar" data-percentage="30"></div><span>S</span></li>
<h2 class="head">Average resolved tickets</h2>
</ul>
</div>

<script type="text/javascript">
$(function(){
    $('.bars li .bar').each(function(key, bar){
        var percentage = $(this).data('percentage');
        $(this).animate({
            'height' : percentage + "%"
        },1000);
        });
    });


</script>
</body>
</html>
<style>
bars{
    margin : 0;
    padding: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-item: center;
    font-family: "Roboto", sans-serif;
    background: #333;
}
.header{
    color: #fff;
    width:34%;
    position: absolute;
    bottom:383px;
    text-align:center;  
}
.head{
    color: #fff;
    width:125%;
    position: absolute;
    bottom:4px;
    text-align:center;
   
}

.charts{
    width: 600px;
    height: 300px;
    display: block;

}

.numbers{
    color: #fff;
    margin: 0;
    padding: 0;
    width: 50px;
    height: 31px;
    display: inline-block;
    float: left;
}
.numbers li{
    list-style: none;
    height: 110px;
    position: relative;
    bottom: 145px;
}

.numbers span{
    font-size: 12px;
    font-weight: 600;
    position: absolute;
    bottom: 0;
    right: 10px;
}

.bars{
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    background: #555;
    margin: 0;
    padding: 0;
    display: inline-block;
    width: 1797px;
    height: 431px;
    box-shadow: 0 0 10px 0 #555;
    border-radius: 19px;
}
 .bars li{
    display: table-cell;
    width: 272px;
    height: 361px;
    position: relative;
 }

 .bars span{
    width: 117%;
    position: absolute;
    bottom: -25px;
    text-align: center;
 }

 .bars .bar{
    display: block;
    background: #17C0EB;
    width: 80px;
    
    position: absolute;
    bottom: 0;
    margin-left: 113px;
    text-align: center;
    box-shadow: 0 0 10px 0 rgba(23, 192, 235, 0.5);
    transition: 0.5s;
    transition-property: background, box-shadow;
 }

 .bars .bar:hover{
    background: #55EFC4;
    box-shadow: 0 0 10px 0 rgba(85, 239, 196, 0.5);
    cursor: pointer;
 }

 .bars .bar:before{
    color: #fff;
    content: attr(data-percentage);
    position: relative;
    bottom: 20px;
 }












    </style>


@endsection