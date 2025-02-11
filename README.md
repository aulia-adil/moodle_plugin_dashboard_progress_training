# Cara Instalasi

Lakukan ini di Bitnami Moodle. Bitnami Moodle menggunakan OS Debian

```
apt update -y
apt install -y python3 git
cd /bitnami
git clone https://github.com/ytdl-org/youtube-dl.git
cd /bitnami
chmod -R 0755 moodle
find moodle -type f -exec chmod 0644 {} \;
cd /bitnami/moodle
php /bitnami/moodle/admin/cli/upgrade.php # type y when it prompts to install database
```