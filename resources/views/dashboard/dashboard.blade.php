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
    <h2 class="chart-heading">Popular Programming Languages</h2>
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
  labels: ["Python", "Java", "JavaScript", "C#", "Others"],
  data: [30, 17, 10, 7, 36],
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

<script>
.chart-heading {
  font-family: "Rubik", sans-serif;
  color: #023047;
  text-transform: uppercase;
  font-size: 24px;
  text-align: center;
}

.chart-container {
     
  width: 50px;
}

.programming-stats {
  font-family: "Rubik", sans-serif;
  display: flex;
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

</script>

@endsection