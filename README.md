# Docker LEMP blog

生のPHPを使用しブログを作成しました。  
1つのブログを複数ユーザーが投稿、管理を行うことを想定しています。

## 機能

- 記事一覧表示機能
- 記事詳細表示機能
- 記事投稿機能
- 記事タグ付機能
- 画像アップロード機能
- ユーザー登録機能
- ユーザーログイン機能
- ページネーション機能

## 必要要件

- Docker
- Docker Compose

## 使用技術
- PHP 7.3.21 (cli)
- MYSQL Ver 14.14 Distrib 5.7.31, for Linux(x86_64)
- Javascript
- jQuery v3.3.1
- nginx/1.19.1
- Docker version 19.03.12
- Docker Compose version 1.26.2

## インストール

```
$ git clone https://github.com/the-bears-field/Docker_LEMP_blog
$ cd Docker_LEMP_blog
$ docker-compose build --no-cache
$ docker-compose up -d
```

## 作者
Satoshi Kumano  
mail to: thebearsfield@mail.com
