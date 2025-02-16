drop database if exists `php2core`;
create database `php2core`;
use `php2core`;

create table `instance`
(
    `id` int(11) not null auto_increment,
    `name` varchar(32) not null,
    primary key(`id`)
)Engine=InnoDB;

create table `route`
(
    `id` int(11) not null auto_increment,
    `instance-id` int(11) null,
    `default` enum('true', 'false') not null default('false'),
    `method` enum('get', 'post') not null default('get'),
    `match` varchar(128) not null,
    `type` enum('file', 'function') not null default('file'),
    `target` varchar(128) not null,
    primary key(`id`),
    foreign key(`instance-id`) references `instance`(`id`) on delete cascade
)Engine=InnoDb;

insert into `route`(`default`, `method`, `match`, `target`, `type`)
values
('false', 'get', 'index', 'index.php', 'file'),
('false', 'get', 'admin-rdb', 'Php2Core::ResetDatabases', 'function'),
('false', 'get', 'admin-cm', 'Php2Core::ClassMap', 'function');