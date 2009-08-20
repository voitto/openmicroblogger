-- phpMyAdmin SQL Dump
-- version 2.11.9.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 27, 2009 at 09:15 PM
-- Server version: 4.1.22
-- PHP Version: 5.2.6

--
-- Database: `urlshort`
--

-- --------------------------------------------------------

--
-- Table structure for table `urls`
--

CREATE TABLE IF NOT EXISTS `urls` (
  `id` varchar(255) NOT NULL default '',
  `url` text,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `text` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `urls`
--

INSERT INTO `urls` (`id`, `url`, `date`, `text`) VALUES
('0', 'http://urlshort.sf.net', '2009-07-27 21:09:33', 0),
('1', 'http://urlshort.sourceforge.net/download', '2009-07-27 21:36:32', 0);