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
- メール認証機能
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
- CSS(設計規則にBEMを採用)
- Git version 2.21.0(ブランチモデルとしてGit-Flowを採用)

## インストール
必要要件に記載している環境を整えた上で、ターミナルで下記コマンドを実行して下さい。

```
git clone https://github.com/the-bears-field/Docker_LEMP_blog
```
```
cd Docker_LEMP_blog && cp www/html/.env.example www/html/.env
```
```
docker-compose build --no-cache
```
```
docker-compose run --rm --no-deps php npm install
```
```
docker-compose up -d
```
CLIENT_URLは、http://localhost:8080です。

## テスト用アカウント情報
<dl>
  <dt>mail</dt>
  <dd><div class="snippet-clipboard-content position-relative"><pre><code>test@example.com</code></pre></div></dd>
  <dt>password</dt>
  <dd><div class="snippet-clipboard-content position-relative"><pre><code>12345678</code></pre></div></dd>
</dl>

## 開発機材
MacBook Pro (Retina, 15-inch, Late 2013) US Keyboard

## 作者
Satoshi Kumano  
mail to: thebearsfield@gmail.com
