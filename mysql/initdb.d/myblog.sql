DROP DATABASE IF EXISTS myblog;
CREATE DATABASE myblog DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE myblog;

#テーブル削除
DROP TABLE IF EXISTS user;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS user_uploaded_posts;
DROP TABLE IF EXISTS post_tags;

#テーブル生成
CREATE TABLE user (
    user_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY UNIQUE KEY,
    name VARCHAR(30) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE KEY,
    admin TINYINT(1) NOT NULL DEFAULT 0,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts
(
    post_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY UNIQUE KEY,
    title VARCHAR(255) NOT NULL,
    post TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tags (
    tag_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY UNIQUE KEY,
    tag_name VARCHAR(50) NOT NULL UNIQUE KEY
);

CREATE TABLE user_uploaded_posts (
    user_id INT(11) NOT NULL,
    post_id INT(11) NOT NULL
);

CREATE TABLE post_tags (
    post_tag_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY UNIQUE KEY,
    post_id INT(11) NOT NULL,
    tag_id INT(11) NOT NULL
);

#ストアドプロシージャ生成
DELIMITER $$

#記事投稿時にタグを登録
DROP PROCEDURE IF EXISTS sp_add_tags $$

CREATE PROCEDURE sp_add_tags (
    IN _tag_name VARCHAR(50),
    IN _post_id INT(11)
)
BEGIN
    DECLARE _tag_id INT DEFAULT 0;
    DECLARE _post_id_has_tag INT DEFAULT 0;

    SELECT tag_id INTO _tag_id FROM tags WHERE tag_name = _tag_name;

    IF _tag_id = 0 THEN
        INSERT INTO tags (tag_name) VALUES (_tag_name);
        SELECT LAST_INSERT_ID() INTO _tag_id ;
    END IF;

    SELECT post_id INTO _post_id_has_tag FROM post_tags WHERE tag_id = _tag_id AND post_id = _post_id;

    IF _post_id_has_tag = 0 THEN
        INSERT INTO post_tags (post_id, tag_id) VALUES (_post_id, _tag_id);
    END IF;
END $$

#記事削除時にタグの登録を削除
DROP PROCEDURE IF EXISTS sp_remove_tags $$

CREATE PROCEDURE sp_remove_tags (
    IN _tag_name VARCHAR(50),
    IN _post_id INT(11)
)
BEGIN
    DECLARE _tag_id INT DEFAULT 0;
    DECLARE _post_tag_id INT DEFAULT 0;
    DECLARE _is_exists_tag_contains_post tinyint(1) DEFAULT 0;

    SELECT tag_id INTO _tag_id FROM tags WHERE tag_name = _tag_name;
    SELECT post_tag_id INTO _post_tag_id FROM post_tags WHERE post_id = _post_id AND tag_id = _tag_id;

    IF _post_tag_id != 0 THEN
        DELETE FROM post_tags WHERE post_id = _post_id AND tag_id = _tag_id;
    END IF;

    SELECT COUNT(*) INTO _is_exists_tag_contains_post FROM post_tags WHERE tag_id = _tag_id;

    IF _is_exists_tag_contains_post = 0 THEN
        DELETE FROM tags WHERE tag_name = _tag_name;
    END IF;
END $$

DELIMITER ;

# userデータ挿入
INSERT INTO user(name, email, password) VALUES('test-user', 'test@test.com', '$2y$10$KSxv6HgNfjTdbP7P/KbGXeKQ4FzXvlPhSDekqJhpsdU0DAIpaR64G');
