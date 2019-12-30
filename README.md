# Chatify Laravel Package
It is a Laravel package to add a complete real-time chat system to your application with one command line.

# Features
 - Users / groups(soon) chat system.
 - Real-time contacts list updates.
 - Favorites contacts list (Like stories style) and add to favorite button.
 - Saved Messages to save your messages online like Telegram messenger app.
 - Search functionality.
 - Contact item's last message indicator (e.g. You: ....).
 - Real-time user's active status.
 - Real-time typing indicator.
 - Real-time seen messages indicator.
 - Upload attachments (Photo/File).
 - User settings and chat customization : user's profile photo, dark mode and chat color.
   with simple and wonderful UI design.

# Demo
Soon

# Installation
Video Tutorial [SOON]
##### Steps :
#### 1. Install the package in your Laravel app
```sh
$ composer require munafio/chatify
```

#### 2. Publishing Assets
Packages' assets to be published :<br/>
The Important assets:
- config
- assets
- migrations

 and the optional assets :
- controllers
- views

to pusblish the assets, do the following command line with changing the tag value .. that means after `--tag=` write `chatify-` + asset name as mentioned above.
Example :
```sh
$ php artisan vendor:publish --tag=chatify-config
```

#### 3. Migrations
Migrate the new `migrations` that added by the previous step 
```sh
$ php artisan migrate
```
#### 4. Storage Symlink
Create a shourtcut or a symlink to the `storage` folder into the `public` folder
```sh
$ php artisan storage:link
```
##### That's it .. Enjoy :)

