# Monastery Healthcare System v2.0

A modern, redesigned healthcare and donation management system specifically built for monastery communities. This version features a complete architectural overhaul with improved performance, security, and user experience.

## ✨ Features

### 🏥 Healthcare Management
- **Role-based Access Control**: Admin, Doctor, Monk, Donor, and Helper roles
- **Monk Profile Management**: Comprehensive health profiles with medical history
- **Appointment Scheduling**: Advanced booking system with provider availability
- **Medical Records**: Digital health records with visit history and treatments
- **Healthcare Provider Management**: Doctor profiles and specializations

### 💝 Donation Management
- **Multi-channel Donations**: Bank transfer, cash, online payments
- **Campaign Management**: Targeted fundraising campaigns
- **Verification System**: Multi-step donation verification process
- **Transparency Dashboard**: Public donation tracking and reporting
- **Donor Management**: Comprehensive donor relationship management

### 📊 Analytics & Reporting
- **Real-time Dashboards**: Role-specific dashboard with relevant metrics
- **Financial Reports**: Comprehensive financial analytics
- **Healthcare Analytics**: Medical service usage and trends
- **Donation Analytics**: Fundraising performance and donor insights

### 🎨 Modern UI/UX
- **Minimalist Design**: Clean, professional interface
- **Fully Responsive**: Mobile-first design approach
- **Accessibility**: WCAG 2.1 compliant design
- **Fast Performance**: Optimized for speed and efficiency

## 🛠️ Technical Architecture

### Backend
- **PHP 8.x**: Modern PHP with strict typing
- **MySQL 8.x**: Robust database with JSON support
- **MVC Pattern**: Clean separation of concerns
- **RESTful API**: JSON API for frontend integration

### Frontend
- **Vanilla JavaScript**: No framework dependencies
- **CSS Grid/Flexbox**: Modern layout techniques
- **Chart.js**: Interactive data visualizations
- **Progressive Enhancement**: Works without JavaScript

### Security
- **CSRF Protection**: Cross-site request forgery prevention
- **XSS Prevention**: Output sanitization and escaping
- **SQL Injection Protection**: Prepared statements
- **Session Security**: Secure session management
- **Password Hashing**: Bcrypt with proper salting

## 🚀 Quick Start

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- Composer (optional, for dependencies)

### Installation

1. **Clone or copy the redesigned system**
   ```bash
   # Navigate to your web directory
   cd /path/to/web/directory
   
   # The redesign is in the 'redesign' folder
   ```

2. **Configure Environment**
   ```bash
   # Copy environment configuration
   cp .env.example .env
   
   # Edit .env with your database settings
   nano .env
   ```

3. **Setup Database**
   ```bash
   # Create database
   mysql -u root -p -e "CREATE DATABASE monastery_healthcare_v2;"
   
   # Import schema
   mysql -u root -p monastery_healthcare_v2 < config/database_schema_v2.sql
   mysql -u root -p monastery_healthcare_v2 < config/database_schema_v2_part2.sql
   ```

4. **Configure Web Server**
   
   **Apache (.htaccess)**
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ public/index.php [QSA,L]
   ```
   
   **Nginx**
   ```nginx
   location / {
       try_files $uri $uri/ /public/index.php?$query_string;
   }
   ```

5. **Set Permissions**
   ```bash
   chmod -R 755 storage/
   chmod -R 755 public/
   ```

### Default Login
- **Email**: admin@monastery.lk
- **Password**: admin123

## 📁 Project Structure

```
redesign/
├── app/                    # Core application files
│   ├── Application.php     # Main application bootstrap
│   ├── Config.php         # Configuration management
│   ├── Database.php       # Database abstraction
│   ├── Router.php         # Request routing
│   ├── SessionManager.php # Session handling
│   └── Logger.php         # Logging system
├── modules/               # Feature modules
│   ├── auth/             # Authentication module
│   ├── healthcare/       # Healthcare management
│   ├── donations/        # Donation management
│   └── reports/          # Reporting system
├── api/                  # API endpoints
│   └── v1/              # API version 1
├── public/               # Web root directory
│   ├── index.php        # Main entry point
│   └── assets/          # Static assets
├── templates/            # View templates
│   ├── layouts/         # Layout templates
│   ├── components/      # Reusable components
│   └── pages/           # Page templates
├── config/               # Configuration files
│   ├── database_schema_v2.sql
│   └── database.php
├── storage/              # File storage
│   ├── logs/            # Application logs
│   └── uploads/         # File uploads
└── assets/               # Frontend assets
    ├── css/             # Stylesheets
    ├── js/              # JavaScript
    └── images/          # Images
```

## 🔐 Security Features

- **Authentication**: Secure login with session management
- **Authorization**: Role-based access control (RBAC)
- **Data Protection**: Input validation and sanitization
- **Session Security**: Secure session configuration
- **CSRF Protection**: Token-based CSRF prevention
- **SQL Injection**: Prepared statement usage
- **XSS Prevention**: Output escaping and sanitization

## 🎯 Key Improvements from v1.0

### Architecture
- ✅ **Modular Design**: Clear separation of concerns
- ✅ **MVC Pattern**: Organized code structure
- ✅ **Database Optimization**: Improved schema and indexes
- ✅ **API-First**: RESTful API design

### User Experience
- ✅ **Modern UI**: Clean, minimalist interface
- ✅ **Responsive Design**: Mobile-optimized layout
- ✅ **Performance**: Faster page loads and interactions
- ✅ **Accessibility**: Screen reader and keyboard friendly

### Functionality
- ✅ **Advanced Reporting**: Comprehensive analytics
- ✅ **Better Workflows**: Streamlined processes
- ✅ **Enhanced Security**: Multiple security layers
- ✅ **Scalability**: Built for growth

## 📱 Browser Support

- ✅ Chrome 70+
- ✅ Firefox 65+
- ✅ Safari 12+
- ✅ Edge 79+
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## 🤝 Contributing

This system is designed for monastery use. For customization requests or issues:
1. Document the requirement clearly
2. Consider security implications
3. Test thoroughly before deployment
4. Maintain backup before changes

## 📄 License

This project is designed specifically for monastery healthcare management. Please respect the intended use case and maintain the spiritual and community-focused nature of the system.

## 🙏 Acknowledgments

- Built with love for monastery communities
- Designed with feedback from healthcare providers
- Inspired by modern healthcare management needs
- Created to serve the noble cause of community health

---

**Version**: 2.0.0  
**Last Updated**: February 2026  
**Minimum PHP**: 8.0  
**Minimum MySQL**: 8.0