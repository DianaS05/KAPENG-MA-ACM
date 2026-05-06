<?php
session_start();
include 'db_conn.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>VibesCheckCAFE | PH Coffee Discovery</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,900;1,900&family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>  
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBdH32lfkAKDgP_P7JNVMRCfjy02hTWNKg&libraries=places&callback=initVibes&loading=async"></script>
</head>

<body>

    <header>
        <div class="logo">VibesCheck<span>CAFE</span></div>
        <nav class="nav-links">
            <a href="index.php">Explore</a>
            <a id="favoritesLink" style="cursor:pointer;">Favorites</a>
            <button class="nearby-btn" id="nearbyBtn"><i class="fas fa-location-dot"></i> Nearby</button>
            <div id="googleSignInContainer"></div>
        </nav>
    </header>

    <main>
        <section class="hero-section">
            <h1>A spot for every <span>vibe.</span></h1>
            <div class="search-container">
                <div class="search-input-group">
                    <i class="fas fa-map-marker-alt"></i>
                    <input type="text" id="searchInput" placeholder="Search any city or cafe in the PH...">
                </div>
            </div>
            <div class="filter-buttons">
                <button class="filter-chip active" data-filter="all">All</button>
                <button class="filter-chip" data-filter="wifi"><i class="fas fa-wifi"></i> WiFi</button>
                <button class="filter-chip" data-filter="sockets"><i class="fas fa-plug"></i> Outlets</button>
                <button class="filter-chip" data-filter="parking"><i class="fas fa-car"></i> Parking</button>
                <button class="filter-chip" data-filter="pet"><i class="fas fa-dog"></i> Pet Friendly</button>
            </div>
        </section>

        <div class="map-wrapper">
            <div id="map"></div>
        </div>

        <section class="cards-grid" id="cafeGrid">
            <?php
            $result = $conn->query("SELECT * FROM cafes ORDER BY rating DESC");
            if (!$result) {
                echo "<!-- Database Error: " . $conn->error . " -->";
            } else {
                $colors = ['c47c3e', '3E2723', 'D4A373', '8B5A2B', 'A0522D', '6F4E37', 'B8860B'];

                while ($row = $result->fetch_assoc()):
                    $searchMeta = htmlspecialchars($row['name'] . ' ' . ($row['city'] ?? '') . ' ' . ($row['province'] ?? ''));
                    $lat = floatval($row['lat']);
                    $lng = floatval($row['lng']);

                    if (!empty($row['image'])) {
                        $imageUrl = $row['image'];
                    } else {
                        $colorIndex = abs(crc32($row['name'])) % count($colors);
                        $placeholderColor = $colors[$colorIndex];
                        $cafeNameShort = substr($row['name'], 0, 20);
                        $imageUrl = "https://via.placeholder.com/400x300/{$placeholderColor}/FFFFFF?text=" . urlencode($cafeNameShort);
                    }
            ?>
                    <div class="vibe-card"
                        data-id="<?php echo $row['id']; ?>"
                        data-lat="<?php echo $lat; ?>"
                        data-lng="<?php echo $lng; ?>"
                        data-name="<?php echo strtolower($searchMeta); ?>"
                        data-wifi="<?php echo $row['has_wifi']; ?>"
                        data-sockets="<?php echo $row['has_sockets']; ?>"
                        data-parking="<?php echo $row['has_parking']; ?>"
                        data-pet="<?php echo $row['pet_friendly']; ?>">

                        <div class="category-badge"><?php echo strtoupper($row['category'] ?? 'CAFE'); ?></div>

                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($imageUrl); ?>"
                                alt="<?php echo htmlspecialchars($row['name']); ?>"
                                loading="lazy"
                                onerror="this.src='https://picsum.photos/id/225/400/300'; this.onerror=null;">
                        </div>

                        <div class="card-body">
                            <div class="card-header">
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                <span class="rating"><?php echo floatval($row['rating']); ?> ⭐</span>
                            </div>

                            <p class="location-tag">
                                <i class="fas fa-thumbtack"></i>
                                <?php echo htmlspecialchars($row['city'] ?? 'Metro Manila'); ?>, <?php echo htmlspecialchars($row['province'] ?? 'Philippines'); ?>
                            </p>

                            <div class="amenities-row">
                                <?php if (!empty($row['has_wifi'])): ?><i class="fas fa-wifi" title="WiFi"></i><?php endif; ?>
                                <?php if (!empty($row['has_sockets'])): ?><i class="fas fa-plug" title="Outlets"></i><?php endif; ?>
                                <?php if (!empty($row['has_parking'])): ?><i class="fas fa-car" title="Parking"></i><?php endif; ?>
                                <?php if (!empty($row['pet_friendly'])): ?><i class="fas fa-dog" title="Pet Friendly"></i><?php endif; ?>
                            </div>

                            <p class="notes"><?php echo htmlspecialchars($row['notes'] ?? 'Cozy ambiance & great brews.'); ?></p>

                            <div class="card-footer">
                                <button class="btn-primary locate-btn" data-lat="<?php echo $lat; ?>" data-lng="<?php echo $lng; ?>">
                                    <i class="fas fa-location-arrow"></i> Locate
                                </button>
                                <button class="btn-wishlist favorite-btn" data-cafe-id="<?php echo $row['id']; ?>">
                                    <i class="far fa-heart"></i>
                                </button>
                                <button class="btn-primary review-btn" data-cafe-id="<?php echo $row['id']; ?>" data-cafe-name="<?php echo htmlspecialchars($row['name']); ?>">
                                    <i class="fas fa-comment"></i> Review
                                </button>
                            </div>
                        </div>
                    </div>
            <?php
                endwhile;
            }
            ?>
        </section>
    </main>

    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3 id="modalCafeName" style="margin-bottom: 1rem;">Write a Review</h3>
            <div id="existingReviews" style="max-height: 300px; overflow-y: auto;"></div>
            <form id="reviewForm">
                <input type="hidden" id="reviewCafeId" name="cafe_id">
                <div class="star-rating">
                    <input type="radio" name="rating" value="5" id="star5"><label for="star5">★</label>
                    <input type="radio" name="rating" value="4" id="star4"><label for="star4">★</label>
                    <input type="radio" name="rating" value="3" id="star3"><label for="star3">★</label>
                    <input type="radio" name="rating" value="2" id="star2"><label for="star2">★</label>
                    <input type="radio" name="rating" value="1" id="star1"><label for="star1">★</label>
                </div>
                <textarea name="comment" placeholder="Share your experience..." rows="3"></textarea>
                <button type="submit" class="btn-primary" style="width:100%;">Submit Review</button>
            </form>
        </div>
    </div>

    <footer>
        <span>☕ VibesCheckCAFE · Discover the perfect coffee nook across the Philippines</span>
    </footer>

    <div id="toastMsg" class="toast-msg"></div>

    <script src="script.js"></script>
</body>

</html>