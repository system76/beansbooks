-- I have absolutely no clue how to handle upgrades natively with Kohana, so I'm stashing this here for the time being!
-- This is an upgrade to add a "type" column to the payments view.
ALTER TABLE transactions
ADD `type` enum('cash','check','credit card','transfer','other') DEFAULT NULL AFTER `code`;