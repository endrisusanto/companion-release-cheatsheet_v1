<?php
require_once __DIR__ . '/includes/functions.php';
// header.php tidak diperlukan di sini karena file ini di-include oleh index.php yang sudah memuatnya.

$stats = getDailyStats();
// MODIFIED: Menggunakan fungsi baru yang benar untuk mendapatkan jumlah rilis hari ini.
$todayReleases = count(getTodayReleasesFiltered(date('Y-m-d'), 'all'));
$totalReleases = countReleases();
$totalModels = countModels();
?>

<div class="bg-white shadow rounded-lg p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
            <h3 class="text-lg font-medium text-blue-800">Today's Releases</h3>
            <p class="text-3xl font-bold text-blue-600"><?php echo $todayReleases; ?></p>
        </div>
        
        <div class="bg-green-50 p-4 rounded-lg border border-green-100">
            <h3 class="text-lg font-medium text-green-800">Total Releases</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo $totalReleases; ?></p>
        </div>
        
        <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
            <h3 class="text-lg font-medium text-purple-800">Unique Models</h3>
            <p class="text-3xl font-bold text-purple-600"><?php echo $totalModels; ?></p>
        </div>
        
        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
            <h3 class="text-lg font-medium text-yellow-800">Active PICs</h3>
            <p class="text-3xl font-bold text-yellow-600"><?php echo count(array_unique(array_column($stats, 'pic'))); ?></p>
        </div>
    </div>
    
    <div class="bg-gray-50 p-4 rounded-lg">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Daily Releases and PIC Contributions (<?php echo date('M Y'); ?>)</h2>
        <canvas id="dailyChart" height="100"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pastikan data stats tersedia dan bukan null
    const stats = <?php echo json_encode($stats ?? []); ?>;
    if (!stats || stats.length === 0) {
        // Jika tidak ada data, jangan coba render chart
        return;
    }
    
    // Group by date for chart
    const dates = [...new Set(stats.map(item => item.date))].sort();
    const pics = [...new Set(stats.map(item => item.pic))];
    
    // Total daily releases for line chart
    const dailyData = dates.map(date => {
        const items = stats.filter(item => item.date === date);
        return items.reduce((sum, item) => sum + parseInt(item.pic_count), 0); // Menggunakan pic_count agar lebih akurat per entri
    });
    
    // Datasets for each PIC (side-by-side bars, no gaps)
    const colors = [
        'rgba(239, 68, 68, 0.7)',  // Red
        'rgba(59, 130, 246, 0.7)', // Blue
        'rgba(245, 158, 11, 0.7)', // Amber
        'rgba(16, 185, 129, 0.7)', // Green
        'rgba(139, 92, 246, 0.7)'  // Purple
    ];
    
    const barDatasets = pics.map((pic, index) => {
        const data = dates.map(date => {
            const item = stats.find(item => item.date === date && item.pic === pic);
            return item ? parseInt(item.pic_count) : 0;
        });
        return {
            type: 'bar',
            label: pic,
            data: data,
            backgroundColor: colors[index % colors.length],
            borderColor: colors[index % colors.length].replace('0.7', '1'),
            borderWidth: 1,
            borderRadius: 2, // Rounded bars
            barPercentage: 1, // Full width for each bar
            categoryPercentage: 0.5 // No gaps between bars in a date
        };
    });
    
    // Add line dataset for total releases
    barDatasets.push({
        type: 'line',
        label: 'Total Releases',
        data: dailyData,
        borderColor: 'rgba(17, 24, 39, 1)', // Dark gray for contrast
        backgroundColor: 'rgba(17, 24, 39, 0.2)',
        borderWidth: 3,
        tension: 0.4, // Smooth, curved line
        fill: false,
        pointRadius: 4,
        pointHoverRadius: 6,
        yAxisID: 'y'
    });
    
    // Combined Chart
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: dates,
            datasets: barDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Allow height control
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 12,
                            family: "'Inter', sans-serif"
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)' // Subtle grid
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 12,
                            family: "'Inter', sans-serif"
                        },
                        maxRotation: 45,
                        minRotation: 45 // Slight tilt for readability
                    },
                    grid: {
                        display: false // Clean x-axis
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 12,
                            family: "'Inter', sans-serif"
                        },
                        padding: 15
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: {
                        family: "'Inter', sans-serif"
                    },
                    bodyFont: {
                        family: "'Inter', sans-serif"
                    },
                    padding: 10
                }
            },
            interaction: {
                mode: 'index', // Show all tooltips for the same x-value
                intersect: false
            }
        }
    });
});
</script>
<style>
    #dailyChart {
        max-height: 200px; /* Restrict height */
    }
</style>