CREATE TABLE if not exists lobby
(
count INTEGER NOT NULL,
map VARCHAR(50));
CREATE TABLE if not exists games
(
id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
servername VARCHAR(50) NOT NULL,
roomid INTEGER NOT NULL,
state INTEGER DEFAULT 0,
count INTEGER NOT NULL,
starttime INTEGER NOT NULL,
gametime TEXT,
updatetime INTEGER NOT NULL,
map VARCHAR(50));
CREATE TABLE if not exists users
(
id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
name VARCHAR(50),
color VARCHAR(6),
p_type INTEGER,
connected INTEGER,
gameid INTEGER NOT NULL,
FOREIGN KEY (gameid) REFERENCES games(id)
);