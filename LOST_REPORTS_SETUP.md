# Lost Reports System Setup Guide

## Overview
The lost reports system allows users to:
1. Fill out a lost item form in `user_report.php`
2. Upload photos (saved to `Dashboard/Image/`)
3. View submitted reports in `Dashboard/my_report.php`

## Database Setup

### Step 1: Update Database Schema
Run this SQL to add the `lost_reports` table:

```sql
USE findit;

CREATE TABLE lost_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_id VARCHAR(20) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  category VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(255) NOT NULL,
  date_lost DATE NOT NULL,
  time_lost TIME,
  photo_path VARCHAR(500),
  contact_email VARCHAR(150) NOT NULL,
  contact_phone VARCHAR(30) NOT NULL,
  status ENUM('Pending', 'Verified', 'Claimed', 'Rejected') DEFAULT 'Pending',
  admin_notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_status (status),
  INDEX idx_report_id (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 2: Create Image Upload Directory
The system will auto-create this, but you can manually ensure it exists:

```powershell
New-Item -ItemType Directory -Force -Path "c:\xampp\htdocs\SIA.html\Dashboard\Image"
```

## How It Works

### User Flow
1. **User visits** `user_report.php`
2. **Fills form** with:
   - Item Name (required)
   - Category (required)
   - Description (required)
   - Location (required)
   - Date Lost (required)
   - Time Lost (optional)
   - Photo (optional)
   - Email (required)
   - Phone (required)

3. **Clicks Submit**
   - Form validates all required fields
   - Sends data to `submit_lost_report.php` via AJAX

4. **Backend Processing** (`submit_lost_report.php`):
   - Validates user is logged in
   - Validates all required fields
   - If photo uploaded:
     - Validates file type (JPG/PNG only)
     - Validates file size (max 10MB)
     - Saves to `Dashboard/Image/` with unique filename
     - Sets status to "Pending" (requires admin approval)
   - If no photo:
     - Sets status to "Verified" (auto-approved)
   - Generates report ID (LR-001, LR-002, etc.)
   - Inserts into `lost_reports` table

5. **Success Response**
   - Shows success message
   - Redirects to `Dashboard/my_report.php` after 2 seconds

### Display in My Reports
`Dashboard/my_report.php` dynamically fetches all reports for the logged-in user and displays:
- Photo (from `Dashboard/Image/` or placeholder)
- Report ID
- Type (always "Lost")
- Item Name
- Category
- Location
- Date Lost
- Status (Pending/Verified/Claimed)
- Edit/Delete actions

## Files Modified

### Created
- `submit_lost_report.php` - Backend handler for form submission

### Updated
- `user_report.php` - Added form action and photo input name
- `report_form.js` - Real AJAX submission instead of simulation
- `Dashboard/my_report.php` - Dynamic data from database
- `sql/schema.sql` - Added lost_reports table

## Testing

### 1. Run Database Migration
```powershell
# Open phpMyAdmin or MySQL command line
# Navigate to http://localhost/phpmyadmin
# Select 'findit' database
# Run the CREATE TABLE query above
```

### 2. Test Submission
1. Login as a regular user (not admin)
2. Navigate to `user_report.php`
3. Fill out the form completely
4. Optionally upload a photo
5. Click "Submit Report"
6. Should redirect to My Reports page

### 3. Verify Data
- Check `Dashboard/my_report.php` - your report should appear
- Check `Dashboard/Image/` folder - photo should be saved
- Check database table `lost_reports` - record should exist

## Photo Requirements
- **Formats**: JPG, PNG only
- **Max Size**: 10MB
- **Naming**: `lost_[timestamp]_[uniqueid].[ext]`
- **Storage**: `Dashboard/Image/`
- **Display Path**: `Image/[filename]` (relative to Dashboard/)

## Status Workflow
- **Pending**: Report with photo, waiting for admin approval
- **Verified**: Report approved (auto or by admin)
- **Claimed**: Item has been claimed by owner
- **Rejected**: Report rejected by admin

## Next Steps
To complete the system, you may want to:
1. Create `edit_report.php` for editing reports
2. Create `delete_report.php` for deleting reports
3. Add admin approval interface
4. Add email notifications when status changes
