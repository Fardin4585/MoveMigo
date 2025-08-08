# MoveMigo - Tenant & Homeowner Connection Platform

MoveMigo is a web application that connects tenants and homeowners, making the rental process seamless and efficient.

## Features

- **User Authentication**: Secure login system for tenants and homeowners
- **Role-based Access**: Different dashboards for tenants and homeowners
- **Session Management**: Secure session handling with token-based authentication
- **Database Integration**: MySQL database with proper relationships

## Setup Instructions

### 1. Database Setup

1. **Start your MySQL server** (XAMPP, WAMP, or standalone MySQL)
2. **Open phpMyAdmin** or MySQL command line
3. **Run the database setup script**:
   ```sql
   -- Copy and paste the contents of database_setup.sql
   -- Or run the file directly in phpMyAdmin
   ```

### 2. Database Configuration

1. **Edit the database configuration** in `config/database.php`:
   ```php
   private $host = 'localhost';
   private $db_name = 'movemigo_db';
   private $username = 'root';  // Change to your MySQL username
   private $password = '';      // Change to your MySQL password
   ```

### 3. File Structure

Ensure your project has the following structure:
```
movemigo/
├── config/
│   └── database.php
├── includes/
│   └── Auth.php
├── signin.php
├── signin.js
├── signin.css
├── signup-tenant.php
├── signup-homeowner.php
├── logout.php
├── tenant-dashboard.php
├── homeowner-dashboard.php
├── database_setup.sql
├── test-signup.php
└── README.md
```

### 4. Testing the Application

1. **Start your web server** (Apache in XAMPP/WAMP)
2. **Navigate to** `http://localhost/movemigo/signin.php`
3. **Test with sample accounts**:
   - **Tenant**: `tenant@example.com` / `password`
   - **Homeowner**: `homeowner@example.com` / `password`
4. **Or create new accounts** using the signup pages

## Database Schema

### Tables Created:
- **users**: Main user accounts (tenants and homeowners)
- **tenant_profiles**: Additional tenant information
- **homeowner_profiles**: Additional homeowner information
- **properties**: Property listings
- **property_images**: Property images
- **user_sessions**: Session management

### Key Features:
- **Password Hashing**: Secure password storage using PHP's `password_hash()`
- **Session Management**: Token-based sessions with expiration
- **Input Sanitization**: Protection against SQL injection and XSS
- **Role-based Access Control**: Different user types with appropriate permissions

## Security Features

- **Password Hashing**: Uses PHP's built-in `password_hash()` function
- **Prepared Statements**: Prevents SQL injection attacks
- **Input Sanitization**: Protects against XSS attacks
- **Session Security**: Token-based sessions with expiration
- **Role Validation**: Ensures users can only access appropriate areas

## Next Steps

To complete the application, you'll need to create:

1. **Property Management**: Add, edit, and delete properties
2. **Search Functionality**: Property search for tenants
3. **Application System**: Tenant applications for properties
4. **Profile Management**: User profile editing
5. **Image Upload**: Property image management

## Troubleshooting

### Common Issues:

1. **Database Connection Error**:
   - Check if MySQL is running
   - Verify database credentials in `config/database.php`
   - Ensure the database `movemigo_db` exists

2. **Session Issues**:
   - Check if PHP sessions are enabled
   - Verify file permissions

3. **File Not Found Errors**:
   - Ensure all files are in the correct directory structure
   - Check file paths in include statements

## Support

For issues or questions, please check:
1. PHP error logs
2. MySQL error logs
3. Browser developer console for JavaScript errors

---

**Note**: This is a basic implementation. For production use, consider adding:
- HTTPS enforcement
- Rate limiting
- Email verification
- Password reset functionality
- More robust error handling
- Input validation
- CSRF protection 