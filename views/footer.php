</main>

<!-- Clear any floating elements and ensure footer is below all content -->
<div style="clear: both; width: 100%;"></div>

<footer style="
  background-color: #f8f9fa;
  padding: 40px 0;
  margin-top: 60px;
  border-top: 1px solid #e9ecef;
  width: 100%;
  clear: both;
  position: relative;
  z-index: 1;
">
  <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
    <div style="
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
      gap: 30px; 
      margin-bottom: 30px;
    ">
      
      <!-- Library Info -->
      <div>
        <h3 style="color: #333; margin-bottom: 15px; font-size: 18px;">ARK Library</h3>
        <p style="color: #666; line-height: 1.6; margin-bottom: 10px;">
          Kaban ng Hiyas Congressional Library - Your gateway to knowledge and academic resources.
        </p>
        <p style="color: #666; line-height: 1.6;">
          <i class="fas fa-map-marker-alt" style="margin-right: 8px;"></i>
          Mandaluyong City, Philippines
        </p>
      </div>
      
      <!-- Quick Links -->
      <div>
        <h3 style="color: #333; margin-bottom: 15px; font-size: 18px;">Quick Links</h3>
        <ul style="list-style: none; padding: 0; margin: 0;">
          <li style="margin-bottom: 8px;">
            <a href="/library-app/index.php" style="color: #666; text-decoration: none; transition: color 0.3s;">
              <i class="fas fa-home" style="margin-right: 8px; width: 16px;"></i>Home
            </a>
          </li>
          <li style="margin-bottom: 8px;">
            <a href="/library-app/views/events.php" style="color: #666; text-decoration: none; transition: color 0.3s;">
              <i class="fas fa-calendar" style="margin-right: 8px; width: 16px;"></i>Events
            </a>
          </li>
          <li style="margin-bottom: 8px;">
            <a href="/library-app/views/about.php" style="color: #666; text-decoration: none; transition: color 0.3s;">
              <i class="fas fa-info-circle" style="margin-right: 8px; width: 16px;"></i>About
            </a>
          </li>
          <li style="margin-bottom: 8px;">
            <a href="/library-app/views/contact.php" style="color: #666; text-decoration: none; transition: color 0.3s;">
              <i class="fas fa-envelope" style="margin-right: 8px; width: 16px;"></i>Contact
            </a>
          </li>
        </ul>
      </div>
      
      <!-- Resources -->
      <div>
        <h3 style="color: #333; margin-bottom: 15px; font-size: 18px;">Resources</h3>
        <ul style="list-style: none; padding: 0; margin: 0;">
          <li style="margin-bottom: 8px;">
            <a href="/library-app/views/favorites.php" style="color: #666; text-decoration: none; transition: color 0.3s;">
              <i class="fas fa-heart" style="margin-right: 8px; width: 16px;"></i>Bookmarks
            </a>
          </li>
          <li style="margin-bottom: 8px;">
            <a href="/library-app/ask.php" style="color: #666; text-decoration: none; transition: color 0.3s;">
              <i class="fas fa-question-circle" style="margin-right: 8px; width: 16px;"></i>Ask AI
            </a>
          </li>
          <li style="margin-bottom: 8px;">
            <a href="#" style="color: #666; text-decoration: none; transition: color 0.3s;">
              <i class="fas fa-book" style="margin-right: 8px; width: 16px;"></i>Digital Library
            </a>
          </li>
          <li style="margin-bottom: 8px;">
            <a href="#" style="color: #666; text-decoration: none; transition: color 0.3s;">
              <i class="fas fa-graduation-cap" style="margin-right: 8px; width: 16px;"></i>Study Guides
            </a>
          </li>
        </ul>
      </div>
      
      <!-- Contact Info -->
      <div>
        <h3 style="color: #333; margin-bottom: 15px; font-size: 18px;">Contact Us</h3>
        <div style="color: #666; line-height: 1.6;">
          <p style="margin-bottom: 10px;">
            <i class="fas fa-phone" style="margin-right: 8px; width: 16px;"></i>
            +63 (2) 8532-4681
          </p>
          <p style="margin-bottom: 10px;">
            <i class="fas fa-envelope" style="margin-right: 8px; width: 16px;"></i>
            info@arklibrary.gov.ph
          </p>
          <p style="margin-bottom: 10px;">
            <i class="fas fa-clock" style="margin-right: 8px; width: 16px;"></i>
            Mon-Fri: 8:00 AM - 6:00 PM
          </p>
        </div>
      </div>
    </div>
    
    <!-- Copyright -->
    <div style="
      border-top: 1px solid #e9ecef;
      padding-top: 20px;
      text-align: center;
      color: #666;
      font-size: 14px;
    ">
      <p style="margin: 0;">
        &copy; <?= date('Y') ?> ARK Library - Kaban ng Hiyas Congressional Library. All rights reserved.
      </p>
    </div>
  </div>
</footer>

<style>
/* Footer specific styles to override any conflicting CSS */
footer {
  display: block !important;
  width: 100% !important;
  float: none !important;
  position: relative !important;
  clear: both !important;
}

footer * {
  box-sizing: border-box;
}

/* Footer link hover effects */
footer a:hover {
  color: #007bff !important;
}

/* Responsive footer */
@media (max-width: 768px) {
  footer div[style*="grid-template-columns"] {
    grid-template-columns: 1fr !important;
    gap: 20px !important;
  }
}

/* Ensure footer doesn't interfere with flex layouts */
body {
  display: block !important;
}

/* Fix for any flex container that might affect footer */
.flex, .md\\:flex-row {
  margin-bottom: 0 !important;
}
</style>

</body>
</html>
