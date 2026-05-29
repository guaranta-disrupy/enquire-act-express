<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Error</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-red-600">
                    {{ $error ?? 'Database connection failed' }}
                </h3>
            </div>

            <!-- Message Body -->
            <div class="px-6 py-4">
                <p class="text-gray-700 leading-relaxed">
                    {{ $message ?? 'The system is temporarily unavailable. Please try again later.' }}
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="py-4 border-t border-gray-200 flex justify-between px-20">
                <button
                    onclick="retryConnection()"
                    id="retryBtn"
                    class="px-4 py-2 text-blue-700 font-medium hover:text-blue-400 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span id="retryText">RETRY</span>
                </button>
                
                <button
                    onclick="goBack()"
                    class="px-4 py-2 text-blue-700 font-medium hover:text-blue-400 transition-colors duration-200"
                >
                    RELOAD
                </button>
            </div>
        </div>
    </div>

    <script>
        function retryConnection() {
            const btn = document.getElementById('retryBtn');
            const text = document.getElementById('retryText');
            
            btn.disabled = true;
            text.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-700 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Retrying...';
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
        
        function goBack() {
            window.location.reload();
        }
    </script>
</body>
</html>
