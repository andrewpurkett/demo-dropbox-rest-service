demo-dropbox-rest-service
=============

Please refer to [the original git log](https://github.com/andrewpurkett/demo-dropbox-rest-service/blob/master/original.log). Commit history has been squashed for security reasons.

This package is designed to be run within a parent repository's Vagrantbox.

To manually launch the queue service, every time you start your VM perform the following steps:
  - `vagrant ssh`
  - `cd /vagrant/dropbox`
  - `artisan migrate`
  - `artisan queue:listen --queue=dropbox.sync.dev --timeout=3600 --sleep=120 &`
  - `artisan queue:listen --queue=files.dropbox.dev --timeout=3600 &`
