Epub Parser
==========

Parse Epub Format Using PHP

This is a Epub Parser written by [Aldrian](https://github.com/justhalf).
It includes epub format checking, decompression and data storage.

File Directory
--------------

	.
	├── handler.php //the epub parser
	├── includes // database credentials
	|   ├── connection.php
	|   └── constants.php
	├── ePub //uploaded epub files
	|   ├── ...
	|   └── *.epub
	├── epubs //parsed ebook contents
	|   ├── ... 
	|   └── bookid/*.html
	├── upload.php //html form uploader
	└── SimpleImage.php //open sourced image handler
	
handler.php is the epub parser. 

includes/* is the database credentials, i.e.,MySQL. 

ePub directory stores the uploaded epubs.

epubs directory stores the parsed ebook contents.

upload.php is a html form uploader for easy ebook uploading.

SimpleImage.php is an open sourced image handler to process the ebook cover image.

Set Up
--------
Dependent Packages:
MySQL and PHP5.

Setup Database :

Configure the MySQL database credentials in includes/constans.php

	define("DB_SERVER","localhost");
	define("DB_NAME","yunreading");
	define("DB_USER","root");
	define("DB_PASS","yourpassword");
	define("SERVER_URL","122.248.240.151");

Create database tables:

Book table

	CREATE TABLE IF NOT EXISTS `Book` (
  		`b_id` int(11) NOT NULL AUTO_INCREMENT,
  		`title` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  		`UUID` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  		`author` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  		`publisher` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  		`publish_date` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  		`create_date` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  		`rights` text COLLATE utf8_unicode_ci,
  		`cover_path` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  		`content_path` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  		`price` decimal(10,0) DEFAULT NULL,
  		`totalChapter` int(11) DEFAULT NULL,
 		`description` text COLLATE utf8_unicode_ci,
 		`borrowerLimit` int(11) DEFAULT NULL,
 		`popularity` int(11) DEFAULT '0',
 		`language` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  		`uploaded_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 		 `uploader` int(11) DEFAULT NULL,
  		PRIMARY KEY (`b_id`),
  		UNIQUE KEY `UUID` (`UUID`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=202 ;
Chapter table

	CREATE TABLE IF NOT EXISTS `Chapter` (
		 `b_id` int(11) NOT NULL DEFAULT '0',
		 `c_id` int(11) NOT NULL,
		 `title` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
 		 `filename` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
		  PRIMARY KEY (`c_id`,`b_id`),
		  KEY `b_id` (`b_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



Setup file direcotry permission: 

Read/Write permissions to ePub and epubs directory.

You're Done.
-----

Drop us an email yunreading@gmail.com or report an issue for bug reporting and collaborations.Thanks!
