# create database user separately
```bash
mysql -u root -p'AdminPassWord' -e "CREATE DATABASE IF NOT EXISTS the_scent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p'AdminPassWord' the_scent < ./db/schema.sql
```

---
# full database schema backup
```bash
mysqldump -h localhost -u root -p --default-character-set=utf8mb4 --no-data --routines --triggers --events --databases the_scent > the_scent_schema.sql
mysqldump -h localhost -u scent_user -p --default-character-set=utf8mb4 --no-data --routines --triggers --events --databases the_scent > the_scent_schema.sql
```

---
# full database backup
```bash
mysqldump -h localhost -u root -p --single-transaction --default-character-set=utf8mb4 --routines --triggers --events --databases the_scent | gzip > the_scent_full_backup.sql.gz
```
