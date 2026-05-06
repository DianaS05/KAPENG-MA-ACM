<?php
// add_cafe.php
include 'db_conn.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $rating = $_POST['rating'];
    $category = $_POST['category'];
    $city = $_POST['city'];
    $province = $_POST['province'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $image = $_POST['image'];
    $notes = $_POST['notes'];
    $has_wifi = isset($_POST['has_wifi']) ? 1 : 0;
    $has_sockets = isset($_POST['has_sockets']) ? 1 : 0;
    $has_parking = isset($_POST['has_parking']) ? 1 : 0;
    $pet_friendly = isset($_POST['pet_friendly']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO cafes (name, rating, category, city, province, lat, lng, image, notes, has_wifi, has_sockets, has_parking, pet_friendly) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdssssdssiiii", $name, $rating, $category, $city, $province, $lat, $lng, $image, $notes, $has_wifi, $has_sockets, $has_parking, $pet_friendly);

    if ($stmt->execute()) {
        $message = "<p style='color: green;'>✅ New cafe added successfully! <a href='index.php'>View on Home</a></p>";
    } else {
        $message = "<p style='color: red;'>❌ Error: " . $stmt->error . "</p>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Add New Cafe</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .form-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h2 { margin-bottom: 20px; color: #c47c3e; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #4a3520; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        .checkbox-group { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        .checkbox-group label { display: inline-flex; align-items: center; gap: 5px; font-weight: normal; }
        .submit-btn { background: #c47c3e; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; width: 100%; }
        .submit-btn:hover { background: #9b5e2c; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #c47c3e; text-decoration: none; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>☕ Add New Cafe</h2>
        <?php echo $message; ?>
        
        <form action="add_cafe.php" method="POST">
            <div class="form-group">
                <label>Cafe Name *</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Rating (0-5)</label>
                <input type="number" step="0.1" name="rating" value="4.0">
            </div>
            
            <div class="form-group">
                <label>Category</label>
                <select name="category">
                    <option value="CAFE">Cafe</option>
                    <option value="COFFEE SHOP">Coffee Shop</option>
                    <option value="SPECIALTY">Specialty Coffee</option>
                    <option value="THIRD WAVE">Third Wave</option>
                    <option value="ARTISAN">Artisan</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" placeholder="e.g., Makati">
            </div>
            
            <div class="form-group">
                <label>Province</label>
                <input type="text" name="province" placeholder="e.g., Metro Manila">
            </div>
            
            <div class="form-group">
                <label>Latitude</label>
                <input type="text" name="lat" placeholder="e.g., 14.5995">
            </div>
            
            <div class="form-group">
                <label>Longitude</label>
                <input type="text" name="lng" placeholder="e.g., 120.9842">
            </div>
            
            <div class="form-group">
                <label>Image URL</label>
                <input type="text" name="image" placeholder="https://...">
            </div>
            
            <div class="form-group">
                <label>Amenities</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="has_wifi"> WiFi</label>
                    <label><input type="checkbox" name="has_sockets"> Outlets</label>
                    <label><input type="checkbox" name="has_parking"> Parking</label>
                    <label><input type="checkbox" name="pet_friendly"> Pet Friendly</label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" rows="3" placeholder="Describe the cafe..."></textarea>
            </div>
            
            <button type="submit" class="submit-btn">Save Cafe</button>
            <a href="index.php" class="back-link">← Back to Map</a>
        </form>
    </div>
</body>
</html>