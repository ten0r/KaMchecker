CREATE TABLE if not exists lobby
(
count INTEGER NOT NULL,
map VARCHAR(50));
CREATE TABLE if not exists games
(
id INTEGER AUTOINCREMENT NOT NULL,
servername VARCHAR(50) NOT NULL,
roomid INTEGER NOT NULL,
state INTEGER DEFAULT 0,
count INTEGER NOT NULL,
starttime INTEGER NOT NULL,
gametime TEXT,
updatetime INTEGER NOT NULL,
map VARCHAR(50),
PRIMARY KEY (id)
);
CREATE TABLE if not exists users
(
id INTEGER AUTOINCREMENT NOT NULL,
name VARCHAR(50),
p_type INTEGER,
PRIMARY KEY (id)
);
CREATE TABLE if not exists usersInGames
(
iduser INTEGER NOT NULL ON DELETE CASCADE,
idgame INTEGER NOT NULL ON DELETE CASCADE,
connected INTEGER DEFAULT '0',
color VARCHAR(6),
FOREIGN KEY (iduser) REFERENCES users(id),
FOREIGN KEY (idgame) REFERENCES games(id),
PRIMARY KEY (iduser,idgame)
);
