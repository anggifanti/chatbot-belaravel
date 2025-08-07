# 🌸💄 Beauty AI Assistant - Backend API

> **API Backend untuk Asisten Kecantikan AI Terpintar**  
> *Powered by Laravel & Google Gemini AI for Beauty Consultation*

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![SQLite](https://img.shields.io/badge/SQLite-003B57?style=for-the-badge&logo=sqlite&logoColor=white)](https://www.sqlite.org/)
[![JWT](https://img.shields.io/badge/JWT-000000?style=for-the-badge&logo=JSON%20web%20tokens&logoColor=white)](https://jwt.io/)
[![Google AI](https://img.shields.io/badge/Google_AI-4285F4?style=for-the-badge&logo=google&logoColor=white)](https://ai.google/)

---

## 🌟 **Tentang Beauty AI Backend**

**Beauty AI Backend** adalah API server yang powerful dan elegant, dibangun dengan Laravel untuk mendukung aplikasi **Beauty AI Assistant**. Backend ini mengelola konsultasi kecantikan dengan AI, manajemen pengguna, sistem premium, dan analytics platform kecantikan yang komprehensif.

### ✨ **Fitur API Unggulan**

🤖 **Google Gemini AI Integration** - Konsultasi kecantikan dengan AI terpintar  
🔐 **JWT Authentication** - Sistem login aman dengan token  
👑 **Multi-level User System** - Guest, Premium, Admin access  
📊 **Beauty Analytics** - Statistik konsultasi & user engagement  
💬 **Chat Management** - History & conversation tracking  
📁 **File Upload System** - Avatar & media management  
🛡️ **Security First** - CORS, validation, & data protection  
📱 **RESTful API** - Clean, documented endpoints  
🎯 **Rate Limiting** - Smart quota management untuk guest users  

---

## 🚀 **Quick Start Guide**

### 📋 **Prerequisites**

Pastikan system Anda sudah memiliki:
- **PHP** 8.2 atau lebih tinggi - [Download di sini](https://php.net/downloads)
- **Composer** - [Install Composer](https://getcomposer.org/download/)
- **SQLite** (sudah termasuk dengan PHP)
- **Git** - [Download Git](https://git-scm.com/)
- **Google Gemini API Key** - [Dapatkan di sini](https://makersuite.google.com/app/apikey)

### 🛠️ **Installation & Setup**

#### **Step 1: Clone & Navigate**
```bash
# Clone repository
git clone <repository-url>
cd fullstack-chatbot/backend-laravel

# Atau jika sudah ada project:
cd path/to/your/backend-laravel
```

#### **Step 2: Install PHP Dependencies**
```bash
# Install semua package Laravel
composer install

# Jika ada error, jalankan:
composer update
```

#### **Step 3: Environment Configuration**
```bash
# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

#### **Step 4: Configure .env File**
Edit file `.env` dengan konfigurasi berikut:
```env
# Basic App Configuration
APP_NAME="Beauty AI Assistant"
APP_ENV=local
APP_KEY=base64:YOUR_GENERATED_KEY
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration (SQLite)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# CORS Configuration
CORS_ALLOWED_ORIGINS="http://localhost:5173,http://127.0.0.1:5173"

# Google Gemini AI Configuration
GOOGLE_API_KEY=your_google_gemini_api_key_here

# JWT Configuration (akan di-generate otomatis)
JWT_SECRET=your_jwt_secret
```

#### **Step 5: Database Setup**
```bash
# Buat file database SQLite
touch database/database.sqlite

# Jalankan migrations untuk membuat tables
php artisan migrate

# (Optional) Jalankan seeder untuk data dummy
php artisan db:seed
```

#### **Step 6: Storage Configuration**
```bash
# Buat symbolic link untuk storage
php artisan storage:link

# Set permissions (jika di Linux/Mac)
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

#### **Step 7: Start Development Server**
```bash
# Jalankan Laravel development server
php artisan serve

# Server akan running di: http://localhost:8000
```

🎉 **Congratulations!** API Backend sudah siap digunakan!

---

## 🔧 **Development Commands**

### **Laravel Artisan Commands**
```bash
# 🚀 Start development server
php artisan serve

# 🗄️ Database operations
php artisan migrate              # Run migrations
php artisan migrate:fresh        # Fresh migration (drop all tables)
php artisan migrate:refresh      # Refresh migrations
php artisan db:seed             # Run seeders

# 🧹 Cache management
php artisan cache:clear         # Clear application cache
php artisan config:clear        # Clear config cache
php artisan route:clear         # Clear route cache
php artisan view:clear          # Clear view cache

# 🔧 Development helpers
php artisan tinker              # Interactive shell
php artisan route:list          # List all routes
php artisan make:model Model    # Create new model
php artisan make:controller Controller  # Create controller

# 🧪 Testing
php artisan test                # Run PHPUnit tests
./vendor/bin/phpunit           # Alternative test command
```

### **Composer Commands**
```bash
# 📦 Package management
composer install               # Install dependencies
composer update                # Update dependencies
composer dump-autoload         # Refresh autoloader

# 🔍 Development tools
composer require package-name   # Add new package
composer remove package-name    # Remove package
```

---

## 📡 **API Documentation**

### **🔐 Authentication Endpoints**
```
POST   /api/register           # User registration
POST   /api/login             # User login
POST   /api/logout            # User logout
GET    /api/user              # Get current user info
PUT    /api/user              # Update user profile
```

### **💬 Chat & AI Endpoints**
```
GET    /api/conversations      # Get user conversations
POST   /api/conversations      # Create new conversation
GET    /api/conversations/{id} # Get conversation messages
POST   /api/chat              # Send message to AI
DELETE /api/conversations/{id} # Delete conversation
```

### **👑 Admin Endpoints**
```
GET    /api/admin/users        # Get all users
GET    /api/admin/conversations # Get all conversations
GET    /api/admin/stats        # Get platform statistics
PUT    /api/admin/users/{id}   # Update user (admin only)
DELETE /api/admin/users/{id}   # Delete user (admin only)
```

### **📊 Analytics Endpoints**
```
GET    /api/stats              # Get user statistics
GET    /api/admin/analytics    # Get admin analytics
```

### **📁 File Upload Endpoints**
```
POST   /api/upload/avatar      # Upload user avatar
DELETE /api/upload/avatar      # Delete user avatar
```

---

## 🏗️ **Project Structure**

```
backend-laravel/
├── 📁 app/
│   ├── 📁 Http/
│   │   ├── 📁 Controllers/
│   │   │   ├── AuthController.php       # 🔐 Authentication logic
│   │   │   ├── ChatController.php       # 💬 AI chat functionality
│   │   │   ├── AdminController.php      # 👑 Admin operations
│   │   │   ├── UserController.php       # 👤 User management
│   │   │   └── StatsController.php      # 📊 Analytics & stats
│   │   ├── 📁 Middleware/
│   │   │   ├── Authenticate.php         # JWT authentication
│   │   │   ├── AdminMiddleware.php      # Admin access control
│   │   │   └── CorsMiddleware.php       # CORS handling
│   │   └── 📁 Requests/                 # Form validation
│   ├── 📁 Models/
│   │   ├── User.php                     # 👤 User model
│   │   ├── Conversation.php             # 💬 Chat conversations
│   │   └── Message.php                  # 📝 Chat messages
│   └── 📁 Services/
│       ├── GeminiService.php            # 🤖 Google AI integration
│       ├── ChatService.php              # 💬 Chat logic
│       └── StatsService.php             # 📊 Analytics service
├── 📁 database/
│   ├── 📁 migrations/                   # Database schema
│   ├── 📁 seeders/                      # Sample data
│   └── database.sqlite                  # SQLite database
├── 📁 routes/
│   ├── api.php                          # API routes
│   └── web.php                          # Web routes
├── 📁 config/                           # Configuration files
├── 📁 storage/                          # File storage
└── 📄 .env                              # Environment variables
```

---

## 🤖 **Google Gemini AI Integration**

### **AI Beauty Consultant Features**
- 💄 **Makeup Consultation** - Personal makeup advice
- 🧴 **Skincare Recommendations** - Custom skincare routine
- 💅 **Beauty Treatments** - Professional treatment suggestions
- 🌟 **Product Reviews** - Beauty product analysis
- 📚 **Beauty Education** - Tips & tutorials

### **AI Configuration**
```php
// config/services.php
'gemini' => [
    'api_key' => env('GOOGLE_API_KEY'),
    'model' => 'gemini-pro',
    'max_tokens' => 1000,
]
```

---

## 🛡️ **Security Features**

### **Authentication & Authorization**
- 🔐 **JWT Tokens** - Secure stateless authentication
- 🛡️ **Middleware Protection** - Route-level security
- 👑 **Role-based Access** - Admin/User/Guest permissions
- 🔒 **Password Hashing** - Bcrypt password security

### **Data Protection**
- ✅ **Input Validation** - Request validation rules
- 🚫 **XSS Protection** - Cross-site scripting prevention
- 🔒 **CSRF Protection** - Cross-site request forgery guard
- 🌐 **CORS Configuration** - Cross-origin resource sharing

### **Rate Limiting**
- ⏱️ **API Rate Limits** - Prevent abuse
- 👥 **Guest Limitations** - 3-prompt limit for unregistered
- 🚀 **Premium Access** - Unlimited for registered users

---

## 🗄️ **Database Schema**

### **Users Table**
```sql
- id (Primary Key)
- name (String)
- email (Unique String)
- password (Hashed String)
- avatar (Nullable String)
- is_admin (Boolean, default: false)
- is_premium (Boolean, default: false)
- created_at, updated_at (Timestamps)
```

### **Conversations Table**
```sql
- id (Primary Key)
- user_id (Foreign Key, nullable for guests)
- title (String)
- created_at, updated_at (Timestamps)
```

### **Messages Table**
```sql
- id (Primary Key)
- conversation_id (Foreign Key)
- content (Text)
- is_from_ai (Boolean)
- created_at, updated_at (Timestamps)
```

---

## 🌍 **Environment Variables**

### **Required Configuration**
```env
# Core Laravel Settings
APP_NAME="Beauty AI Assistant"
APP_ENV=local
APP_KEY=base64:generated_key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Google AI
GOOGLE_API_KEY=your_gemini_api_key

# JWT Authentication
JWT_SECRET=your_jwt_secret
JWT_TTL=60
JWT_REFRESH_TTL=20160

# CORS
CORS_ALLOWED_ORIGINS="http://localhost:5173"

# File Storage
FILESYSTEM_DISK=local
```

---

## 🧪 **Testing**

### **Run Tests**
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=AuthTest

# Run tests with coverage
php artisan test --coverage
```

### **Test Categories**
- 🔐 **Authentication Tests** - Login/register functionality
- 💬 **Chat API Tests** - AI conversation endpoints
- 👑 **Admin Tests** - Administrative functions
- 📊 **Analytics Tests** - Statistics & reporting
- 🛡️ **Security Tests** - Middleware & validation

---

## 🚀 **Deployment**

### **Production Setup**
```bash
# Optimize for production
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set production environment
APP_ENV=production
APP_DEBUG=false
```

### **Server Requirements**
- **PHP 8.2+** with extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- **Nginx/Apache** web server
- **SQLite** or **MySQL/PostgreSQL** for production
- **SSL Certificate** for HTTPS

---

## 🤝 **Contributing**

Kami menyambut kontribusi untuk Beauty AI Backend! 

### **Development Workflow**
1. **Fork** repository ini
2. **Create** feature branch (`git checkout -b feature/beauty-feature`)
3. **Write** tests untuk fitur baru
4. **Commit** changes (`git commit -m 'Add beauty feature'`)
5. **Push** ke branch (`git push origin feature/beauty-feature`)
6. **Open** Pull Request

### **Code Standards**
- Follow **PSR-12** coding standards
- Write **comprehensive tests**
- Document **API endpoints**
- Use **meaningful commit messages**

---

## 📝 **License**

Project ini dibuat untuk tujuan edukasi dan portfolio development.

---

## 💖 **Credits & Acknowledgments**

Dibangun dengan teknologi terbaik:
- **🌐 Laravel Framework** - The PHP Framework for Web Artisans
- **🤖 Google Gemini AI** - Advanced AI for beauty consultation
- **🔐 JWT Auth** - Secure authentication system
- **🗄️ SQLite** - Lightweight database solution
- **🎨 Beautiful Architecture** - Clean, maintainable code

---

**✨ Happy Coding & Build Beautiful APIs! 💄🌸**

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
