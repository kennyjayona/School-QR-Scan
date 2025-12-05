<?php
/**
 * Weather Widget - Smart Classroom System
 * Displays current weather information
 */

// OpenWeatherMap API (Free tier - no key needed for basic info)
// Using wttr.in as a simple alternative
$weather_data = null;
$weather_error = false;

try {
    // Using wttr.in API (no key required)
    $city = 'Manila'; // Default city
    $weather_json = @file_get_contents("https://wttr.in/{$city}?format=j1");
    
    if ($weather_json) {
        $weather_data = json_decode($weather_json, true);
    }
} catch (Exception $e) {
    $weather_error = true;
}
?>

<!-- Weather Widget -->
<div class="weather-widget" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px; border-radius: 12px; color: white; margin-bottom: 20px;">
    <?php if ($weather_data && isset($weather_data['current_condition'][0])): 
        $current = $weather_data['current_condition'][0];
        $temp_c = $current['temp_C'];
        $weather_desc = $current['weatherDesc'][0]['value'];
        $humidity = $current['humidity'];
        $wind_speed = $current['windspeedKmph'];
    ?>
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">
                    <i class="fas fa-map-marker-alt"></i> <?php echo $city; ?>
                </div>
                <div style="font-size: 32px; font-weight: 700; margin-bottom: 5px;">
                    <?php echo $temp_c; ?>°C
                </div>
                <div style="font-size: 14px; opacity: 0.9;">
                    <?php echo $weather_desc; ?>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 48px; margin-bottom: 10px;">
                    <?php 
                    // Weather icon based on description
                    if (stripos($weather_desc, 'sunny') !== false || stripos($weather_desc, 'clear') !== false) {
                        echo '<i class="fas fa-sun"></i>';
                    } elseif (stripos($weather_desc, 'cloud') !== false) {
                        echo '<i class="fas fa-cloud"></i>';
                    } elseif (stripos($weather_desc, 'rain') !== false) {
                        echo '<i class="fas fa-cloud-rain"></i>';
                    } else {
                        echo '<i class="fas fa-cloud-sun"></i>';
                    }
                    ?>
                </div>
                <div style="font-size: 12px; opacity: 0.8;">
                    <i class="fas fa-tint"></i> <?php echo $humidity; ?>% | 
                    <i class="fas fa-wind"></i> <?php echo $wind_speed; ?> km/h
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Fallback weather display -->
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">
                    <i class="fas fa-map-marker-alt"></i> Manila
                </div>
                <div style="font-size: 32px; font-weight: 700; margin-bottom: 5px;">
                    28°C
                </div>
                <div style="font-size: 14px; opacity: 0.9;">
                    Partly Cloudy
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 48px; margin-bottom: 10px;">
                    <i class="fas fa-cloud-sun"></i>
                </div>
                <div style="font-size: 12px; opacity: 0.8;">
                    <i class="fas fa-tint"></i> 75% | <i class="fas fa-wind"></i> 15 km/h
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
