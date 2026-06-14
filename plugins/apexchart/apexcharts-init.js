// Chart 1 
var options = {
    series: [{
    name: 'Employee',
    data: [10, 90, 45, 65, 55, 70, 40, 120]
  }],
  chart: {
      height: 70,
      // type: 'line',
    
      toolbar: {
        show: false
      },
  },
  stroke: {
    width: 5,
    curve: 'smooth'
  },
  grid: {
    show: false,
  },
  xaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  fill: {
    type: 'gradient',
    gradient: {
      shade: 'dark',
      gradientToColors: [ '#924AEF'],
      shadeIntensity: 1,
      type: 'horizontal',
      opacityFrom: 1,
      opacityTo: 1,
      stops: [0, 100, 100, 100]
    },
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_one = new ApexCharts(document.querySelector("#chart-1"), options);
chart_one.render();


// Chart 2
var options = {
  series: [{
    name: 'Employee',
    data: [100, 40, 65, 45, 55, 40, 70, 10]
  }],
    chart: {
      height: 70,
      // type: 'line',
    
      toolbar: {
        show: false
      },
  },
  stroke: {
    width: 5,
    curve: 'smooth'
  },
  grid: {
    show: false,
  },
  xaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  fill: {
    type: 'gradient',
    gradient: {
      shade: 'dark',
      gradientToColors: [ '#5ECFFF'],
      shadeIntensity: 1,
      type: 'horizontal',
      opacityFrom: 1,
      opacityTo: 1,
      stops: [0, 100, 100, 100]
    },
  },

  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_two = new ApexCharts(document.querySelector("#chart-2"), options);
chart_two.render();


// Chart 3
var options = {
  series: [{
    name: 'Employee',
    data: [10, 90, 45, 65, 55, 70, 40, 120]
  }],
    chart: {
      height: 70,
      // type: 'line',
    
      toolbar: {
        show: false
      },
  },
  stroke: {
    width: 5,
    curve: 'smooth'
  },
  grid: {
    show: false,
  },
  xaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  fill: {
    type: 'gradient',
    gradient: {
      shade: 'dark',
      gradientFromColors: [ '#E328AF'],
      gradientToColors: [ '#E328AF'],
      shadeIntensity: 1,
      type: 'horizontal',
      opacityFrom: 1,
      opacityTo: 1,
      stops: [0, 100, 100, 100]
    },
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_three = new ApexCharts(document.querySelector("#chart-3"), options);
chart_three.render();


// Chart 4
var options = {
  series: [{
    name: 'This Week',
    data: [44, 35, 51, 37, 22, 33, 21]
  }, {
    name: 'Last Week',
    data: []
  }],
  chart: {
    type: 'bar',
    height: 350,
    toolbar: {
      show: true,
      tools: {
        download: '<i class="bi bi-three-dots-vertical fs-24 text-gray"></i>',
      }
    },
  },
  plotOptions: {
    bar: {
      distributed: true,
      horizontal: false,
      columnWidth: '50%',
      borderRadius: 6,
    },
  },

  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  colors: ['#E328AF', '#924AEF',],
  // title: {
  //   text: 'Recruitment Progress',
  //   align: 'left'
  // },
  stroke: {
    show: true,
    width: 5,
    colors: ['transparent']
  },
  xaxis: {
    categories: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
  },
  fill: {
    opacity: 1
  },


  legend: {
    show: true,
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '16px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    labels: {
      colors: '#A5A5A5',
      useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_four = new ApexCharts(document.querySelector("#chart-4"), options);
chart_four.render();


// Chart 5
var options = {
  series: [
    {
      name: "Approved",
      data: [0, 62, 57, 78, 64, 70, 38, 77, 38, 61, 50]
    },
    {
      name: "Pending",
      data: [50, 12, 22, 19, 49, 19, 18, 10, 82, 41, 60]
    }
  ],
  chart: {
    height: 350,
    type: 'line',
    zoom: {
      enabled: false
    },
    toolbar: {
      show: true,
      tools: {
        download: '<i class="bi bi-three-dots-vertical fs-24 text-gray"></i>',
      }
    },
  },
  colors: ['#924AEF', '#E328AF'],
 
  stroke: {
    curve: 'smooth'
  },
  xaxis: {
    categories: ['Sunday', '', 'Monday', '', 'Tuesday', '', 'Thursday', '', 'Friday', '', 'Saturday'],
  },
  legend: {
    show: true,
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '16px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    labels: {
        colors: '#ff0000',
        useSeriesColors: true
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_five = new ApexCharts(document.querySelector("#chart-5"), options);
chart_five.render();


// Chart 6 
var options = {
  series: [75],
  chart: {
    height: 450,
    type: 'radialBar',
  },
  colors: ['#924AEF'],
  plotOptions: {
    radialBar: {
      startAngle: -180,
      endAngle: 180,
      hollow: {
        size: '30%',
        background: 'transparent',
      },
      track: {
        background: "#F5F5F5",
      },
      dataLabels: {
        name: {
          show: true,
          offsetY: 30,
          fontFamily: "'Open Sans' Sans-serif",
          fontSize: '16px',
          fontWeight: 400,
          color: '#a5a5a5',
        },
        value: {
          offsetY: -10,
          fontFamily: "'Cairo' Sans-serif",
          fontSize: '34px',
          fontWeight: 700,
        }
      }
    },
  },
  labels: ['of Employee'],
  responsive: [
    {
      breakpoint: 1199,
      options: {
          chart: {
            height: 350,
          },
        plotOptions: {
          radialBar: {
            hollow: {
              size: '30%',
              background: 'transparent',
            },
            track: {
              background: "#F5F5F5",
            },
            dataLabels: {
              name: {
                show: true,
                offsetY: 30,
                fontFamily: "'Open Sans' Sans-serif",
                fontSize: '12px',
                fontWeight: 400,
                color: '#a5a5a5',
              },
              value: {
                offsetY: -10,
                fontFamily: "'Cairo' Sans-serif",
                fontSize: '24px',
                fontWeight: 700,
              }
            }
          },
        },
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      },
    }
  ]
};

var chart_six = new ApexCharts(document.querySelector("#chart-6"), options);
chart_six.render();


// Chart 7
var options = {
  series: [
    {
      name: "This Week",
      data: [160, 74, 60, 127]
    }, 
    {
      name: "Last Week",
      data: [190, 99, 123, 145]
    }
  ],
  chart: {
    type: 'bar',
    height: 400,
    toolbar: {
      show: true,
      tools: {
        download: '<i class="bi bi-three-dots-vertical fs-24 text-gray"></i>',
      }
    },
  },
  plotOptions: {
    bar: {
      horizontal: true,
      barHeight: '30%',
      dataLabels: {
        position: 'top',
      },
    }
  },
  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  colors: ['#5ECFFF', '#E328AF'],
  
  // title: {
  //   text: 'Attendance Overview',
  //   align: 'left'
  // },
  legend: {
    show: true,
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  stroke: {
    show: false,
    width: 1,
    colors: ['#fff']
  },
  tooltip: {
    shared: true,
    intersect: false
  },
  xaxis: {
    categories: ['W4', 'W3', 'W2', 'W1'],
  },
  yaxis: {
      min: 0,
      max: 275,
  },
  grid: {
    show: true,
    borderColor: '#C2C2C2',
    strokeDashArray: 7,
    position: 'back',
    xaxis: {
        lines: {
            show: true
        }
    },   
    yaxis: {
        lines: {
            show: false
        }
    },  
    row: {
        colors: undefined,
        opacity: 0.5
    },  
    column: {
        colors: undefined,
        opacity: 0.5
    },  
    padding: {
        top: 0,
        right: 0,
        bottom: 0,
        left: 0
    },  
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_seven = new ApexCharts(document.querySelector("#chart-7"), options);
chart_seven.render();


// Chart 8
var options = {
  series: [
    {
      name: 'Week 1',
      data: [80, 60, 80, 30, 52, 58, 33, 60, 46, 87, 99, 76]
    }, 
    {
      name: 'Week 2',
      data: [95, 70, 55, 50, 70, 95, 71, 100, 74, 89, 65, 98]
    }, 
    {
      name: 'Week 3',
      data: [55, 40, 55, 60, 55, 48, 82, 53, 61, 76, 56, 34]
    },
    {
      name: 'Week 4',
      data: [95, 55, 70, 95, 70, 48, 52, 73, 41, 89, 45, 39]
    }
  ],
    chart: {
      type: 'bar',
      height: 460,
      toolbar: {
        show: true,
        tools: {
          download: '<i class="bi bi-three-dots-vertical fs-24 text-gray"></i>',
        }
      },
    },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '70%',
      borderRadius: 5
    },
  },
  
  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  colors: ['#924AEF', '#5ECFFF', '#E328AF', '#FF9325'],
  
  // title: {
  //   text: 'Leave Statistic',
  //   align: 'left'
  // },
  legend: {
    show: true,
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 5,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  stroke: {
    show: true,
    width: 4,
    colors: ['transparent']
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  },
  fill: {
    opacity: 1
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return val + " leave"
      }
    }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_eight = new ApexCharts(document.querySelector("#chart-8"), options);
chart_eight.render();


// Chart 9
var options = {
    series: [{
    name: 'Yesterday',
    data: [44, 35, 21, 37, 22, 33, 21, 29, 15, 21, 14, 15]
  }, {
    name: 'Today',
    data: [13, 23, 20, 18, 13, 27, 33, 12, 41, 27, 22, 43]
  }, {
    name: 'Weekly',
    data: [11, 17, 15, 15, 21, 14, 15, 13, 13, 17, 33, 12]
  }],
  chart: {
    type: 'bar',
    height: 350,
    stacked: true,
    toolbar: {
      show: true,
      tools: {
        download: '<i class="bi bi-three-dots-vertical fs-24 text-gray"></i>',
      }
    },
  },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '50%',
      borderRadius: 7
    },
  },
  
  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  colors: ['#924AEF', '#5ECFFF', '#E328AF'],
  // title: {
  //   text: 'Attendance Performance',
  //   align: 'left'
  // },
  legend: {
    show: true,
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  stroke: {
    show: true,
    width: 6,
    colors: ['#fff']
  },
  xaxis: {
    categories: ['14.10', '14.20', '14.30', '14.40', '14.50', '14.60', '15.00', '15.10', '15.20', '15.30', '15.40', '15.50'],
  },
  fill: {
    opacity: 1
  },
  grid: {
    show: true,
    borderColor: '#c2c2c2',
    strokeDashArray: 0,
    position: 'back',
    xaxis: {
        lines: {
            show: true
        }
    },   
    yaxis: {
        lines: {
            show: true
        }
    },  
    row: {
        colors: undefined,
        opacity: 0.5
    },  
    column: {
        colors: undefined,
        opacity: 0.5
    },  
    padding: {
        top: 0,
        right: 0,
        bottom: 0,
        left: 0
    },  
  },
  responsive: [
    {
      breakpoint: 600,
      options: {
        chart: {
          type: 'bar',
          height: 350,
          stacked: true,
          toolbar: {
            show: false
          },
        },
        plotOptions: {
          bar: {
            horizontal: true,
            columnWidth: '100%',
            borderRadius: 0
          },
        },
        
        legend: {
          position: 'bottom',
          horizontalAlign: 'left'
        }
      }
    }
  ]
};

var chart_nine = new ApexCharts(document.querySelector("#chart-9"), options);
chart_nine.render();


// Chart 10
var options = {
    series: [{
      name: 'This Week',
      data: [44, 35, 51, 37, 22, 33, 21]
    }, {
      name: 'Last Week',
      data: [13, 23, 30, 18, 43, 27, 33]
    }],
  chart: {
    type: 'bar',
    height: 350,
    toolbar: {
      show: true,
      tools: {
        download: '<i class="bi bi-three-dots-vertical fs-24 text-gray"></i>',
      }
    },
  },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '50%',
      borderRadius: 10,
    },
  },

  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  colors: ['#E328AF', '#924AEF',],
  // title: {
  //   text: 'Recruitment Progress',
  //   align: 'left'
  // },
  stroke: {
    show: true,
    width: 5,
    colors: ['transparent']
  },
  xaxis: {
    categories: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
  },
  fill: {
    opacity: 1
  },

  
  legend: {
    show: true,
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '16px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    labels: {
      colors: '#A5A5A5',
      useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_ten = new ApexCharts(document.querySelector("#chart-10"), options);
chart_ten.render();


// Chart 11
var options = {
  series: [{
    name: 'Employee',
    data: [0, 58, 39, 59, 38, 71, 75, 70, 82]
  }],
  chart: {
      height: 150,
      type: 'line',
    
      toolbar: {
        show: false
      },
  },

  colors: ['#FFFFFF'],
  stroke: {
    width: 3,
    curve: 'smooth'
  },
  grid: {
    show: true,
    position: 'back',
    xaxis: {
      lines: {
        show: true
      },
    },
  },
  xaxis: {
    show: false,
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  
  yaxis: {
    show: false,
    lines: {
      show: true
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_eleven = new ApexCharts(document.querySelector("#chart-11"), options);
chart_eleven.render();


// Chart 12
var options = {
  series: [
    {
      name: 'series1',
      data: [10, 5, 40, 20, 25, 0, 35, 45, 0]
    }
  ],
  chart: {
    height: 100,
    type: 'area',    
    toolbar: {
      show: false
    },
    sparkline: {
      enabled: true
    },
  },
  colors: ['#924AEF'],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth'
  },
  
  grid: {
    show: false,
    position: 'front'
  },
  xaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_twelve = new ApexCharts(document.querySelector("#chart-12"), options);
chart_twelve.render();


// CHart 13
var options = {
  series: [75],
  chart: {
    height: 200,
    width: 200,
    type: 'radialBar',
  },
  colors: ['#924AEF'],
  plotOptions: {
    radialBar: {
      hollow: {
        size: '65%',
        margin: 15,
        background: '#F5F5F5',
      },
      track: {
        background: "#F5F5F5",
      },
      dataLabels: {
        name: {
          show: false
        },
        value: {
          offsetY: 15,
          fontFamily: "'Cairo' Sans-serif",
          fontSize: '28px',
          fontWeight: 700,
        }
      }
    },
  },
  labels: ['Hired Candidates'],
  responsive: [
    {
      breakpoint: 1399,
      options: {
        plotOptions: {
          radialBar: {
            hollow: {
              size: '70%',
              margin: 10,
              background: '#F5F5F5',
            },
            track: {
              background: "#F5F5F5",
            },
            dataLabels: {
              name: {
                show: false
              },
              value: {
                offsetY: 10,
                fontFamily: "'Cairo' Sans-serif",
                fontSize: '18px',
                fontWeight: 600,
              }
            }
          },
        },
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      },
    }
  ]
};

var chart_thirteen = new ApexCharts(document.querySelector("#chart-13"), options);
chart_thirteen.render();


// CHart 14
var options = {
  series: [75],
  chart: {
    width: 64,
    height: 120,
    type: 'radialBar',
  },
  colors: ['#924AEF'],
  plotOptions: {
    radialBar: {
      hollow: {
        size: '50%',
        margin: 0,
        background: undefined,
      },
      track: {
        background: "#F6EEFF",
      },
      dataLabels: {
        name: {
          show: false
        },
        value: {
          offsetY: 10,
          fontFamily: "'Cairo' Sans-serif",
          fontSize: '18px',
          fontWeight: 700,
        }
      }
    },
  },
  stroke: {
    lineCap: "round",
  },
  labels: ['Hired Candidates'],
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_fourteen = new ApexCharts(document.querySelector("#chart-14"), options);
chart_fourteen.render();


// CHart 15
var options = {
  series: [50],
  chart: {
    width: 64,
    height: 120,
    type: 'radialBar',
  },
  colors: ['#E328AF'],
  plotOptions: {
    radialBar: {
      hollow: {
        size: '50%',
        margin: 0,
        background: undefined,
      },
      track: {
        background: "#F6EEFF",
      },
      dataLabels: {
        name: {
          show: false
        },
        value: {
          offsetY: 10,
          fontFamily: "'Cairo' Sans-serif",
          fontSize: '18px',
          fontWeight: 700,
        }
      }
    },
  },
  stroke: {
    lineCap: "round",
  },
  labels: ['Hired Candidates'],
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_fifteen = new ApexCharts(document.querySelector("#chart-15"), options);
chart_fifteen.render();


// CHart 16
var options = {
  series: [25],
  chart: {
    width: 64,
    height: 120,
    type: 'radialBar',
  },
  colors: ['#5ECFFF'],
  plotOptions: {
    radialBar: {
      hollow: {
        size: '50%',
        margin: 0,
        background: undefined,
      },
      track: {
        background: "#F6EEFF",
      },
      dataLabels: {
        name: {
          show: false
        },
        value: {
          offsetY: 10,
          fontFamily: "'Cairo' Sans-serif",
          fontSize: '18px',
          fontWeight: 700,
        }
      }
    },
  },
  stroke: {
    lineCap: "round",
  },
  labels: ['Hired Candidates'],
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_sixteen = new ApexCharts(document.querySelector("#chart-16"), options);
chart_sixteen.render();


// Chart 17
var options = {
  series: [
    {
      name: 'Completed',
      data: [80, 60, 80, 30, 52, 58, 33]
    }, 
    {
      name: 'Ongoing',
      data: [95, 70, 55, 50, 70, 95, 71]
    }, 
    {
      name: 'Unfinished',
      data: [55, 40, 55, 60, 55, 48, 82]
    },
  ],
    chart: {
      type: 'bar',
      height: 460, 
      toolbar: {
        show: true,
        tools: {
          download: '<i class="bi bi-three-dots-vertical fs-24 text-gray"></i>',
        }
      },
    },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '50%',
      borderRadius: 5
    },
  },
  
  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  colors: ['#924AEF', '#5ECFFF', '#E328AF', '#FF9325'],
  
  // title: {
  //   text: 'Leave Statistic',
  //   align: 'left'
  // },
  legend: {
    show: true,
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 5,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  stroke: {
    show: true,
    width: 4,
    colors: ['transparent']
  },
  xaxis: {
    categories: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
  },
  fill: {
    opacity: 1
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return val + " leave"
      }
    }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_seventeen = new ApexCharts(document.querySelector("#chart-17"), options);
chart_seventeen.render();


// Chart 18
var options = {
  series: [
    {
      name: 'Marine Sprite',
      data: [50]
    },
    {
      name: 'Marine Sprite',
      data: [25]
    },
    {
      name: 'Marine Sprite',
      data: [5]
    },
  ],
  chart: {
    type: 'bar',
    height: 150,
    stacked: true,
    // stackType: '100%'
  },
  plotOptions: {
    bar: {
      horizontal: true,
    },
  },
  stroke: {
    width: 1,
    colors: ['#fff']
  },
  xaxis: {
    categories: [2008],
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return val + "%"
      }
    }
  },
  fill: {
    opacity: 1

  },
  legend: {
    show: true,
    position: 'top',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 5,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
};

var chart_eighteen = new ApexCharts(document.querySelector("#chart-18"), options);
chart_eighteen.render();


// Chart 19
var options = {
  series: [65, 35],
  labels: ['Completed', 'Ongoing'],
  chart: {
    type: 'donut',
    width: '300',
  },
  colors: ['#924AEF', '#E328AF'],
  plotOptions: {
    pie: {
      startAngle: -90,
      endAngle: 90,
      offsetY: 0
    }
  },
  grid: {
    padding: {
      bottom: -100
    }
  },
  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  legend: {
    show: false,
    position: 'bottom',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 0,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  // responsive: [{
  //   breakpoint: 300,
  //   options: {
  //     chart: {
  //       width: 200
  //     },
  //     legend: {
  //       position: 'bottom'
  //     }
  //   }
  // }]
};

var chart_nineteen = new ApexCharts(document.querySelector("#chart-19"), options);
chart_nineteen.render();


// Chart 20
var options = {
  series: [
    {
      name: 'series1',
      data: [2, 8, 5, 7, 5, 8, 5, 10]
    }
  ],
  chart: {
    height: 200,
    type: 'area',    
    toolbar: {
      show: false
    },
  },
  colors: ['#924AEF'],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth'
  },
  
  grid: {
    show: true,
    position: 'back',
    borderColor: '#C2C2C2',
    strokeDashArray: 5,
    xaxis: {
        lines: {
            show: false
        }
    },   
    yaxis: {
        lines: {
            show: true
        }
    }, 
  },
  xaxis: {
    categories: ['', '', '', '', '', '', '', ''],
  },
  yaxis: {
      title: {
        text: 'AM'
      }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_twenty = new ApexCharts(document.querySelector("#chart-20"), options);
chart_twenty.render();


// Chart 21
var options = {
  series: [
    {
      name: 'series1',
      data: [10, 5, 40, 20, 25, 0, 35, 45, 0]
    }
  ],
  chart: {
    height: 200,
    type: 'area',    
    toolbar: {
      show: false
    },
  },
  colors: ['#924AEF'],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth'
  },
  
  grid: {
    show: true,
    position: 'back'
  },

  xaxis: {
    show: false,
    lines: {
      show: true
    },
    labels: {
      show: true
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    },
    categories: ['2015', '2016', '2017', '2018', '2019', '2020', '2021', '2022', '2023'],
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_twentyone = new ApexCharts(document.querySelector("#chart-21"), options);
chart_twentyone.render();


// Chart 22
var options = {
  series: [
    {
      name: 'Week 1',
      data: [80, 60, 80, 30, 52, 58, 33, 65, 74, 33, 43, 33]
    }, 
    {
      name: 'Week 2',
      data: [95, 70, 55, 50, 70, 95, 71, 55, 66, 45, 54, 63]
    }, 
    {
      name: 'Week 3',
      data: [55, 40, 55, 60, 55, 48, 82, 42, 76, 98, 34, 67]
    },
  ],
    chart: {
      type: 'bar',
      height: 270,
      toolbar: {
        show: true,
        tools: {
          download: '<i class="bi bi-three-dots-vertical fs-24 text-gray"></i>',
        }
      },
    },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '50%',
      borderRadius: 5
    },
  },
  
  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  colors: ['#924AEF', '#5ECFFF', '#E328AF'],
  
  // title: {
  //   text: 'Leave Statistic',
  //   align: 'left'
  // },
  legend: {
    show: true,
    position: 'top',
    horizontalAlign: 'left',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 5,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0,
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  stroke: {
    show: true,
    width: 4,
    colors: ['transparent']
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  },
  fill: {
    opacity: 1
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return val + " leave"
      }
    }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_twentytwo = new ApexCharts(document.querySelector("#chart-22"), options);
chart_twentytwo.render();


// Chart 23 
var options = {
  series: [
    {
      name: 'series1',
      data: [5, 45, 20, 40, 10, 60, 5, 75]
    }
  ],
  chart: {
    height: 150,
    type: 'area',    
    toolbar: {
      show: false
    },
    sparkline: {
      enabled: true
    },
  },
  colors: ['#924AEF'],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth'
  },
  markers: {
      size: 7,
      colors: undefined,
      strokeColors: '#fff',
      strokeWidth: 4,
      strokeOpacity: 0.9,
      strokeDashArray: 0,
      fillOpacity: 1,
      shape: "circle",
      radius: 50,
      hover: {
        size: 8,
        sizeOffset: 3
      }
  },
  grid: {
    show: false,
    position: 'front'
  },
  xaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_twentythree = new ApexCharts(document.querySelector("#chart-23"), options);
chart_twentythree.render();


// Chart 24
var options = {
  series: [
    {
      name: 'This Week',
      data: [80, 95, 60, 70, 70, 55]
    }, 
  ],
    chart: {
      type: 'bar',
      height: 135,
      // stacked: true,
      toolbar: {
        show: false,
      },
    },
  plotOptions: {
    bar: {
      distributed: true,
      horizontal: false,
      columnWidth: '70%',
      borderRadius: 5,
    },
  },
  
  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  colors: ['#924AEF', '#E328AF'],
  
  // title: {
  //   text: 'Leave Statistic',
  //   align: 'left'
  // },
  legend: {
    show: false,
    position: 'top',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 5,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0,
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  stroke: {
    show: true,
    width: 10,
    colors: ['transparent']
  },
  xaxis: {
    categories: [''],
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  grid: {
    xaxis: {
      show: false,
      lines: {
        show: false
      },
      labels: {
        show: false
      },
      axisBorder: {
        show: false
      },
      axisTicks: {
        show: false
      }
    },
    yaxis: {
      show: false,
      lines: {
        show: false
      },
      labels: {
        show: false
      },
      axisBorder: {
        show: false
      },
      axisTicks: {
        show: false
      }
    },
  },
  fill: {
    opacity: 1
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return val + " interview"
      }
    }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_twentyfour = new ApexCharts(document.querySelector("#chart-24"), options);
chart_twentyfour.render();


// Chart 25 
var options = {
  series: [
    {
      name: 'series1',
      data: [5, 45, 20, 40, 10, 60, 5, 75]
    }
  ],
  chart: {
    height: 150,
    type: 'area',    
    toolbar: {
      show: false
    },
    sparkline: {
      enabled: true
    },
  },
  colors: ['#924AEF'],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth'
  },
  markers: {
      size: 0,
      colors: undefined,
      strokeColors: '#fff',
      strokeWidth: 4,
      strokeOpacity: 0.9,
      strokeDashArray: 0,
      fillOpacity: 1,
      shape: "circle",
      radius: 50,
      hover: {
        size: 8,
        sizeOffset: 3
      }
  },
  grid: {
    show: false,
    position: 'front'
  },
  xaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_twentyfive = new ApexCharts(document.querySelector("#chart-25"), options);
chart_twentyfive.render();


// Chart 26 
var options = {
  series: [
    {
      name: 'series1',
      data: [5, 45, 20, 40, 25, 55]
    }
  ],
  chart: {
    height: 150,
    type: 'line',    
    toolbar: {
      show: false
    },
    sparkline: {
      enabled: true
    },
  },
  colors: ['#E328AF'],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth'
  },
  markers: {
      size: 7,
      colors: undefined,
      strokeColors: '#fff',
      strokeWidth: 4,
      strokeOpacity: 0.9,
      strokeDashArray: 0,
      fillOpacity: 1,
      shape: "circle",
      radius: 50,
      hover: {
        size: 8,
        sizeOffset: 3
      }
  },
  grid: {
    show: false,
    position: 'front'
  },
  xaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_twentysix = new ApexCharts(document.querySelector("#chart-26"), options);
chart_twentysix.render();


// Chart 27
var options = {
  chart: {
    type: 'donut',
    width: 400
  },
  series: [20, 16, 16, 12, 25, 10],
  labels: ['UI Designer', 'Front-End Developer', 'Back-End Developer', 'UX Researcher', 'Project Manager', 'Other'],
  fill: {
    colors: ['#924AEF', '#5ECFFF', '#E328AF', '#FF9325', '#FF4A55', '#DFEDF2']
  },
  plotOptions: {
    pie: {
      // size: 200,
      donut: {
        size: '50%',
        // labels: {
        //   show: true,
        //   name: {
        //     show: true,
        //   },
        //   value: {
        //     show: true,
        //     fontSize: '34px',
        //     fontFamily: "'Cairo' Sans-serif",
        //     fontWeight: 700,
        //   },
        //   total: {
        //     show: true,
        //     showAlways: true,
        //     label: "Total",
        //     fontSize: '14px',
        //     fontFamily: "'Open Sans' Sans-serif",
        //     fontWeight: 400,
        //     color: '#a5a5a5',
        //   },
        // },
      },
    },
  },
  legend: {
    show: false,
    position: 'right',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 5,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: true
    },
    markers: {
        width: 20,
        height: 20,
        offsetX: -5,
        offsetY: 6,
    },
    itemMargin: {
      horizontal: 0,
      vertical: 8
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        width: 200
      },
      legend: {
        position: 'bottom'
      }
    }
  }]
};

var chart_twentyseven = new ApexCharts(document.querySelector("#chart-27"), options);
chart_twentyseven.render();


// Chart 28
var options = {
  series: [{
    name: 'Score',
    data: [10, 35, 28, 20, 7, 19, 7]
  }, ],
  chart: {
    type: 'bar',
    height: 350,
    // stacked: true,
    stackType: '100%',
    toolbar: {
      show: false
    },
  },

  plotOptions: {
    bar: {
      distributed: true, // this line is mandatory
      horizontal: true,
      barHeight: '90%',
      borderRadius: 6
    },
  },

  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  colors: ['#FF4A55', '#38E25D', '#38E25D', '#FF9325', '#FF4A55', '#FF9325', '#FF4A55'],

  // title: {
  //   text: 'Attendance Performance',
  //   align: 'left'
  // },
  legend: {
    show: false,
    position: 'top',
    horizontalAlign: 'right',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  stroke: {
    show: true,
    width: 15,
    colors: ['transparent']
  },
  xaxis: {
    categories: ['1PM', '12AM', '10AM', '8AM', '6AM', '4AM', '2PM'],
  },
  grid: {
    show: false,
  },
};

var chart_twentyeight = new ApexCharts(document.querySelector("#chart-28"), options);
chart_twentyeight.render();


// Chart 29
var options = {
  series: [
    {
      name: "Last Week",
      data: [10, 70, 30, 40, 15, 90, 45, 55]
    },
    {
      name: "This Week",
      data: [15, 30, 75, 30, 98, 10, 45, 75]
    }
  ],
  chart: {
    height: 250,
    type: 'line',
    zoom: {
      enabled: false
    },
    toolbar: {
      show: false,
      tools: {
        download: '<i class="bi bi-three-dots-vertical fs-24 text-gray"></i>',
      }
    },
  },
  colors: ['#924AEF', '#E328AF'],
 
  stroke: {
    curve: 'smooth'
  },
  xaxis: {
    categories: ['Sunday', '', 'Monday', '', 'Tuesday', '', 'Thursday', '', 'Friday', '', 'Saturday'],
  },
  legend: {
    show: true,
    position: 'bottom',
    horizontalAlign: 'center',
    fontSize: '16px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 8,
    labels: {
        colors: '#ff0000',
        useSeriesColors: true
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_twentynine = new ApexCharts(document.querySelector("#chart-29"), options);
chart_twentynine.render();


// Chart 30
var options = {
  series: [
    {
      name: "0",
      data: [30, 0, 70, 30, 50, 20, 60]
    },
    {
      name: "25",
      data: [25, 20, 30, 10, 50, 70, 5]
    },
    {
      name: "50",
      data: [30, 0, 70, 30, 50, 20, 60]
    },
    {
      name: "75",
      data: [25, 20, 30, 10, 50, 70, 5]
    },
    {
      name: "100",
      data: [25, 20, 30, 10, 50, 70, 5]
    }
  ],
  xaxis: {
    categories: ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:0', '16:00'],
  },
  chart: {
    height: 250,
    type: 'heatmap',    
    toolbar: {
      show: false,
    },
  },
  plotOptions: {
    heatmap: {
      colorScale: {
        ranges: [{
            from: 0,
            to: 25,
            color: '#D8C5F0',
            name: '0-25',
          },
          {
            from: 26,
            to: 50,
            color: '#B18BE1',
            name: '26-50',
          },
          {
            from: 51,
            to: 75,
            color: '#8B52D2',
            name: '51-75',
          },          
          {
            from: 76,
            to: 100,
            color: '#924AEF',
            name: '76-100',
          }
        ]
      }
    }
  },
  
  legend: {
    show: true,
    position: 'bottom',
    fontSize: '16px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    labels: {
        // colors: '#ff0000',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  dataLabels: {
    enabled: false
  },
  stroke: {
    width: 3
  },
  // colors: ["#924AEF"],
  
};

var chart_thirty = new ApexCharts(document.querySelector("#chart-30"), options);
chart_thirty.render();


// Chart 31
var options = {
  chart: {
    type: 'donut',
    width: 300
  },
  series: [20, 16, 16, 12, 25, 10],
  labels: ['UI Designer', 'Front-End Developer', 'Back-End Developer', 'UX Researcher', 'Project Manager', 'Other'],
  fill: {
    colors: ['#924AEF', '#5ECFFF', '#E328AF', '#FF9325', '#FF4A55', '#DFEDF2']
  },
  plotOptions: {
    pie: {
      // size: 200,
      donut: {
        size: '50%',
      },
    },
  },
  legend: {
    show: false,
    position: 'right',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 5,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: true
    },
    markers: {
        width: 20,
        height: 20,
        offsetX: -5,
        offsetY: 6,
    },
    itemMargin: {
      horizontal: 0,
      vertical: 8
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        width: 200
      },
      legend: {
        position: 'bottom'
      }
    }
  }]
};

var chart_thirtyone = new ApexCharts(document.querySelector("#chart-31"), options);
chart_thirtyone.render();


// Chart 32 
var options = {
  series: [87, 75, 44],
  labels: ['Apps', 'Website', 'Other'],
  chart: {
    height: 250,
    type: 'radialBar',
  },
  colors: ['#924AEF', '#E328AF', '#5ECFFF'],
  plotOptions: {
    radialBar: {
      startAngle: -180,
      endAngle: 180,
      hollow: {
        margin: 0,
        size: '20%',
        background: '#fff',
      },
      track: {
        background: "#F5F5F5",
        margin: 10,
      },
      dataLabels: {
        name: {
          offsetY: 0,
          fontSize: '16px',
        },
        value: {
          offsetY: 0,
          fontSize: '12px',
        },
      }
    }
  },
  
  legend: {
    show: true,
    floating: true,
    position: 'right',
    offsetX: -30,
    offsetY: 50
  },
};

var chart_thirtytwo = new ApexCharts(document.querySelector("#chart-32"), options);
chart_thirtytwo.render();


// Chart 33
var options = {
  series: [
    {
      name: "Yesterday",
      data: [30, 0, 70, 30, 50, 20, 60, 5]
    },
    {
      name: "Today",
      data: [25, 20, 30, 10, 50, 70, 5, 25]
    }
  ],
  chart: {
    height: 350,
    type: 'line',
    zoom: {
      enabled: false
    },
    toolbar: {
      show: false,
    },
  },
  colors: ['#924AEF', '#E328AF'],
 
  stroke: {
    curve: 'smooth'
  },
  xaxis: {
    categories: ['14.10', '14.20', '14.30', '14.40', '14.50', '14.60', '15.00', '15.10', '15.20', '15.30'],
  },
  legend: {
    show: true,
    position: 'top',
    fontSize: '16px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    labels: {
        colors: '#ff0000',
        useSeriesColors: true
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_thirtythree = new ApexCharts(document.querySelector("#chart-33"), options);
chart_thirtythree.render();


// Chart 34
var options = {
  series: [62],
  chart: {
    width: 130,
    height: 200,
    type: 'radialBar',
  },
  colors: ['#E328AF'],
  plotOptions: {
    radialBar: {
      hollow: {
        size: '45%',
        margin: 0,
        background: undefined,
      },
      track: {
        background: "#FFCFF2",
      },
      dataLabels: {
        name: {
          show: false
        },
        value: {
          offsetY: 10,
          fontFamily: "'Cairo' Sans-serif",
          fontSize: '18px',
          fontWeight: 700,
        }
      }
    },
  },
  stroke: {
    lineCap: "round",
  },
  labels: ['Hired Candidates'],
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_thirtyfour = new ApexCharts(document.querySelector("#chart-34"), options);
chart_thirtyfour.render();

// Chart 35
var options = {
  series: [38],
  chart: {
    width: 130,
    height: 200,
    type: 'radialBar',
  },
  colors: ['#5ECFFF'],
  plotOptions: {
    radialBar: {
      hollow: {
        size: '45%',
        margin: 0,
        background: undefined,
      },
      track: {
        background: "#DFEDF2",
      },
      dataLabels: {
        name: {
          show: false
        },
        value: {
          offsetY: 10,
          fontFamily: "'Cairo' Sans-serif",
          fontSize: '18px',
          fontWeight: 700,
        }
      }
    },
  },
  stroke: {
    lineCap: "round",
  },
  labels: ['Hired Candidates'],
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_thirtyfive = new ApexCharts(document.querySelector("#chart-35"), options);
chart_thirtyfive.render();


// Chart 36
var options = {
  series: [
    {
      name: 'Spam',
      data: [50]
    }, 
    {
      name: 'Promotional',
      data: [95]
    }, 
  ],
    chart: {
      type: 'bar',
      width: 130,
      height: 150, 
      toolbar: {
        show: false,
      },
    },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '100%',
      borderRadius: 15
    },
  },
  
  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  colors: ['#DFEDF2', '#5ECFFF'],
  
  // title: {
  //   text: 'Leave Statistic',
  //   align: 'left'
  // },
  legend: {
    show: false,
    position: 'right',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 5,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  stroke: {
    show: true,
    width: 15,
    colors: ['transparent']
  },

  xaxis: {
    show: false,
    categories: ['X-Axis'],
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  grid: {
    xaxis: {
      lines: {
        show: false
      }
    },
    yaxis: {
      lines: {
        show: false
      }
    }
  },
  fill: {
    opacity: 1
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return val + " Emails"
      }
    }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_thirtysix = new ApexCharts(document.querySelector("#chart-36"), options);
chart_thirtysix.render();


// Chart 37
var options = {
  series: [
    {
      name: "Emails",
      data: [0, 50, 40, 90, 30, 50, 5]
    },
  ],
  chart: {
    height: 150,
    type: 'line',
    zoom: {
      enabled: false
    },
    toolbar: {
      show: false,
    },
  },
  colors: ['#924AEF', '#E328AF'],
 
  stroke: {
    curve: 'smooth'
  },
  xaxis: {
    show: false,
    categories: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
    lines: {
      show: false
    },
    labels: {
      show: true
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  grid: {
    xaxis: {
      lines: {
        show: false
      }
    },
    yaxis: {
      lines: {
        show: false
      }
    }
  },
  legend: {
    show: false,
    position: 'top',
    fontSize: '16px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    labels: {
        colors: '#ff0000',
        useSeriesColors: true
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_thirtyseven = new ApexCharts(document.querySelector("#chart-37"), options);
chart_thirtyseven.render();


// Chart 38
var options = {
  chart: {
    type: 'donut',
    width: 300
  },
  series: [20, 16, 16, 12, 25, 10],
  labels: ['Primary', 'Promotion', 'Forum', 'Socials', 'Spam', 'Other'],
  fill: {
    colors: ['#924AEF', '#5ECFFF', '#E328AF', '#FF9325', '#FF4A55', '#DFEDF2']
  },
  plotOptions: {
    pie: {
      donut: {
        size: '50%',
      },
    },
  },
  legend: {
    show: false,
    position: 'right',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 5,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: true
    },
    markers: {
        width: 20,
        height: 20,
        offsetX: -5,
        offsetY: 6,
    },
    itemMargin: {
      horizontal: 0,
      vertical: 8
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        width: 200
      },
      legend: {
        position: 'bottom'
      }
    }
  }]
};

var chart_thirtyeight = new ApexCharts(document.querySelector("#chart-38"), options);
chart_thirtyeight.render();

// Chart 39
var options = {
  series: [
    {
      name: 'Ongoing',
      data: [60, 60, 80, 30, 52, 58, 33]
    }, 
    {
      name: 'Unfinished',
      data: [80, 70, 55, 95, 70, 15, 71]
    }, 
  ],
  chart: {
    type: 'bar',
    height: 350, 
    toolbar: {
      show: false,
    },
  },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '45%',
      borderRadius: 7
    },
  },
  
  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  colors: ['#5ECFFF', '#E328AF'],
  legend: {
    show: false,
    position: 'top',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 5,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0,
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  stroke: {
    show: true,
    width: 4,
    colors: ['transparent']
  },
  xaxis: {
  },
  xaxis: {
    categories: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
    labels: {
      show: true,
      rotate: -45,
      rotateAlways: false,
      hideOverlappingLabels: true,
      showDuplicates: false,
      trim: false,
      minHeight: undefined,
      maxHeight: 120,
      style: {
          colors: ['#A5A5A5'],
          fontSize: '14px',
          fontFamily: "'Public Sans' Sans-serif",
          fontWeight: 600,
          cssClass: 'apexcharts-xaxis-label',
      },
    },
  },
  yaxis: {
    labels: {
      show: true,
      // rotate: -45,
      rotateAlways: false,
      hideOverlappingLabels: true,
      showDuplicates: false,
      trim: false,
      minHeight: undefined,
      maxHeight: 120,
      style: {
          colors: ['#A5A5A5'],
          fontSize: '14px',
          fontFamily: "'Public Sans' Sans-serif",
          fontWeight: 600,
          cssClass: 'apexcharts-yaxis-label',
      },
    },
  },
  fill: {
    opacity: 1
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return val + " Projects"
      }
    }
  },
  responsive: [
    {
      breakpoint: 991,
      options: {        
        plotOptions: {
          bar: {
            horizontal: true,
            columnWidth: '100%',
            borderRadius: 5
          },
        },       
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      },
    }
  ]
};

var chart_thirtynine = new ApexCharts(document.querySelector("#chart-39"), options);
chart_thirtynine.render();


// Chart 40
var options = {
  series: [62],
  chart: {
    width: 70,
    height: 120,
    type: 'radialBar',
  },
  colors: ['#924AEF'],
  plotOptions: {
    radialBar: {
      hollow: {
        size: '45%',
        margin: 0,
        background: undefined,
      },
      track: {
        background: "#F5F5F5",
      },
      dataLabels: {
        name: {
          show: false
        },
        value: {
          show: false,
          offsetY: 10,
          fontFamily: "'Cairo' Sans-serif",
          fontSize: '18px',
          fontWeight: 700,
        }
      }
    },
  },

  labels: [''],
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_fourty = new ApexCharts(document.querySelector("#chart-40"), options);
chart_fourty.render();


// Chart 41 
var options = {
  series: [
    {
      name: 'Projects',
      data: [5, 50, 40, 90, 80]
    }
  ],
  chart: {
    height: 150,
    type: 'line',    
    toolbar: {
      show: false
    },
    sparkline: {
      enabled: true
    },
  },
  colors: ['#924AEF'],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth',
    width: 6,
  },
  grid: {
    show: false,
    position: 'front'
  },
  xaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_fourtyone = new ApexCharts(document.querySelector("#chart-41"), options);
chart_fourtyone.render();


// Chart 42 
var options = {
  series: [
    {
      name: 'Clients',
      data: [50, 50, 80, 30, 40]
    }
  ],
  chart: {
    height: 150,
    type: 'line',    
    toolbar: {
      show: false
    },
    sparkline: {
      enabled: true
    },
  },
  colors: ['#924AEF'],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth',
    width: 6,
  },
  grid: {
    show: false,
    position: 'front'
  },
  xaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_fourtytwo = new ApexCharts(document.querySelector("#chart-42"), options);
chart_fourtytwo.render();


// Chart 43
var options = {
  series: [
    {
      name: "Projects",
      data: [10, 25, 22, 42, 40, 60, 35, 42, 30, 54, 55, 45, 47]
    },
  ],
  chart: {
    height: 250,
    type: 'area',
    zoom: {
      enabled: false
    },
    toolbar: {
      show: false,
    },
  },
  colors: ['#924AEF'],
  dataLabels: {
    enabled: false
  },
 
  stroke: {
    curve: 'smooth',
    width: 6,
  },
  
  
  xaxis: {
    categories: ['S', '', 'M', '', 'T', '', 'W', '', 'T', '', 'F', '', 'S'],
    labels: {
      show: true,
      rotate: -45,
      rotateAlways: false,
      hideOverlappingLabels: true,
      showDuplicates: false,
      trim: false,
      minHeight: undefined,
      maxHeight: 120,
      style: {
          colors: ['#A5A5A5'],
          fontSize: '14px',
          fontFamily: "'Public Sans' Sans-serif",
          fontWeight: 600,
          cssClass: 'apexcharts-xaxis-label',
      },
    },
  },
  yaxis: {
    labels: {
      show: true,
      // rotate: -45,
      rotateAlways: false,
      hideOverlappingLabels: true,
      showDuplicates: false,
      trim: false,
      minHeight: undefined,
      maxHeight: 120,
      style: {
          colors: ['#A5A5A5'],
          fontSize: '14px',
          fontFamily: "'Public Sans' Sans-serif",
          fontWeight: 600,
          cssClass: 'apexcharts-yaxis-label',
      },
    },
  },
  grid: {
    yaxis: {
      show: false,
      lines: {
        show: false
      },
      labels: {
        show: false
      },
      axisBorder: {
        show: false
      },
      axisTicks: {
        show: false
      }
    },
  },
  legend: {
    show: true,
    position: 'top',
    fontSize: '16px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    labels: {
        colors: '#ff0000',
        useSeriesColors: true
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  annotations: {
    points: [{
      x: 'T',
      y: 42,
      marker: {
        size: 14,
        strokeColor: '#ffffff',
        fillColor: '#924AEF',
        strokeWidth: 6,
        strokeOpacity: 0.9,
        strokeDashArray: 0,
        fillOpacity: 1,
        shape: "circle",
        radius: 50,
      },
      // label: {
      //   borderColor: '#924AEF',
      //   offsetY: 0,
      //   style: {
      //     color: '#fff',
      //     background: '#924AEF',
      //   },
      //   text: 'Marker Here'
      // }
    }]
  },
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_fourtythree = new ApexCharts(document.querySelector("#chart-43"), options);
chart_fourtythree.render();


// Chart 44
var options = {
  series: [
    {
      name: 'Unpaid',
      data: [60, 60, 80, 30, 52, 58]
    }, 
    {
      name: 'Paid',
      data: [80, 70, 55, 95, 70, 15]
    }, 
  ],
  chart: {
    type: 'bar',
    height: 350, 
    toolbar: {
      show: false,
    },
  },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '50%',
      borderRadius: 7
    },
  },
  
  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  colors: ['#5ECFFF', '#38E25D'],
  legend: {
    show: false,
    position: 'top',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 5,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0,
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  stroke: {
    show: true,
    width: 4,
    colors: ['transparent']
  },
  xaxis: {
  },
  xaxis: {
    categories: ['January', 'February', 'March', 'April', 'May', 'June'],
  },
  yaxis: {
    labels: {
      show: true,
      // rotate: -45,
      rotateAlways: false,
      hideOverlappingLabels: true,
      showDuplicates: false,
      trim: false,
      minHeight: undefined,
      maxHeight: 120,
      style: {
          colors: ['#A5A5A5'],
          fontSize: '14px',
          fontFamily: "'Public Sans' Sans-serif",
          fontWeight: 600,
          cssClass: 'apexcharts-yaxis-label',
      },
    },
  },
  fill: {
    opacity: 1
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return val + " Invoices"
      }
    }
  },
  responsive: [
    {
      breakpoint: 991,
      options: {        
        plotOptions: {
          bar: {
            horizontal: true,
            columnWidth: '100%',
            borderRadius: 5
          },
        },       
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      },
    }
  ]
};

var chart_fourtyfour = new ApexCharts(document.querySelector("#chart-44"), options);
chart_fourtyfour.render();


// CHart 45
var options = {
  series: [75],
  chart: {
    height: 160,
    width: 160,
    type: 'radialBar',
  },
  colors: ['#924AEF'],
  plotOptions: {
    radialBar: {
      hollow: {
        size: '65%',
        margin: 15,
        background: '#F5F5F5',
      },
      track: {
        background: "#F5F5F5",
      },
      dataLabels: {
        name: {
          show: false
        },
        value: {
          offsetY: 15,
          fontFamily: "'Cairo' Sans-serif",
          fontSize: '28px',
          fontWeight: 700,
        }
      }
    },
  },
  labels: ['Hired Candidates'],
  responsive: [
    {
      breakpoint: 1399,
      options: {
        plotOptions: {
          radialBar: {
            hollow: {
              size: '70%',
              margin: 10,
              background: '#F5F5F5',
            },
            track: {
              background: "#F5F5F5",
            },
            dataLabels: {
              name: {
                show: false
              },
              value: {
                offsetY: 10,
                fontFamily: "'Cairo' Sans-serif",
                fontSize: '18px',
                fontWeight: 600,
              }
            }
          },
        },
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      },
    }
  ]
};

var chart_fourtyfive = new ApexCharts(document.querySelector("#chart-45"), options);
chart_fourtyfive.render();

// Chart 46
var options = {
  series: [
    {
      name: 'This Week',
      data: [80, 95, 60, 70]
    }, 
  ],
    chart: {
      type: 'bar',
      width: 150,
      height: 100,
      // stacked: true,
      toolbar: {
        show: false,
      },
    },
  plotOptions: {
    bar: {
      distributed: true,
      horizontal: false,
      columnWidth: '100%',
      borderRadius: 5,
    },
  },
  
  dataLabels: {
    enabled: false,
    offsetX: -6,
    style: {
      fontSize: '12px',
      colors: ['#fff']
    }
  },
  colors: ['#924AEF', '#924AEF', '#924AEF', '#924AEF'],
  
  // title: {
  //   text: 'Leave Statistic',
  //   align: 'left'
  // },
  legend: {
    show: false,
    position: 'top',
    fontSize: '14px',
    fontFamily: "'Open Sans' Sans-serif",
    fontWeight: 400,
    offsetY: 5,
    labels: {
        colors: '#A5A5A5',
        useSeriesColors: false
    },
    markers: {
        width: 13,
        height: 13,
        offsetX: -10,
        offsetY: 0,
    },
    itemMargin: {
      horizontal: 25,
      vertical: 0
    },
    onItemClick: {
        toggleDataSeries: true
    },
    onItemHover: {
        highlightDataSeries: true
    },
  },
  stroke: {
    show: true,
    width: 10,
    colors: ['transparent']
  },
  xaxis: {
    categories: [''],
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    show: false,
    lines: {
      show: false
    },
    labels: {
      show: false
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  grid: {
    xaxis: {
      show: false,
      lines: {
        show: false
      },
      labels: {
        show: false
      },
      axisBorder: {
        show: false
      },
      axisTicks: {
        show: false
      }
    },
    yaxis: {
      show: false,
      lines: {
        show: false
      },
      labels: {
        show: false
      },
      axisBorder: {
        show: false
      },
      axisTicks: {
        show: false
      }
    },
  },
  fill: {
    opacity: 1
  },
  tooltip: {
    y: {
      formatter: function (val) {
        return val + " clients"
      }
    }
  },
  responsive: [
    {
      breakpoint: 1200,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        },
      }
    }
  ]
};

var chart_fourtysix = new ApexCharts(document.querySelector("#chart-46"), options);
chart_fourtysix.render();


// Chart 47 
var options = {
  series: [{
  name: 'Employee',
  data: [10, 40, 45, 10, 75, 12, 60, 50, 20, 60]
}],
chart: {
    height: 250,
    type: 'line',
  
    toolbar: {
      show: false
    },
},
stroke: {
  width: 2,
  // curve: 'smooth',
  curve: 'straight',
  dashArray: 8,
},
colors: ['#A5A5A5'],
markers: {
  size: 10,
  colors: '#924AEF',
  strokeColors: '#fff',
  strokeWidth: 0,
  strokeOpacity: 0.9,
  strokeDashArray: 0,
  fillOpacity: 1,
  shape: "circle",
  radius: 50,
  hover: {
    size: 12,
    sizeOffset: 3
  }
},
grid: {
  show: true,
  borderColor: '#F5F5F5',
},
xaxis: {
  show: false,
  lines: {
    show: false
  },
  labels: {
    show: false
  },
  axisBorder: {
    show: false
  },
  axisTicks: {
    show: false
  }
},
yaxis: {
  show: true,
  lines: {
    show: false
  },
  labels: {
    show: false
  },
  axisBorder: {
    show: false
  },
  axisTicks: {
    show: false
  }
},
responsive: [
  {
    breakpoint: 767,
    options: {        
      legend: {
        position: 'bottom',
        horizontalAlign: 'center'
      }
    }
  }
]
};

var chart_fourtyseven = new ApexCharts(document.querySelector("#chart-47"), options);
chart_fourtyseven.render();


// Chart 48
var options = {
  series: [81],
  chart: {
    width: 170,
    height: 280,
    type: 'radialBar',
  },
  colors: ['#924AEF'],
  
  plotOptions: {
    radialBar: {
      hollow: {
        size: '30%',
        margin: 5,
        background: 'transparent',
      },
      track: {
        background: "#F6EEFF",
      },
      dataLabels: {
        name: {
          show: false
        },
        value: {
          offsetY: 5,
          fontFamily: "'Cairo' Sans-serif",
          fontSize: '20px',
          fontWeight: 700,
        }
      }
    },
  },
  
  labels: [''],
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_fourtyeight = new ApexCharts(document.querySelector("#chart-48"), options);
chart_fourtyeight.render();


// Chart 49
var options = {
  series: [22],
  chart: {
    width: 170,
    height: 280,
    type: 'radialBar',
  },
  colors: ['#E328AF'],
  
  plotOptions: {
    radialBar: {
      hollow: {
        size: '30%',
        margin: 5,
        background: 'transparent',
      },
      track: {
        background: "#F6EEFF",
      },
      dataLabels: {
        name: {
          show: false
        },
        value: {
          offsetY: 5,
          fontFamily: "'Cairo' Sans-serif",
          fontSize: '20px',
          fontWeight: 700,
        }
      }
    },
  },

  labels: [''],
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_fourtynine = new ApexCharts(document.querySelector("#chart-49"), options);
chart_fourtynine.render();


// Chart 50
var options = {
  series: [62],
  chart: {
    width: 170,
    height: 280,
    type: 'radialBar',
  },
  colors: ['#5ECFFF'],
  
  plotOptions: {
    radialBar: {
      hollow: {
        size: '30%',
        margin: 5,
        background: 'transparent',
      },
      track: {
        background: "#F6EEFF",
      },
      dataLabels: {
        name: {
          show: false
        },
        value: {
          offsetY: 5,
          fontFamily: "'Cairo' Sans-serif",
          fontSize: '20px',
          fontWeight: 700,
        }
      }
    },
  },

  labels: [''],
  responsive: [
    {
      breakpoint: 767,
      options: {        
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
};

var chart_fifty = new ApexCharts(document.querySelector("#chart-50"), options);
chart_fifty.render();