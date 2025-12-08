<?php ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Visitor Check-In</title>

<link rel="stylesheet" href="css/styles.css">
</head>
<body>
  <div class="header-wrapper">
    <a href="login.php" class="h1-link">
      Visitor Form 
    </a>
  </div>
  <main class="container"> 
  
      <div class="card left-side">
        <form id="checkInForm">
          <label>
            First Name: <span style="color: red;">*</span>
            <input type="text" name="firstname" id="firstname" required>
          </label>
        <br><br>

          <label>
            Last Name: <span style="color: red;">*</span>
            <input type="text" name="lastname" id="lastname" required>
          </label>
        <br><br>
          
          <label>
            Type: <span style="color: red;">*</span>
            <select name="userType" id="userTypeSelect" required>
              <option value="">-- Select Type --</option>
              <option value="student">Student</option>
              <option value="visitor">Visitor</option>
            </select>
          </label>
          <br><br>

          <div id="srCodeField" style="display: none;">
            <label>
              Sr-Code: <span style="color: red;">*</span>
              <input type="text" name="srcode" id="srcode" pattern="[0-9]{2}-[0-9]{5}" title="Please enter SR-Code in format: 10-10001" placeholder="e.g., 10-10001">
            </label>
            <br><br>
          </div>

          <label>
            Contact (phone): <span style="color: red;">*</span>
            <input type="text" name="contact" id="contact" required pattern="[0-9]+" title="Please enter numbers only" placeholder="e.g., 09123456789">
          </label>
          <br><br>

          <label>
            Purpose: <span style="color: red;">*</span>
            <textarea name="purpose" id="purpose" rows="3" required></textarea>
          </label>
          <br><br>

          <button type="submit">Check In</button>
        </form>
      </div>

      <div class="card right-side">
          
            <h2 style="color: #d32f2f; font-size: 20px; margin-bottom: 15px; font-weight: 600;">Visitor Information</h2>
            <p style="color: #555; line-height: 1.6; font-size: 14px; margin-bottom: 20px;">
            <strong>Welcome to Batangas State University</strong>
            <br><br>
            Please take a moment to complete the visitor form before entering the premises.
            Your information helps us maintain campus safety, proper monitoring, and smooth visitor coordination.
            Thank you for your cooperation and enjoy your visit!
            </p>

          <hr>

        <div style="text-align: center; margin-bottom: 30px;">
          <img src="img/ahu.jpg" alt="Batangas State University Logo" style="max-width: 70px; height: auto; margin-right: 15px; display: inline-block; vertical-align: middle;">
          <img src="img/Batangas_State_Logo.png" alt="Batangas State Logo" style="max-width: 100px; height: auto; display: inline-block; vertical-align: middle;">
        </div>
        
        <h2 style="text-align: center; color: #d32f2f; font-size: 22px; margin: 25px 0 15px 0; font-weight: 600;">Our Location</h2>
        
        <div class="map-container" style="border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.12); margin-bottom: 25px;">
          <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3870.545318282881!2d121.1559532!3d14.0449448!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd6ed9735068d7%3A0x97fd25b226e150e7!2sBatangas%20State%20University%20Jose%20P.%20Laurel%20Polytechnic%20College!5e0!3m2!1sen!2sph!4v1763555452933!5m2!1sen!2sph" 
            width="100%" 
            height="280" 
            style="border: none; display: block;" 
            allowfullscreen="" 
            loading="lazy">
          </iframe>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
          <a href="https://batstateu.edu.ph/" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #d32f2f, #b71c1c); color: white; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px; transition: all 0.3s ease; box-shadow: 0 2px 6px rgba(211, 47, 47, 0.25);" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(211, 47, 47, 0.35)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(211, 47, 47, 0.25)';">Learn More About BSU</a>
        </div>
          </div>
      </div>
      
  </main>

  <!-- Receipt Modal -->
<div id="receiptModal" class="modal">
    <div class="modal-content">
        <h2>Check-in Successful!</h2>
        <div style="text-align: center; margin: 20px 0; padding: 15px;">
            <p style="font-size: 18px;">Thank you for checking in!</p>
        </div>
        <p style="color: #d32f2f; font-weight: 600;">Enjoy your visit to Batangas State University!</p>
        <button class="close-btn" onclick="closeModal()">Close</button>
    </div>
</div>
  <script src="js/script.js"></script>
</body>
</html>