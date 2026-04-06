<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'
import ApexCharts from 'apexcharts'

const chartRef = ref(null)
let chart = null

onMounted(() => {
    const options = {
        chart: {
            height: 360,
            type: 'bar',
            stacked: true,
            toolbar: { show: false },
            zoom: { enabled: true },
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '15%',
                endingShape: 'rounded',
                borderRadius: 5,
            },
        },
        dataLabels: { enabled: false },
        series: [
            { name: 'Series A', data: [44, 55, 41, 67, 22, 43, 36, 52, 24, 18, 36, 48] },
            { name: 'Series B', data: [13, 23, 20, 8, 13, 27, 18, 22, 10, 16, 24, 22] },
            { name: 'Series C', data: [11, 17, 15, 15, 21, 14, 11, 18, 17, 12, 20, 18] },
        ],
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        },
        legend: { position: 'bottom' },
        fill: { opacity: 1 },
    };

  chart = new ApexCharts(chartRef.value, options)
  chart.render()
})

onBeforeUnmount(() => {
  if (chart) {
    chart.destroy()
    chart = null
  }
})
</script>

<template>
  <div ref="chartRef"></div>
</template>