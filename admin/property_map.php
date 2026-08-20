<?php
// 1. डेटा प्राप्त करें
$lat = isset($_GET['lat']) ? $_GET['lat'] : '';
$lng = isset($_GET['lng']) ? $_GET['lng'] : '';

$locationName = "Coordinates not provided";

if (!empty($lat) && !empty($lng)) {
    // 2. एड्रेस फेच करने के लिए URL
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lng&zoom=18&addressdetails=1";

    // CURL के जरिए एड्रेस लाना
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL Error fix
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"); // User Agent जरूरी है
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (isset($data['display_name'])) {
        $locationName = $data['display_name'];
    } else {
        $locationName = "Address not found for these coordinates";
    }
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <title>Property Map - Madanpur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* अगर मैप लोड न हो तो बैकग्राउंड कलर दिखे */
        .map-container { background: #e5e7eb; position: relative; }
    </style>
</head>
<body class="bg-gray-100 p-4">

    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
        
        <div class="bg-blue-600 p-4 flex justify-between items-center text-white">
            <h1 class="text-lg font-bold"><i class="fas fa-map-marker-alt"></i> Property Location</h1>
            <button onclick="window.close()" class="bg-red-500 px-3 py-1 rounded hover:bg-red-700">Close</button>
        </div>

        <div class="map-container w-full h-[450px]">
            <?php if (!empty($lat) && !empty($lng)): ?>
                <iframe 
                    width="100%" 
                    height="100%" 
                    frameborder="0" 
                    style="border:0"
                    src="https://maps.google.com/maps?q=<?php echo $lat; ?>,<?php echo $lng; ?>&hl=en&z=15&output=embed" 
                    allowfullscreen>
                </iframe>
            <?php else: ?>
                <div class="flex items-center justify-center h-full text-gray-500 italic">
                    Coordinates not found in URL (?lat=...&lng=...)
                </div>
            <?php endif; ?>
        </div>

        <div class="p-6 border-t">
            <label class="text-sm text-gray-500 font-bold uppercase">Location Address:</label>
            <div class="mt-2 p-3 bg-gray-50 border rounded text-gray-800 flex items-start gap-3">
                <i class="fas fa-search-location mt-1 text-blue-500"></i>
                <span><?php echo htmlspecialchars($locationName); ?></span>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $lat; ?>,<?php echo $lng; ?>" 
                   target="_blank" 
                   class="bg-green-600 text-white px-5 py-2 rounded shadow hover:bg-green-700 flex items-center">
                    <i class="fas fa-external-link-alt mr-2"></i> Open in Google Maps
                </a>
                
                <button onclick="window.location.reload()" class="bg-gray-200 px-5 py-2 rounded hover:bg-gray-300">
                    <i class="fas fa-sync-alt mr-2"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="text-center text-gray-400 text-sm mt-4">
        Coordinates: <?php echo $lat . ", " . $lng; ?>
    </div>

</body>
</html>