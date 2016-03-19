CREATE TABLE IF NOT EXISTS `candidates` (
  `user_oc_id` int(11) NOT NULL,
  `user_name` varchar(30) NOT NULL,
  `region` varchar(30) NOT NULL,
  PRIMARY KEY (`user_oc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

--example data:
INSERT INTO `candidates` (`user_oc_id`, `user_name`, `region`) VALUES ('32795', 'triPPer', 'pomorskie');
INSERT INTO `candidates` (`user_oc_id`, `user_name`, `region`) VALUES ('10046', 'Agatha', 'pomorskie');
INSERT INTO `candidates` (`user_oc_id`, `user_name`, `region`) VALUES ('15739', 'schron', 'pomorskie');
INSERT INTO `candidates` (`user_oc_id`, `user_name`, `region`) VALUES ('44710', 'krecik34', 'wielkopolskie');
INSERT INTO `candidates` (`user_oc_id`, `user_name`, `region`) VALUES ('59665;', 'MSZU', 'wielkopolskie');
