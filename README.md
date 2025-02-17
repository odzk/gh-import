# Groundhogg Importer & Exporter

**Contributors:** Odysseus Ambut  
**Tags:** Groundhogg, Import, Export, Database, WordPress  
**Requires at least:** 5.0  
**Tested up to:** 6.3  
**Requires PHP:** 7.4+  
**Stable tag:** 1.6  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

A WordPress plugin for importing and exporting Groundhogg database tables, allowing table prefix replacements, selective import options, and overwrite capabilities.

---

## 🛠 Features

- **Export Groundhogg tables** to an SQL backup file.
- **Import SQL backup files** with prefix replacement.
- **Four import options:**  
  - 🗑 **Drop**: Deletes tables and restores them from backup.  
  - ⏭ **Skip**: Avoids import if tables exist.  
  - ❌ **Cancel**: Stops import if tables exist.  
  - 🔄 **Overwrite**: Updates existing data without deleting tables.  
- **No need for `mysqldump`** – Works on all hosting environments.
- **Logs errors and success messages** for troubleshooting.

---

## 🚀 Installation

### **Method 1: Install via WordPress**
1. Download the plugin ZIP file.
2. Go to **WordPress Admin > Plugins > Add New**.
3. Click **Upload Plugin** and select the ZIP file.
4. Click **Install Now**, then **Activate**.

### **Method 2: Manual Upload**
1. Extract the ZIP file.
2. Upload the folder to `/wp-content/plugins/`.
3. Activate the plugin in **WordPress Admin > Plugins**.

---

## 🎯 How to Use

### **Export Groundhogg Data**
1. Go to **WordPress Admin > GH Import / Export**.
2. Click the **Export Groundhogg Data** button.
3. A `.sql` file will be generated and available for download.

### **Import Groundhogg Data**
1. Go to **WordPress Admin > GH Import / Export**.
2. Upload an `.sql` backup file.
3. Choose the **source prefix** (e.g., `wp_`).
4. Choose the **target prefix** (e.g., your current WordPress prefix).
5. Select an **import option**:
   - **Drop**: Delete and restore tables.
   - **Skip**: Ignore import if tables exist.
   - **Cancel**: Stop import if tables exist.
   - **Overwrite**: Keep tables and update existing records.
6. Click **Import Backup**.

---

## 🛠 Troubleshooting & Debugging

### **🔴 Import Fails**
Check the log file in:

wp-content/uploads/gh_import.log


### **🔴 Error: Tables Already Exist**
- Use **Drop** to delete tables before importing.
- Use **Overwrite** to keep tables and update existing records.

### **🔴 No Export File Generated**
- Ensure the plugin has write permissions in `/wp-content/uploads/`.
- Check if your hosting provider blocks file creation.

---

## 📜 Changelog

### **1.6**
- Added **Overwrite Data** option without deleting tables.
- Improved SQL execution logging.

### **1.5**
- Added **Drop, Skip, and Cancel** import options.
- Improved error handling and debugging.

### **1.4**
- Converted **MySQL export to PHP-based** (no need for `mysqldump`).
- Improved performance and security.

### **1.3**
- Display **Groundhogg tables and their file sizes** in the admin panel.

---

## 🔗 License
This plugin is licensed under the **GPL v2 or later**. See [GNU License](https://www.gnu.org/licenses/gpl-2.0.html).
