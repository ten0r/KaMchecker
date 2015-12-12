DROP TABLE if exists userInGames;
DROP TABLE if exists users;
DROP TABLE if exists lobby;
DROP TABLE if exists games;
CREATE TABLE if not exists lobby
(
count INTEGER NOT NULL,
map VARCHAR(50)
);
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
map VARCHAR(50)
);
CREATE TABLE if not exists users
(
id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
name VARCHAR(50) UNIQUE
);
CREATE TABLE if not exists usersInGames
(
iduser INTEGER NOT NULL,
idgame INTEGER NOT NULL,
connected INTEGER,
color VARCHAR(6),
FOREIGN KEY (iduser) REFERENCES users(id) ON DELETE CASCADE,
FOREIGN KEY (idgame) REFERENCES games(id) ON DELETE CASCADE,
PRIMARY KEY (iduser,idgame,color)
);
