import './bootstrap';
import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';

// Make ApexCharts available globally
window.ApexCharts = ApexCharts;

// Alpine.js component for Sales Trend Chart
document.addEventListener('alpine:init', () => {
    Alpine.data('salesTrendChart', (salesData) => ({
        chart: null,
        salesData: salesData,
        
        init() {
            this.$nextTick(() => {
                this.renderChart();
            });
        },
        
        renderChart() {
            const isDark = document.documentElement.classList.contains('dark');
            
            const dates = this.salesData.map(item => item.date);
            const totals = this.salesData.map(item => parseFloat(item.total) || 0);
            const counts = this.salesData.map(item => parseInt(item.count) || 0);
            
            const options = {
                series: [
                    {
                        name: 'Sales (₱)',
                        type: 'line',
                        data: totals
                    },
                    {
                        name: 'Transactions',
                        type: 'column',
                        data: counts
                    }
                ],
                chart: {
                    height: 320,
                    type: 'line',
                    toolbar: {
                        show: false
                    },
                    background: 'transparent',
                    fontFamily: 'inherit'
                },
                colors: ['#f59e0b', '#3b82f6'],
                stroke: {
                    width: [3, 0],
                    curve: 'smooth'
                },
                plotOptions: {
                    bar: {
                        columnWidth: '50%',
                        borderRadius: 4
                    }
                },
                fill: {
                    opacity: [1, 0.7]
                },
                labels: dates,
                markers: {
                    size: 4,
                    strokeWidth: 0,
                    hover: {
                        size: 6
                    }
                },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        style: {
                            colors: isDark ? '#9ca3af' : '#6b7280'
                        },
                        datetimeFormatter: {
                            year: 'yyyy',
                            month: "MMM 'yy",
                            day: 'dd MMM',
                            hour: 'HH:mm'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: [
                    {
                        title: {
                            text: 'Sales (₱)',
                            style: {
                                color: isDark ? '#9ca3af' : '#6b7280'
                            }
                        },
                        labels: {
                            style: {
                                colors: isDark ? '#9ca3af' : '#6b7280'
                            },
                            formatter: (value) => '₱' + value.toLocaleString()
                        }
                    },
                    {
                        opposite: true,
                        title: {
                            text: 'Transactions',
                            style: {
                                color: isDark ? '#9ca3af' : '#6b7280'
                            }
                        },
                        labels: {
                            style: {
                                colors: isDark ? '#9ca3af' : '#6b7280'
                            }
                        }
                    }
                ],
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    labels: {
                        colors: isDark ? '#d1d5db' : '#374151'
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    theme: isDark ? 'dark' : 'light',
                    y: {
                        formatter: function(value, { seriesIndex }) {
                            if (seriesIndex === 0) {
                                return '₱' + value.toLocaleString(undefined, { minimumFractionDigits: 2 });
                            }
                            return value + ' transactions';
                        }
                    }
                },
                grid: {
                    borderColor: isDark ? '#374151' : '#e5e7eb',
                    strokeDashArray: 4
                }
            };
            
            if (this.chart) {
                this.chart.destroy();
            }
            
            this.chart = new ApexCharts(this.$refs.chart, options);
            this.chart.render();
        },
        
        destroy() {
            if (this.chart) {
                this.chart.destroy();
            }
        }
    }));
});

// Initialize Alpine.js
window.Alpine = Alpine;
Alpine.start();
