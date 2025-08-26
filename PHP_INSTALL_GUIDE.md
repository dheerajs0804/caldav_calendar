# 🚀 Simple PHP Installation Guide

## 🎯 **Choose Your Installation Method:**

### **Method 1: XAMPP (Easiest - No Admin Rights Required)**

1. **Download XAMPP**: [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. **Install XAMPP** (choose default options)
3. **Start Apache** from XAMPP Control Panel
4. **PHP will be available** at the installed location

**✅ Pros**: No PATH changes, includes Apache, MySQL
**❌ Cons**: Larger download, includes extra software

---

### **Method 2: Project-Local PHP (No Admin Rights Required)**

1. **Create `php` folder** in your project directory
2. **Download PHP** from [windows.php.net](https://windows.php.net/download/)
3. **Extract to** `your-project/php/` folder
4. **Use relative paths** in your scripts

**✅ Pros**: Portable, no system changes
**❌ Cons**: Need to specify full path to PHP

---

### **Method 3: Automatic Script (Requires Admin Rights)**

1. **Right-click** `install_php.ps1`
2. **Select "Run as Administrator"**
3. **Follow prompts** - automatic installation

**✅ Pros**: Fully automatic, sets up PATH
**❌ Cons**: Requires admin rights

---

## 🔧 **Quick Test After Installation:**

### **If using XAMPP:**
```bash
# PHP will be in XAMPP directory
C:\xampp\php\php.exe --version
```

### **If using project-local PHP:**
```bash
# Use relative path
.\php\php.exe --version
```

### **If using automatic script:**
```bash
# PHP will be in PATH
php --version
```

---

## 🚀 **Start Your Backend After Installation:**

### **With XAMPP:**
```bash
cd backend
C:\xampp\php\php.exe start_server.php
```

### **With project-local PHP:**
```bash
cd backend
..\php\php.exe start_server.php
```

### **With automatic installation:**
```bash
cd backend
php start_server.php
```

---

## 🆘 **Need Help?**

- **XAMPP Issues**: Check XAMPP Control Panel
- **Path Issues**: Use full path to PHP executable
- **Permission Issues**: Try project-local installation
- **Port Conflicts**: Change port in `start_server.php`

---

**Choose the method that works best for your situation! 🎯**

