```bash
mysql -u root -p'AdminPassWord' -e "CREATE DATABASE IF NOT EXISTS the_scent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p'AdminPassWord' the_scent < ./db/schema.sql
```
