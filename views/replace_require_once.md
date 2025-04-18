sed -i "s|require_once 'layout|require_once __DIR__ . '/layout|g" filename.php

pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ sed -i "s|require_once 'layout|require_once __DIR__ . '/layout|g" cart.php
pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ sed -i "s|require_once 'layout|require_once __DIR__ . '/layout|g" checkout.php
pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ sed -i "s|require_once 'layout|require_once __DIR__ . '/layout|g" error.php
pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ sed -i "s|require_once 'layout|require_once __DIR__ . '/layout|g" forgot_password.php
pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ sed -i "s|require_once 'layout|require_once __DIR__ . '/layout|g" login.php
pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ sed -i "s|require_once 'layout|require_once __DIR__ . '/layout|g" order_confirmation.php
pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ sed -i "s|require_once 'layout|require_once __DIR__ . '/layout|g" product_detail.php
pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ sed -i "s|require_once 'layout|require_once __DIR__ . '/layout|g" products.php
pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ sed -i "s|require_once 'layout|require_once __DIR__ . '/layout|g" products.php
pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ sed -i "s|require_once 'layout|require_once __DIR__ . '/layout|g" reset_password.php
pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ grep require_once * | grep -v DIR
grep: account: Is a directory
grep: admin: Is a directory
grep: emails: Is a directorycart.php.bak:<?php require_once 'layout/header.php'; ?>

cart.php.bak:<?php require_once 'layout/footer.php'; ?>
grep: layout: Is a directory
register.php:<?php require_once 'layout/header.php'; ?>
register.php:<?php require_once 'layout/footer.php'; ?>
pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ sed -i "s|require_once 'layout|require_once __DIR__ . '/layout|g" register.php
pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ grep require_once * | grep -v DIR
grep: account: Is a directory
grep: admin: Is a directory
grep: emails: Is a directory
cart.php.bak:<?php require_once 'layout/header.php'; ?>
cart.php.bak:<?php require_once 'layout/footer.php'; ?>
grep: layout: Is a directory
pete@pop-os:/cdrom/project/The-Scent-oa5/views
$ grep require_once register.php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<?php require_once __DIR__ . '/layout/footer.php'; ?>

