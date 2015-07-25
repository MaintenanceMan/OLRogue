create database jpendell_rogue;
use jpendell_rogue;
create table players(name varchar(35),hash varchar(35),clevel int, dlevel int, experience int, hp int);
create user 'jpendell_user'@'localhost' identified by 'vabtha';
GRANT ALL PRIVILEGES ON jpendell_rogue.* to 'jpendell_user'@'localhost';

jpendell_user
vabtha

