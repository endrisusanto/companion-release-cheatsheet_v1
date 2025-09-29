<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date Input Display</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-6 rounded-lg shadow-lg text-center w-full max-w-md">
        <h1 class="text-2xl font-bold mb-4 text-gray-800">Date Information</h1>
        <div class="space-y-4">
            <div>
                <label for="yesterday" class="block text-lg text-gray-600 mb-1">Yesterday</label>
                <input id="yesterday" type="text" readonly class="w-full p-2 border rounded-md text-gray-800 bg-gray-50" />
            </div>
            <div>
                <label for="today" class="block text-lg text-gray-600 mb-1">Today</label>
                <input id="today" type="text" readonly class="w-full p-2 border rounded-md text-gray-800 bg-gray-50" />
            </div>
            <div>
                <label for="year" class="block text-lg text-gray-600 mb-1">Current Year</label>
                <input id="year" type="text" readonly class="w-full p-2 border rounded-md text-gray-800 bg-gray-50" />
            </div>
        </div>
    </div>

    <script>
        // Get today's date
        const today = new Date();
        
        // Get yesterday's date
        const yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);
        
        // Format dates as YYYY-MM-DD
        const formatDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };
        
        const todayFormatted = formatDate(today);
        const yesterdayFormatted = formatDate(yesterday);
        const currentYear = today.getFullYear();
        
        // Update input fields
        document.getElementById('yesterday').value = yesterdayFormatted;
        document.getElementById('today').value = todayFormatted;
        document.getElementById('year').value = currentYear;
    </script>
</body>
</html>