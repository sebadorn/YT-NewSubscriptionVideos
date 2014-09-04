## How does it work?

In order to use the YouTube API and access your account, an OAuth authentication is required. This is done one time and the received refresh token saved in a file on your server.

The OAuth process also requires a client ID (`OAUTH_CLIENTID`) and secret (`OAUTH_CLIENTSECRET`) (see `config.php`). Please get your own key by creating a new project on [Google Console](https://console.developers.google.com/).


## YouTube

The problem with the YouTube API v3 is, that it is not possible to only request all new subscription videos. This was possible in the past, directly as RSS feed.

What I'm doing now, is request all activities which includes all subscription videos and filter by upload. But since a request is limited to maximal 50 results at a time, a lot of *not-video-upload-activities* may push out video uploads.

To counter this, you can go to [youtube.com/subscription_manager](https://www.youtube.com/subscription_manager), select all, and choose to only have uploads shown in your activity feed.


## Google Developers Console

1. Create a new project, for example call it *yt-nsv*.
1. In **APIs & auth > APIs** enable **YouTube Data API v3**. Disable everything else.
1. In **APIs & auth > Credentials** create a new Client ID for a web application.


## Trouble shooting

* Should the application fail to create the file specified by `OAUTH_REFRESHTOKEN_FILE` (see `config.php`), just create it yourself and set its permissions to 666.


## Limits

* Only for 1 user.