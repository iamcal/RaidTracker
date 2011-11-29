-- phpMyAdmin SQL Dump
-- version 3.0.0
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 28, 2011 at 06:31 PM
-- Server version: 5.0.22
-- PHP Version: 5.2.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `eternal_raids`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `player_name` varchar(255) NOT NULL,
  `raid_id` int(10) unsigned NOT NULL,
  `raid_day` date NOT NULL,
  `time_raid` int(10) unsigned NOT NULL,
  `time_wait` int(10) unsigned NOT NULL,
  `time_offline` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `player_name` (`player_name`,`raid_id`),
  KEY `raid_day` (`raid_day`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `bosses`
--

CREATE TABLE `bosses` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `raid_id` int(10) unsigned NOT NULL,
  `raid_day` date NOT NULL,
  `name` varchar(255) NOT NULL,
  `date_kill` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `raid_id` (`raid_id`,`name`),
  KEY `raid_day` (`raid_day`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `qual` tinyint(3) unsigned NOT NULL,
  `level` smallint(5) unsigned NOT NULL,
  `icon` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `loots`
--

CREATE TABLE `loots` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `player_name` varchar(255) NOT NULL,
  `raid_id` int(10) unsigned NOT NULL,
  `raid_day` date NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `date_drop` int(10) unsigned NOT NULL,
  `source` varchar(255) NOT NULL,
  `ded` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `player_name` (`player_name`,`raid_id`,`item_id`),
  KEY `item_id` (`item_id`,`date_drop`),
  KEY `raid_day` (`raid_day`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `name` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `guild` varchar(255) NOT NULL,
  `race` varchar(255) NOT NULL,
  `sex` varchar(255) NOT NULL,
  `level` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `raids`
--

CREATE TABLE `raids` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `day` date NOT NULL,
  `zone` varchar(255) NOT NULL,
  `difficulty` varchar(255) NOT NULL,
  `date_start` int(10) unsigned NOT NULL,
  `date_end` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `day` (`day`,`zone`,`difficulty`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date_create` int(10) unsigned NOT NULL,
  `raid_day` date NOT NULL,
  `user` varchar(255) NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `raid_day` (`raid_day`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `roster`
--

CREATE TABLE `roster` (
  `name` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `race` varchar(255) NOT NULL,
  `sex` varchar(255) NOT NULL,
  `level` tinyint(3) unsigned NOT NULL,
  `rank` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `roster_changes`
--

CREATE TABLE `roster_changes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date_create` int(10) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `race` varchar(255) NOT NULL,
  `sex` varchar(255) NOT NULL,
  `level` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `date_create` (`date_create`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

