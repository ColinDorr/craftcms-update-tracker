# 📦 Update Tracker Plugin for Craft CMS

**Update Tracker** is a powerful Craft CMS plugin designed to help you monitor updates and license statuses seamlessly.

---

## 🌟 Features
- Track updates for Craft CMS and installed plugins.
- Send email notifications for available updates.
- Configurable notification frequency (daily, weekly, monthly).
- Supports Craft CMS 3.7, 4.4, and 5.0.

---

## 📋 Requirements
- **Craft CMS**: ^3.7 || ^4.4 || ^5.0  
- **PHP**: ^7.2.5 || ^8.0  
- **Symfony YAML**: ^4.4 || ^5.4

---

## 🚀 Installation

### Via Composer
1. **Install the plugin**:
    ```bash
    composer require colin-dorr/craftcms-update-tracker
    ```
2. **Enable the plugin**:
    ```bash
    php craft plugin/install update-tracker
    ```

---

## ⚙️ Configuration
Configure your plugin settings in the **Craft CMS Control Panel** under **Settings > Update Tracker**. Adjust:
- Email for notifications.
- Notification frequency (daily, weekly, bi-weekly, monthly).
- Day of the week for notifications.

---

## 🛠️ Usage

### Run Update Check
Check for updates and send notifications:
```bash
php craft update-tracker/check-for-updates/run
```

### Force Update Check
Force an update check and bypass schedule:
```bash
php craft update-tracker/check-for-updates/run --forced
```

---

## 📚 Documentation
For more detailed documentation, visit the [GitHub repository](https://github.com/ColinDorr/craftcms-update-tracker).

## 🐞 Support
- [Report Issues](https://github.com/ColinDorr/craftcms-update-tracker/issues?state=open)
- [Source Code](https://github.com/ColinDorr/craftcms-update-tracker)

---

## 📜 License
This plugin is open-sourced under the [MIT License](LICENSE).

---

Elevate your Craft CMS experience by keeping your system updated with **Update Tracker**! 🚀

